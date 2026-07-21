<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectManagerDashboardService
{
    /**
     * Return dashboard-ready positions assigned to the supplied project manager.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function positionsFor(User $user): Collection
    {
        return Position::query()
            ->where('project_manager_user_id', $user->id)
            ->with([
                'candidates.person:id,first_name,preferred_name,last_name',
                'candidates.workflow.steps',
                'candidates.stepEvents.workflowStep',
            ])
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
        ];
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
