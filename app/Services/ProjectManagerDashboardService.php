<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ProjectManagerDashboardService
{
    /**
     * Return dashboard-ready positions assigned to the supplied project manager.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function positionsFor(User $user): Collection
    {
        return $this->positionQuery()
            ->where('project_manager_user_id', $user->id)
            ->get()
            ->map(fn (Position $position) => $this->transformPosition($position))
            ->sortBy([
                ['attention_rank', 'asc'],
                ['days_open', 'desc'],
                ['position_code', 'asc'],
            ])
            ->values();
    }

    /**
     * Return every position for the PMO dashboard.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function allPositionsForPmo(): Collection
    {
        $positions = $this->positionQuery()
            ->with('projectManager:id,name,email')
            ->get();

        $personByUserId = Person::query()
            ->whereIn(
                'user_id',
                $positions
                    ->pluck('project_manager_user_id')
                    ->filter()
                    ->unique()
                    ->values()
            )
            ->get(['id', 'user_id'])
            ->keyBy('user_id');

        return $positions
            ->map(function (Position $position) use ($personByUserId) {
                $metrics = $this->transformPosition($position);
                $projectManager = $position->projectManager;
                $person = $projectManager
                    ? $personByUserId->get($projectManager->id)
                    : null;

                return [
                    ...$metrics,
                    'project_manager' => [
                        'id' => $projectManager?->id,
                        'name' => $projectManager?->name,
                        'email' => $projectManager?->email,
                        'person_id' => $person?->id,
                    ],
                ];
            })
            ->sortBy([
                ['attention_rank', 'asc'],
                ['days_open', 'desc'],
                ['position_code', 'asc'],
            ])
            ->values();
    }


    /**
     * Return opportunities and workflow progress for the Person linked to the
     * supplied Candidate user.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function candidateOpportunitiesFor(User $user): Collection
    {
        $person = Person::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $person) {
            return collect();
        }

        return Candidate::query()
            ->where('person_id', $person->id)
            ->with([
                'position:id,position_code,job_title,status',
                'workflow.steps',
                'stepEvents.workflowStep',
            ])
            ->latest('updated_at')
            ->get()
            ->map(function (Candidate $candidate): array {
                $metrics = $this->candidateMetrics($candidate);

                return [
                    'candidate_id' => $candidate->id,
                    'position_id' => $candidate->position_id,
                    'position_code' => $candidate->position?->position_code,
                    'position_title' => $candidate->position?->job_title
                        ?? 'Untitled Position',
                    'position_status' => $candidate->position?->status,
                    'candidate_status' => $candidate->status,
                    'workflow_name' => $metrics['workflow_name'],
                    'current_stage' => $metrics['step_name'],
                    'step_number' => $metrics['step_number'],
                    'step_count' => $metrics['step_count'],
                    'status_code' => $metrics['status_code'],
                    'last_updated' => $candidate->updated_at?->toIso8601String(),
                ];
            })
            ->values();
    }

    private function positionQuery()
    {
        return Position::query()
            ->with([
                'candidates.person:id,first_name,preferred_name,last_name',
                'candidates.workflow.steps',
                'candidates.stepEvents.workflowStep',
                'assignments',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformPosition(Position $position): array
    {
        $candidateMetrics = $position->candidates
            ->map(fn (Candidate $candidate) => $this->candidateMetrics($candidate));

        $mostAdvanced = $candidateMetrics
            ->sortByDesc('step_number')
            ->first();

        $currentStage = $this->currentStage($candidateMetrics, $mostAdvanced);
        $daysOpen = $this->daysOpen($position);
        $nextAction = $this->nextAction($position, $candidateMetrics, $mostAdvanced);

        return [
            'id' => $position->id,
            'position_code' => $position->position_code,
            'title' => $position->job_title
                ?? $position->title
                ?? $position->team_name
                ?? 'Untitled Position',
            'status' => $position->status,
            'candidates_count' => $position->candidates->count(),
            'candidate_names' => $position->candidates
                ->map(fn (Candidate $candidate) => $this->candidateName($candidate))
                ->filter()
                ->values()
                ->all(),
            'candidate_summaries' => $candidateMetrics
                ->map(fn (array $metrics) => [
                    'id' => $metrics['candidate_id'],
                    'person_id' => $metrics['person_id'],
                    'name' => $metrics['candidate_name'],
                    'status' => $metrics['candidate_status'],
                    'stage' => $metrics['step_name'],
                ])
                ->values()
                ->all(),
            'current_stage' => $currentStage['label'],
            'current_stage_count' => $currentStage['count'],
            'current_stage_candidate_id' => $mostAdvanced['candidate_id'] ?? null,
            'days_open' => $daysOpen,
            'next_action' => $nextAction['label'],
            'next_action_tone' => $nextAction['tone'],
            'attention_rank' => $nextAction['rank'],
            'staffing_state' => $this->staffingState($position, $candidateMetrics),
        ];
    }

    /**
     * Build the four staffing totals shown above position dashboard tables.
     *
     * @param Collection<int, array<string, mixed>> $positions
     * @return array{vacant: int, selected: int, departing: int, onHold: int}
     */
    public function staffingSummary(Collection $positions): array
    {
        $counts = $positions->countBy('staffing_state');

        return [
            'vacant' => (int) $counts->get('vacant', 0),
            'selected' => (int) $counts->get('selected', 0),
            'departing' => (int) $counts->get('departing', 0),
            'onHold' => (int) $counts->get('on_hold', 0),
        ];
    }

    /**
     * Determine the staffing condition independently of the recruiting status
     * displayed in the positions table.
     *
     * @param Collection<int, array<string, mixed>> $candidateMetrics
     */
    private function staffingState(Position $position, Collection $candidateMetrics): string
    {
        $positionStatus = Str::of((string) $position->status)
            ->lower()
            ->replace(['-', '_'], ' ')
            ->squish()
            ->toString();

        if (Str::contains($positionStatus, ['on hold', 'hold'])) {
            return 'on_hold';
        }

        $departingAssignment = $position->assignments->first(function ($assignment): bool {
            $assignmentStatus = Str::of((string) $assignment->assignment_status)
                ->lower()
                ->replace(['-', '_'], ' ')
                ->squish()
                ->toString();

            if (Str::contains($assignmentStatus, ['departing', 'ending'])) {
                return true;
            }

            if (! $assignment->end_date || in_array($assignmentStatus, ['ended', 'inactive'], true)) {
                return false;
            }

            return Carbon::parse($assignment->end_date)->betweenIncluded(
                now()->startOfDay(),
                now()->addDays(30)->endOfDay(),
            );
        });

        if ($departingAssignment) {
            return 'departing';
        }

        $hasSelectedCandidate = $candidateMetrics->contains(function (array $metrics): bool {
            $candidateStatus = Str::of((string) ($metrics['candidate_status'] ?? ''))
                ->lower()
                ->replace(['-', '_'], ' ')
                ->squish()
                ->toString();

            return in_array($candidateStatus, [
                'selected',
                'approved',
                'assigned',
                'hired',
                'onboarding',
            ], true);
        });

        $hasStaffedAssignment = $position->assignments->contains(function ($assignment): bool {
            $assignmentStatus = Str::lower((string) $assignment->assignment_status);

            return in_array($assignmentStatus, ['active', 'planned', 'selected'], true)
                && (! $assignment->end_date || Carbon::parse($assignment->end_date)->isFuture());
        });

        if ($hasSelectedCandidate || $hasStaffedAssignment || Str::contains($positionStatus, ['selected', 'filled'])) {
            return 'selected';
        }

        return 'vacant';
    }

    /**
     * @return array<string, mixed>
     */
    private function candidateMetrics(Candidate $candidate): array
    {
        $currentEvent = $candidate->stepEvents
            ->sortByDesc(fn ($event) => $event->completed_at
                ?? $event->scheduled_at
                ?? $event->requested_at
                ?? $event->updated_at)
            ->first();

        $firstStep = $candidate->workflow?->steps
            ?->sortBy('step_order')
            ->first();

        $workflowStep = $currentEvent?->workflowStep ?? $firstStep;

        return [
            'candidate_id' => $candidate->id,
            'person_id' => $candidate->person_id,
            'candidate_name' => $this->candidateName($candidate) ?? 'Unknown candidate',
            'candidate_status' => $candidate->status,
            'workflow_name' => $candidate->workflow?->name,
            'step_name' => $workflowStep?->name ?? 'Not started',
            'step_number' => (int) ($workflowStep?->step_order ?? 0),
            'step_count' => (int) ($candidate->workflow?->steps?->count() ?? 0),
            'status_code' => $currentEvent?->status_code,
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $candidateMetrics
     * @param array<string, mixed>|null $mostAdvanced
     * @return array{label: string, count: int}
     */
    private function currentStage(Collection $candidateMetrics, ?array $mostAdvanced): array
    {
        if ($candidateMetrics->isEmpty()) {
            return [
                'label' => 'No candidates',
                'count' => 0,
            ];
        }

        $stage = $mostAdvanced['step_name'] ?? 'Not started';
        $count = $candidateMetrics
            ->filter(fn (array $metrics) => $metrics['step_name'] === $stage)
            ->count();

        return [
            'label' => $stage,
            'count' => $count,
        ];
    }

    private function daysOpen(Position $position): int
    {
        $openedAt = $position->customer_created_at ?? $position->created_at;
        $closedAt = $position->close_date ?? now();

        if (! $openedAt) {
            return 0;
        }

        return max(0, $openedAt->startOfDay()->diffInDays($closedAt->startOfDay()));
    }

    /**
     * @param Collection<int, array<string, mixed>> $candidateMetrics
     * @param array<string, mixed>|null $mostAdvanced
     * @return array{label: string, tone: string, rank: int}
     */
    private function nextAction(
        Position $position,
        Collection $candidateMetrics,
        ?array $mostAdvanced
    ): array {
        $positionStatus = Str::lower((string) $position->status);

        if (in_array($positionStatus, ['closed', 'cancelled', 'canceled'], true)) {
            return ['label' => 'Closed', 'tone' => 'neutral', 'rank' => 50];
        }

        if ($candidateMetrics->isEmpty()) {
            return ['label' => 'Need Candidates', 'tone' => 'danger', 'rank' => 10];
        }

        $candidateStatus = Str::lower((string) ($mostAdvanced['candidate_status'] ?? ''));
        $step = Str::lower((string) ($mostAdvanced['step_name'] ?? ''));
        $statusCode = Str::lower((string) ($mostAdvanced['status_code'] ?? ''));
        $combined = trim("{$candidateStatus} {$step} {$statusCode}");

        if (Str::contains($combined, ['offer accepted', 'selected', 'hired', 'onboarding'])) {
            return ['label' => 'Ready to Hire', 'tone' => 'success', 'rank' => 20];
        }

        if (Str::contains($combined, ['offer'])) {
            return ['label' => 'Await Candidate', 'tone' => 'warning', 'rank' => 25];
        }

        if (Str::contains($combined, ['customer', 'client', 'manager review', 'approval'])) {
            return ['label' => 'Customer Review', 'tone' => 'warning', 'rank' => 15];
        }

        if (Str::contains($combined, ['interview'])) {
            return ['label' => 'Complete Interview', 'tone' => 'warning', 'rank' => 12];
        }

        if (Str::contains($combined, ['background', 'security', 'clearance'])) {
            return ['label' => 'Monitor Screening', 'tone' => 'info', 'rank' => 30];
        }

        if (Str::contains($combined, ['submitted', 'review', 'screen', 'not started'])) {
            return ['label' => 'Review Candidates', 'tone' => 'info', 'rank' => 11];
        }

        return ['label' => 'Advance Workflow', 'tone' => 'info', 'rank' => 35];
    }

    private function candidateName(Candidate $candidate): ?string
    {
        if (! $candidate->person) {
            return null;
        }

        return trim(
            ($candidate->person->preferred_name ?: $candidate->person->first_name)
            .' '
            .$candidate->person->last_name
        );
    }
}
