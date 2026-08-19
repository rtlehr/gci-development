<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Carbon;
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
        return $this->positionQuery()
            ->where('project_manager_user_id', $user->id)
            ->get()
            ->map(function (Position $position) use ($user): array {
                $metrics = $this->transformPosition($position);

                return [
                    ...$metrics,
                    'project_manager_name' => $user->name,
                    'search_text' => trim(($metrics['search_text'] ?? '').' '.$user->name),
                ];
            })
            ->sortBy([
                ['staffing_rank', 'asc'],
                ['created_at_sort', 'asc'],
                ['position_code', 'asc'],
            ])
            ->values();
    }

    /**
     * Return every position for the PMO/COTR staffing dashboard.
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
                    'project_manager_name' => $projectManager?->name,
                    'search_text' => trim(($metrics['search_text'] ?? '').' '.($projectManager?->name ?? '').' '.($projectManager?->email ?? '')),
                ];
            })
            ->sortBy([
                ['staffing_rank', 'asc'],
                ['created_at_sort', 'asc'],
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

    /**
     * Column definition shared by the dashboard staffing matrix, preferences,
     * and CSV export. The default view includes the staffing status alongside
     * the core position fields and workflow link.
     *
     * @return array<int, array{key:string,label:string,default_visible:bool,default_order:int}>
     */
    public function staffingColumns(): array
    {
        return [
            ['key' => 'position_code', 'label' => 'ID', 'default_visible' => true, 'default_order' => 10],
            ['key' => 'title', 'label' => 'Job Title', 'default_visible' => true, 'default_order' => 20],
            ['key' => 'staffing_state', 'label' => 'Staffing Status', 'default_visible' => true, 'default_order' => 30],
            ['key' => 'level', 'label' => 'Level', 'default_visible' => true, 'default_order' => 40],
            ['key' => 'team_name', 'label' => 'Team Name', 'default_visible' => true, 'default_order' => 50],
            ['key' => 'location', 'label' => 'Location', 'default_visible' => true, 'default_order' => 60],
            ['key' => 'building', 'label' => 'Building', 'default_visible' => true, 'default_order' => 70],
            ['key' => 'created_at', 'label' => 'Created', 'default_visible' => true, 'default_order' => 80],
            ['key' => 'closed_at', 'label' => 'Closed', 'default_visible' => true, 'default_order' => 90],
            ['key' => 'workflow_link', 'label' => 'Workflow', 'default_visible' => true, 'default_order' => 100],
            ['key' => 'current_person_name', 'label' => 'Current Person', 'default_visible' => false, 'default_order' => 110],
            ['key' => 'employer', 'label' => 'Employer', 'default_visible' => false, 'default_order' => 120],
            ['key' => 'project_team_name', 'label' => 'Project Team', 'default_visible' => false, 'default_order' => 130],
            ['key' => 'project_manager_name', 'label' => 'Project Manager', 'default_visible' => false, 'default_order' => 140],
            ['key' => 'current_workflow_step', 'label' => 'Current Workflow Step', 'default_visible' => false, 'default_order' => 150],
            ['key' => 'scheduled_start_date', 'label' => 'Scheduled Start', 'default_visible' => false, 'default_order' => 160],
            ['key' => 'actual_start_date', 'label' => 'Actual Start', 'default_visible' => false, 'default_order' => 170],
            ['key' => 'departure_date', 'label' => 'Departure', 'default_visible' => false, 'default_order' => 180],
            ['key' => 'last_updated', 'label' => 'Last Updated', 'default_visible' => false, 'default_order' => 190],
        ];
    }

    /**
     * Build the five staffing totals shown above position dashboard tables.
     *
     * @param Collection<int, array<string, mixed>> $positions
     * @return array{vacant:int,selected:int,filled:int,departing:int,onHold:int}
     */
    public function staffingSummary(Collection $positions): array
    {
        $counts = $positions->countBy('staffing_state');

        return [
            'vacant' => (int) $counts->get('vacant', 0),
            'selected' => (int) $counts->get('selected', 0),
            'filled' => (int) $counts->get('filled', 0),
            'departing' => (int) $counts->get('departing', 0),
            'onHold' => (int) $counts->get('on_hold', 0),
        ];
    }

    private function positionQuery()
    {
        return Position::query()
            ->with([
                'candidates.person:id,person_code,first_name,alternate_first_name,preferred_name,last_name,alternate_last_name,company_name,employment_status',
                'candidates.workflow.steps',
                'candidates.stepEvents.workflowStep',
                'assignments.person:id,person_code,first_name,alternate_first_name,preferred_name,last_name,alternate_last_name,company_name,employment_status',
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

        $staffingState = $this->staffingState($position, $candidateMetrics);
        $currentAssignment = $this->currentAssignment($position);
        $workflowCandidates = $position->candidates
            ->map(fn (Candidate $candidate) => $this->workflowCandidate($candidate))
            ->values();

        $selectedCandidate = $this->selectedCandidate($position->candidates);
        $currentPerson = $currentAssignment?->person;
        $currentWorkflow = $this->currentWorkflow($workflowCandidates);
        $lastUpdated = $this->lastUpdated($position);
        $createdAt = $position->customer_created_at ?? $position->created_at;

        return [
            'id' => $position->id,
            'position_code' => $position->position_code,
            'title' => $position->job_title
                ?? $position->title
                ?? $position->team_name
                ?? 'Untitled Position',
            'level' => $position->level,
            'team_name' => $position->team_name,
            'project_team_name' => $position->project_team_name,
            'location' => $position->location,
            'building' => $position->building,
            'created_at' => $createdAt?->toDateString(),
            'created_at_sort' => $createdAt?->timestamp ?? PHP_INT_MAX,
            'closed_at' => $position->close_date?->toDateString(),
            'status' => $position->status,
            'staffing_state' => $staffingState,
            'staffing_label' => $this->staffingLabel($staffingState),
            'staffing_rank' => $this->staffingRank($staffingState),
            'current_person' => $currentPerson ? $this->personDetails($currentPerson) : null,
            'current_person_name' => $currentPerson ? $this->personName($currentPerson) : null,
            'employer' => $currentPerson?->company_name
                ?? $selectedCandidate?->person?->company_name,
            'actual_start_date' => $currentAssignment?->start_date
                ? Carbon::parse($currentAssignment->start_date)->toDateString()
                : null,
            'departure_date' => $currentAssignment?->end_date
                ? Carbon::parse($currentAssignment->end_date)->toDateString()
                : null,
            'assignment_status' => $currentAssignment?->assignment_status,
            'scheduled_start_date' => $selectedCandidate?->scheduled_start_date?->toDateString(),
            'current_workflow_step' => $currentWorkflow['step_name'],
            'current_workflow_name' => $currentWorkflow['workflow_name'],
            'current_workflow_candidate_id' => $currentWorkflow['candidate_id'],
            'workflow_candidates' => $workflowCandidates->all(),
            'workflow_link' => 'View Workflow',
            'last_updated' => $lastUpdated?->toIso8601String(),
            'search_text' => $this->searchText($position, $staffingState, $currentPerson, $workflowCandidates),
        ];
    }

    /**
     * Determine the staffing condition independently of recruiting workflow names.
     *
     * @param Collection<int, array<string, mixed>> $candidateMetrics
     */
    private function staffingState(Position $position, Collection $candidateMetrics): string
    {
        $positionStatus = $this->normalize($position->status);

        if (Str::contains($positionStatus, ['on hold', 'hold'])) {
            return 'on_hold';
        }

        $departingAssignment = $position->assignments->first(function ($assignment): bool {
            $assignmentStatus = $this->normalize($assignment->assignment_status);

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

        $hasActiveAssignment = $position->assignments->contains(function ($assignment): bool {
            $assignmentStatus = $this->normalize($assignment->assignment_status);

            return in_array($assignmentStatus, ['active', 'filled'], true)
                && (! $assignment->end_date || Carbon::parse($assignment->end_date)->isFuture());
        });

        if ($hasActiveAssignment || Str::contains($positionStatus, ['filled'])) {
            return 'filled';
        }

        $hasSelectedCandidate = $candidateMetrics->contains(function (array $metrics): bool {
            $candidateStatus = $this->normalize($metrics['candidate_status'] ?? '');

            return in_array($candidateStatus, [
                'selected',
                'approved',
                'assigned',
                'hired',
                'onboarding',
            ], true);
        });

        $hasPlannedAssignment = $position->assignments->contains(function ($assignment): bool {
            $assignmentStatus = $this->normalize($assignment->assignment_status);

            return in_array($assignmentStatus, ['planned', 'selected'], true)
                && (! $assignment->end_date || Carbon::parse($assignment->end_date)->isFuture());
        });

        if ($hasSelectedCandidate || $hasPlannedAssignment || Str::contains($positionStatus, ['selected'])) {
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
     * Build the customer-configurable candidate workflow timeline. Nothing here
     * assumes step names such as Resume Accepted, Extra Check, or Crossover.
     *
     * @return array<string, mixed>
     */
    private function workflowCandidate(Candidate $candidate): array
    {
        $eventsByStep = $candidate->stepEvents
            ->sortByDesc(fn ($event) => $event->completed_at
                ?? $event->scheduled_at
                ?? $event->requested_at
                ?? $event->updated_at)
            ->unique('workflow_step_id')
            ->keyBy('workflow_step_id');

        $steps = ($candidate->workflow?->steps ?? collect())
            ->sortBy('step_order')
            ->map(function ($step) use ($eventsByStep): array {
                $event = $eventsByStep->get($step->id);

                return [
                    'id' => $step->id,
                    'name' => $step->name,
                    'step_order' => (int) $step->step_order,
                    'status_code' => $event?->status_code,
                    'requested_at' => $event?->requested_at?->toIso8601String(),
                    'scheduled_at' => $event?->scheduled_at?->toIso8601String(),
                    'completed_at' => $event?->completed_at?->toIso8601String(),
                    'notes' => $event?->notes,
                    'comments' => $event?->comments,
                    'has_event' => (bool) $event,
                ];
            })
            ->values();

        $metrics = $this->candidateMetrics($candidate);

        return [
            'candidate_id' => $candidate->id,
            'candidate_code' => $candidate->candidate_code,
            'candidate_status' => $candidate->status,
            'scheduled_start_date' => $candidate->scheduled_start_date?->toDateString(),
            'person' => $candidate->person ? $this->personDetails($candidate->person) : null,
            'workflow_id' => $candidate->workflow_id,
            'workflow_name' => $candidate->workflow?->name,
            'current_step' => $metrics['step_name'],
            'current_step_number' => $metrics['step_number'],
            'step_count' => $metrics['step_count'],
            'steps' => $steps->all(),
        ];
    }

    private function currentAssignment(Position $position)
    {
        return $position->assignments
            ->sortByDesc(fn ($assignment) => $assignment->start_date ?? $assignment->created_at)
            ->first(function ($assignment): bool {
                $status = $this->normalize($assignment->assignment_status);

                return ! in_array($status, ['ended', 'inactive'], true)
                    && (! $assignment->end_date || Carbon::parse($assignment->end_date)->isFuture());
            });
    }

    private function selectedCandidate(Collection $candidates): ?Candidate
    {
        return $candidates
            ->first(function (Candidate $candidate): bool {
                return in_array($this->normalize($candidate->status), [
                    'selected', 'approved', 'assigned', 'hired', 'onboarding',
                ], true);
            }) ?? $candidates->first();
    }

    /**
     * @param Collection<int, array<string,mixed>> $workflowCandidates
     * @return array{candidate_id:int|null,workflow_name:string|null,step_name:string}
     */
    private function currentWorkflow(Collection $workflowCandidates): array
    {
        $candidate = $workflowCandidates
            ->sortByDesc('current_step_number')
            ->first();

        return [
            'candidate_id' => $candidate['candidate_id'] ?? null,
            'workflow_name' => $candidate['workflow_name'] ?? null,
            'step_name' => $candidate['current_step'] ?? 'No candidate workflow',
        ];
    }

    private function lastUpdated(Position $position): ?Carbon
    {
        $timestamps = collect([$position->updated_at]);

        foreach ($position->assignments as $assignment) {
            $timestamps->push($assignment->updated_at);
        }

        foreach ($position->candidates as $candidate) {
            $timestamps->push($candidate->updated_at);

            foreach ($candidate->stepEvents as $event) {
                $timestamps->push($event->updated_at);
            }
        }

        $latest = $timestamps->filter()->sortDesc()->first();

        return $latest ? Carbon::parse($latest) : null;
    }

    private function searchText(
        Position $position,
        string $staffingState,
        $currentPerson,
        Collection $workflowCandidates,
    ): string {
        $workflowText = $workflowCandidates
            ->flatMap(function (array $candidate): array {
                return [
                    $candidate['candidate_code'] ?? null,
                    $candidate['candidate_status'] ?? null,
                    $candidate['person']['person_code'] ?? null,
                    $candidate['person']['name'] ?? null,
                    $candidate['person']['first_name'] ?? null,
                    $candidate['person']['preferred_name'] ?? null,
                    $candidate['person']['alternate_first_name'] ?? null,
                    $candidate['person']['last_name'] ?? null,
                    $candidate['person']['alternate_last_name'] ?? null,
                    $candidate['person']['company_name'] ?? null,
                    $candidate['workflow_name'] ?? null,
                    $candidate['current_step'] ?? null,
                    ...collect($candidate['steps'] ?? [])->pluck('name')->all(),
                ];
            });

        return collect([
            $position->position_code,
            $position->job_title,
            $position->title ?? null,
            $position->level,
            $position->team_name,
            $position->project_team_name,
            $position->location,
            $position->building,
            $position->status,
            $this->staffingLabel($staffingState),
            $currentPerson?->person_code,
            $currentPerson ? $this->personName($currentPerson) : null,
            $currentPerson?->first_name,
            $currentPerson?->preferred_name,
            $currentPerson?->alternate_first_name,
            $currentPerson?->last_name,
            $currentPerson?->alternate_last_name,
            $currentPerson?->company_name,
            ...$workflowText->all(),
        ])->filter(fn ($value) => $value !== null && $value !== '')
            ->implode(' ');
    }

    /** @return array<string,mixed> */
    private function personDetails(Person $person): array
    {
        return [
            'id' => $person->id,
            'person_code' => $person->person_code,
            'name' => $this->personName($person),
            'first_name' => $person->first_name,
            'alternate_first_name' => $person->alternate_first_name,
            'preferred_name' => $person->preferred_name,
            'last_name' => $person->last_name,
            'alternate_last_name' => $person->alternate_last_name,
            'company_name' => $person->company_name,
            'employment_status' => $person->employment_status,
        ];
    }

    private function personName(Person $person): string
    {
        return trim(
            ($person->preferred_name ?: $person->first_name)
            .' '
            .$person->last_name
        );
    }

    private function candidateName(Candidate $candidate): ?string
    {
        return $candidate->person ? $this->personName($candidate->person) : null;
    }

    private function normalize(mixed $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->replace(['-', '_'], ' ')
            ->squish()
            ->toString();
    }

    private function staffingLabel(string $state): string
    {
        return match ($state) {
            'on_hold' => 'On-Hold',
            default => Str::headline($state),
        };
    }

    private function staffingRank(string $state): int
    {
        return match ($state) {
            'vacant' => 10,
            'selected' => 20,
            'filled' => 30,
            'departing' => 40,
            'on_hold' => 50,
            default => 99,
        };
    }
}
