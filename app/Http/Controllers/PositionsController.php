<?php

namespace App\Http\Controllers;

use App\Models\JobTitle;
use App\Models\Organization;
use App\Models\Position;
use App\Models\PositionActivity;
use App\Models\Person;
use App\Models\Workflow;
use App\Models\User;
use App\Models\UserListPreference;
use App\Services\ListEngine;
use App\Services\ListExportService;
use App\Services\UserResolver;
use App\Support\ListDefinitions\PositionsDefinition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PositionsController extends Controller
{
    public function index(
        Request $request,
        UserResolver $userResolver,
        ListEngine $listEngine
    ) {
        $definition = PositionsDefinition::get();

        $list = $listEngine->run(
            request: $request,
            definition: $definition,
            userId: $userResolver->resolveUserId(),
            query: Position::query(),
            filterCallback: function ($query, $request) {
                if ($request->filled('status')) {
                    $query->where('status', $request->input('status'));
                }
            }
        );

        $list['filters']['status'] = $request->input('status', '');

        return inertia('Positions/Index', [
            'positions' => $list['rows'],
            'columns' => $list['columns'],
            'visibleColumns' => $list['visibleColumns'],
            'columnOrder' => $list['columnOrder'],
            'filters' => $list['filters'],
            'sort' => $list['sort'],
            'direction' => $list['direction'],
        ]);
    }

    public function create()
    {
        $organizations = Organization::orderBy('full_path')
            ->get([
                'id',
                'name',
                'full_path',
                'depth',
            ]);

        $jobTitles = JobTitle::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $projectManagers = $this->projectManagers();

        return Inertia::render('Positions/Create', [
            'organizations' => $organizations,
            'jobTitles' => $jobTitles,
            'projectManagers' => $projectManagers,
        ]);
    }

    public function store(
        Request $request,
        UserResolver $userResolver
    ) {
        $validated = $this->validatePosition($request);

        $position = Position::create($validated);

        PositionActivity::create([
            'position_id' => $position->id,
            'user_id' => $userResolver->resolveUserId(),
            'action' => 'created',
            'description' => 'Position created.',
        ]);

        return redirect()
            ->route('positions.edit', $position->id)
            ->with(
                'success',
                'Position created successfully. You may now add custom skills and tasks.'
            );
    }

    public function show($id)
    {
        $position = Position::with([
            'jobTitle.skills',
            'jobTitle.tasks',
            'customSkills',
            'customTasks',
            'currentAssignment.person',
            'assignments.person',
            'activities.user',
            'positionOrganization',
            'sponsoringOrganization',
            'fundingOrganization',
        ])->findOrFail($id);

        $jobTitleSkills = $position->jobTitle?->skills ?? collect();
        $jobTitleTasks = $position->jobTitle?->tasks ?? collect();
        $customSkills = $position->customSkills ?? collect();
        $customTasks = $position->customTasks ?? collect();

        /*
        |--------------------------------------------------------------------------
        | Prevent jobTitle Relationship Serialization Conflict
        |--------------------------------------------------------------------------
        |
        | Laravel serializes jobTitle() as job_title, which conflicts with the
        | positions.job_title text column. We send skills/tasks separately.
        |
        */

        $position->unsetRelation('jobTitle');

        return inertia('Positions/Show', [
            'position' => $position,
            'jobTitleSkills' => $jobTitleSkills,
            'jobTitleTasks' => $jobTitleTasks,
            'customSkills' => $customSkills,
            'customTasks' => $customTasks,
        ]);
    }

    public function edit($id)
    {
        $position = Position::with([
            'jobTitle.skills',
            'jobTitle.tasks',
            'customSkills',
            'customTasks',
            'candidates.person.primaryPhoneNumber',
            'candidates.workflow.steps',
            'candidates.stepEvents.workflowStep',
        ])->findOrFail($id);

        $jobTitleSkills = $position->jobTitle?->skills ?? collect();
        $jobTitleTasks = $position->jobTitle?->tasks ?? collect();

        /*
        |--------------------------------------------------------------------------
        | Prevent jobTitle Relationship Serialization Conflict
        |--------------------------------------------------------------------------
        */

        $position->unsetRelation('jobTitle');

        $organizations = Organization::orderBy('full_path')
            ->get([
                'id',
                'name',
                'full_path',
                'depth',
            ]);

        $jobTitles = JobTitle::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $projectManagers = $this->projectManagers();

        $candidatePersonIds = $position->candidates
            ->pluck('person_id')
            ->filter()
            ->values();

        $candidateOptions = Person::query()
            ->with('primaryPhoneNumber:id,person_id,phone_number,extension')
            ->when($candidatePersonIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $candidatePersonIds))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'preferred_name', 'last_name', 'email', 'person_code'])
            ->map(fn (Person $person) => [
                'id' => $person->id,
                'full_name' => trim(($person->preferred_name ?: $person->first_name).' '.$person->last_name),
                'email' => $person->email,
                'person_code' => $person->person_code,
                'primary_phone' => $person->primaryPhoneNumber?->phone_number,
                'primary_phone_extension' => $person->primaryPhoneNumber?->extension,
            ])
            ->values();

        $workflows = Workflow::query()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_primary'])
            ->map(fn (Workflow $workflow) => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'code' => $workflow->code,
                'is_primary' => (bool) $workflow->is_primary,
            ])
            ->values();

        $positionCandidates = $position->candidates
            ->map(function ($candidate) {
                $currentEvent = $candidate->stepEvents
                    ->sortByDesc(fn ($event) => $event->completed_at ?? $event->scheduled_at ?? $event->requested_at ?? $event->updated_at)
                    ->first();

                $firstStep = $candidate->workflow?->steps
                    ?->sortBy('step_order')
                    ->first();

                $workflowStep = $currentEvent?->workflowStep ?? $firstStep;

                return [
                    'id' => $candidate->id,
                    'status' => $candidate->status,
                    'submitted_at' => $candidate->submitted_at?->toIso8601String(),
                    'person' => $candidate->person ? [
                        'id' => $candidate->person->id,
                        'full_name' => trim(($candidate->person->preferred_name ?: $candidate->person->first_name).' '.$candidate->person->last_name),
                        'email' => $candidate->person->email,
                        'primary_phone' => $candidate->person->primaryPhoneNumber?->phone_number,
                        'primary_phone_extension' => $candidate->person->primaryPhoneNumber?->extension,
                    ] : null,
                    'workflow' => $candidate->workflow ? [
                        'id' => $candidate->workflow->id,
                        'name' => $candidate->workflow->name,
                        'step_name' => $workflowStep?->name ?? 'Not started',
                        'step_number' => $workflowStep?->step_order,
                        'step_count' => $candidate->workflow->steps?->count() ?? 0,
                        'status_code' => $currentEvent?->status_code,
                    ] : null,
                ];
            })
            ->values();

        return inertia('Positions/Edit', [
            'position' => $position,
            'organizations' => $organizations,
            'jobTitles' => $jobTitles,
            'projectManagers' => $projectManagers,
            'jobTitleSkills' => $jobTitleSkills,
            'jobTitleTasks' => $jobTitleTasks,
            'candidateOptions' => $candidateOptions,
            'positionCandidates' => $positionCandidates,
            'workflows' => $workflows,
            'initialSection' => request()->query('section', 'general'),
        ]);
    }

    public function update(
        Request $request,
        UserResolver $userResolver,
        $id
    ) {
        $position = Position::findOrFail($id);

        $original = $position->getOriginal();

        $validated = $this->validatePosition($request);

        $position->update($validated);

        $ignoredActivityFields = [
            'customer_created_at',
        ];

        foreach ($validated as $field => $newValue) {
            if (in_array($field, $ignoredActivityFields)) {
                continue;
            }

            $oldValue = $original[$field] ?? null;

            if ((string) $oldValue !== (string) $newValue) {
                PositionActivity::create([
                    'position_id' => $position->id,
                    'user_id' => $userResolver->resolveUserId(),
                    'action' => 'updated',
                    'field_name' => $field,
                    'old_value' => is_array($oldValue)
                        ? json_encode($oldValue)
                        : $oldValue,
                    'new_value' => is_array($newValue)
                        ? json_encode($newValue)
                        : $newValue,
                    'description' => "Updated {$field}.",
                ]);
            }
        }

        return redirect()
            ->route('positions.edit', $position->id)
            ->with('success', 'Position updated successfully.');
    }

    public function destroy(
        UserResolver $userResolver,
        $id
    ) {
        $position = Position::with('assignments')
            ->findOrFail($id);

        if ($position->assignments()->exists()) {
            return redirect()
                ->route('positions.index')
                ->with(
                    'error',
                    'This position cannot be deleted because it has assignments.'
                );
        }

        PositionActivity::create([
            'position_id' => $position->id,
            'user_id' => $userResolver->resolveUserId(),
            'action' => 'deleted',
            'description' => 'Position deleted.',
        ]);

        $position->delete();

        return redirect()
            ->route('positions.index')
            ->with('success', 'Position deleted successfully.');
    }

    public function savePreferences(
        Request $request,
        UserResolver $userResolver
    ) {
        $definition = PositionsDefinition::get();

        $validKeys = collect($definition['columns'])
            ->pluck('key')
            ->toArray();

        $validated = $request->validate([
            'visible_columns' => [
                'required',
                'array',
            ],
            'visible_columns.*' => [
                'string',
            ],
            'column_order' => [
                'required',
                'array',
            ],
            'column_order.*' => [
                'string',
            ],
        ]);

        $visibleColumns = collect($validated['visible_columns'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $columnOrder = collect($validated['column_order'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        UserListPreference::updateOrCreate(
            [
                'user_id' => $userResolver->resolveUserId(),
                'list_key' => $definition['list_key'],
            ],
            [
                'visible_columns' => $visibleColumns,
                'column_order' => $columnOrder,
            ]
        );

        return redirect()
            ->route('positions.index')
            ->with('success', 'Column preferences saved.');
    }

    public function resetPreferences(UserResolver $userResolver)
    {
        $definition = PositionsDefinition::get();

        UserListPreference::where('user_id', $userResolver->resolveUserId())
            ->where('list_key', $definition['list_key'])
            ->delete();

        return redirect()
            ->route('positions.index')
            ->with('success', 'Column preferences reset to defaults.');
    }

    public function exportCsv(
        Request $request,
        ListExportService $listExportService
    ): StreamedResponse {
        return $listExportService->exportCsv(
            request: $request,
            definition: PositionsDefinition::get(),
            query: Position::query(),
            filenamePrefix: 'positions-export',
            filterCallback: function ($query, $request) {
                if ($request->filled('status')) {
                    $query->where('status', $request->input('status'));
                }
            }
        );
    }

    private function validatePosition(Request $request): array
    {
        $validated = $request->validate([
            'position_code' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'in:Open,In Process,Closed',
            ],

            'job_title_id' => [
                'required',
                'exists:job_titles,id',
            ],

            'level' => [
                'nullable',
                'integer',
                'between:1,5',
            ],

            'team_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'project_manager_user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'certifications_required' => [
                'nullable',
                'string',
            ],

            'training_required' => [
                'nullable',
                'string',
            ],

            'experience' => [
                'nullable',
                'string',
            ],

            'is_essential' => [
                'boolean',
            ],

            'travel_required' => [
                'boolean',
            ],

            'high_risk_role' => [
                'boolean',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'building' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mission_description' => [
                'nullable',
                'string',
            ],

            'component' => [
                'nullable',
                'string',
                'max:255',
            ],

            'position_organization_id' => [
                'nullable',
                'exists:organizations,id',
            ],

            'sponsoring_organization_id' => [
                'nullable',
                'exists:organizations,id',
            ],

            'funding_organization_id' => [
                'nullable',
                'exists:organizations,id',
            ],

            'funding_info' => [
                'nullable',
                'string',
            ],

            'request_to_close' => [
                'boolean',
            ],

            'scheduled_to_close' => [
                'nullable',
                'date',
            ],

            'close_date' => [
                'nullable',
                'date',
                'required_if:status,Closed',
            ],

            'close_reason' => [
                'nullable',
                'string',
                'required_if:status,Closed',
            ],

            'project_team_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'customer_lead_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'customer_created_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        if (! empty($validated['project_manager_user_id'])) {
            $isProjectManager = User::query()
                ->whereKey($validated['project_manager_user_id'])
                ->whereHas('roles', fn ($query) => $query->where('roles.name', 'project_manager'))
                ->exists();

            if (! $isProjectManager) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'project_manager_user_id' => 'The selected user must have the Project Manager role.',
                ]);
            }
        }

        return $validated;
    }

    private function projectManagers()
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('roles.name', 'project_manager'))
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
            ]);
    }
}