<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\CandidateStepEvent;
use App\Models\Person;
use App\Models\Position;
use App\Models\Workflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CandidateSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $workflow = Workflow::query()->where('code', 'default_candidate_workflow')->firstOrFail();
            $steps = $workflow->steps()->get()->keyBy('code');
            $positions = Position::query()->where('position_code', 'like', 'IRAD-%')->get()->keyBy('position_code');
            $performers = Person::query()->whereIn('email', [
                'owner@localhost', 'admin@localhost', 'cotr@localhost', 'pmo@localhost',
                'project.manager1@localhost', 'project.manager2@localhost',
            ])->get()->keyBy('email');

            $submittedBy = $performers->get('pmo@localhost') ?? $performers->first();
            $reviewer = $performers->get('project.manager1@localhost') ?? $performers->first();
            $interviewer = $performers->get('project.manager2@localhost') ?? $performers->first();
            $approver = $performers->get('cotr@localhost') ?? $performers->first();

            // Keep development candidate scenarios deterministic when this
            // seeder is rerun without migrate:fresh. Candidates removed from a
            // filled position must not linger as historical rows in the matrix.
            Candidate::query()
                ->where('candidate_code', 'like', 'CAND-%')
                ->delete();

            $scenarios = [
                // Filled positions intentionally have one candidate only. Their
                // workflow is complete through the final configured step.
                ['CAND-001', 'Trapper John', 'McIntyre', 'IRAD-SWE-001', 'assigned', 'subcontract_signed', 58],
                ['CAND-004', 'Walter', "O'Reilly", 'IRAD-BA-004', 'selected', 'interview_scheduled', 24, 'Radar'],
                ['CAND-005', 'Maxwell', 'Klinger', 'IRAD-BA-004', 'submitted', 'resume_review', 11, 'Klinger'],
                ['CAND-006', 'Francis', 'Mulcahy', 'IRAD-PM-007', 'selected', 'tech_screen_scheduled', 29, 'Father Mulcahy'],
                ['CAND-007', 'Sidney', 'Freedman', 'IRAD-PM-007', 'approved', 'offer_sent', 43],
                ['CAND-008', 'Kellye', 'Nakahara', 'IRAD-QA-008', 'selected', 'interview_completed', 27],
                ['CAND-009', 'Ginger', 'Bayliss', 'IRAD-QA-008', 'submitted', 'interview_requested', 15],
                ['CAND-010', 'Luther', 'Rizzo', 'IRAD-NET-006', 'submitted', 'tech_screen_cancelled', 62],
                ['CAND-011', 'Henry', 'Blake', 'IRAD-DBA-005', 'assigned', 'subcontract_signed', 120],
                ['CAND-013', 'Sam', 'Flagg', 'IRAD-CYB-002', 'selected', 'tech_screen_completed', 34],
                ['CAND-014', 'Spearchucker', 'Jones', 'IRAD-CYB-002', 'submitted', 'resume_review', 9],
                ['CAND-015', 'Donald', 'Penobscott', 'IRAD-DOP-003', 'approved', 'crossover_approved', 37],
                ['CAND-016', 'Ugly John', 'Black', 'IRAD-DOP-003', 'selected', 'interview_completed', 22],
                ['CAND-017', 'Nurse', 'Bigelow', 'IRAD-DATA-009', 'submitted', 'interview_requested', 8, 'Bigelow'],
                ['CAND-018', 'Nurse', 'Baker', 'IRAD-SYS-010', 'submitted', 'resume_review', 5, 'Baker'],
            ];

            foreach ($scenarios as $index => $scenario) {
                [$code, $first, $last, $positionCode, $candidateStatus, $stage, $daysAgo] = $scenario;
                $preferred = $scenario[7] ?? null;

                $person = Person::updateOrCreateByPersonCode(
                    $code,
                    [
                        'first_name' => $first,
                        'preferred_name' => $preferred,
                        'last_name' => $last,
                        'company_name' => '4077th Talent Group',
                        'email' => strtolower(str_replace([' ', "'"], ['.', ''], $preferred ?: $first).'.'.str_replace([' ', "'"], ['', ''], $last)).'@example.test',
                        'employment_status' => $candidateStatus === 'assigned' ? 'Active' : 'Candidate',
                        'notes' => 'Scenario-driven M*A*S*H development candidate.',
                    ]
                );

                $position = $positions->get($positionCode);
                if (! $position) {
                    continue;
                }

                $candidate = Candidate::updateOrCreate(
                    ['candidate_code' => $code],
                    [
                        'person_id' => $person->id,
                        'position_id' => $position->id,
                        'workflow_id' => $workflow->id,
                        'status' => $candidateStatus,
                        'candidate_fbr' => 0.75 + (($index % 6) * 0.15),
                        'submitted_by_person_id' => $submittedBy?->id,
                        'submitted_at' => now()->subDays($daysAgo),
                        'scheduled_start_date' => $candidateStatus === 'assigned' ? now()->subDays(14 + $index)->toDateString() : null,
                    ]
                );

                CandidateStepEvent::query()->where('candidate_id', $candidate->id)->delete();
                $this->seedWorkflow($candidate, $steps, $stage, $daysAgo, $reviewer, $interviewer, $approver);
            }
        });
    }

    private function seedWorkflow(Candidate $candidate, $steps, string $stage, int $daysAgo, ?Person $reviewer, ?Person $interviewer, ?Person $approver): void
    {
        $submitted = now()->subDays($daysAgo);

        $this->event($candidate, $steps['resume_review'], null, null, null, $submitted->copy()->addDays(2), $reviewer, 'Resume reviewed against position requirements.');

        if ($stage === 'resume_review') return;

        $interviewStatus = str_contains($stage, 'interview_cancelled') ? 'cancelled'
            : (str_contains($stage, 'interview_requested') ? 'requested'
            : (str_contains($stage, 'interview_scheduled') ? 'scheduled' : 'completed'));

        $this->event(
            $candidate,
            $steps['interview'],
            $interviewStatus,
            $submitted->copy()->addDays(4),
            in_array($interviewStatus, ['scheduled', 'completed'], true) ? $submitted->copy()->addDays(8) : null,
            $interviewStatus === 'completed' ? $submitted->copy()->addDays(8)->addHour() : ($interviewStatus === 'cancelled' ? $submitted->copy()->addDays(6) : null),
            $interviewer,
            $interviewStatus === 'cancelled' ? 'Interview cancelled; candidate was not advanced.' : 'Interview activity recorded for the development scenario.'
        );

        if (str_starts_with($stage, 'interview_')) return;

        $techStatus = str_contains($stage, 'tech_screen_cancelled') ? 'cancelled'
            : (str_contains($stage, 'tech_screen_scheduled') ? 'scheduled'
            : (str_contains($stage, 'tech_screen_completed') || in_array($stage, ['crossover_approved', 'crossover_denied', 'offer_sent', 'offer_signed', 'subcontract_signed'], true) ? 'completed' : 'requested'));

        $this->event(
            $candidate,
            $steps['tech_screen'],
            $techStatus,
            $submitted->copy()->addDays(10),
            in_array($techStatus, ['scheduled', 'completed'], true) ? $submitted->copy()->addDays(13) : null,
            $techStatus === 'completed' ? $submitted->copy()->addDays(13)->addHours(2) : ($techStatus === 'cancelled' ? $submitted->copy()->addDays(12) : null),
            $reviewer,
            $techStatus === 'cancelled' ? 'Technical screen cancelled.' : 'Technical screen activity recorded.'
        );

        if (str_starts_with($stage, 'tech_screen_')) return;

        $crossoverStatus = $stage === 'crossover_denied' ? 'denied' : ($stage === 'crossover_approved' || in_array($stage, ['offer_sent', 'offer_signed', 'subcontract_signed'], true) ? 'approved' : 'submitted');
        $this->event($candidate, $steps['crossover'], $crossoverStatus, $submitted->copy()->addDays(15), null, $submitted->copy()->addDays(17), $approver, 'Crossover review decision recorded.');

        if (str_starts_with($stage, 'crossover_')) return;

        $this->event($candidate, $steps['offer_sent'], null, null, null, $submitted->copy()->addDays(20), $approver, null, 'Offer package sent to candidate.');
        if ($stage === 'offer_sent') return;

        $this->event($candidate, $steps['offer_signed'], null, null, null, $submitted->copy()->addDays(23), $candidate->person, null, 'Candidate signed the offer.');
        if ($stage === 'offer_signed') return;

        $this->event($candidate, $steps['subcontract_signed'], null, null, null, $submitted->copy()->addDays(27), $approver, null, 'Subcontract completed; candidate ready for assignment.');
    }

    private function event(Candidate $candidate, $step, ?string $status, $requested, $scheduled, $completed, ?Person $performedBy, ?string $notes = null, ?string $comments = null): void
    {
        CandidateStepEvent::create([
            'candidate_id' => $candidate->id,
            'workflow_step_id' => $step->id,
            'status_code' => $status,
            'requested_at' => $requested,
            'scheduled_at' => $scheduled,
            'completed_at' => $completed,
            'performed_by_person_id' => $performedBy?->id,
            'notes' => $notes,
            'comments' => $comments,
            'metadata' => ['seed_scenario' => true],
        ]);
    }
}
