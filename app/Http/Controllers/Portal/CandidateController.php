<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;
use App\Models\Workflow;
use App\Services\PortalWorkforceAccessService;
use App\Services\UserEventLogger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CandidateController extends Controller
{
    public function __construct(
        private readonly PortalWorkforceAccessService $workforceAccess,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $sort = (string) $request->input('sort', 'created_at');
        $direction = strtolower((string) $request->input('direction', 'desc'));

        $columns = $this->getColumnDefinitions();
        $allowedSorts = collect($columns)
            ->filter(fn ($column) => $column['sortable'])
            ->pluck('key')
            ->values()
            ->all();

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $defaultColumnOrder = $this->getDefaultColumnOrder();
        $defaultVisibleColumns = $this->getDefaultVisibleColumns();

        $visibleColumns = session('portal.candidates.visible_columns', $defaultVisibleColumns);
        $columnOrder = session('portal.candidates.column_order', $defaultColumnOrder);

        $visibleColumns = $this->sanitizeColumnKeys($visibleColumns, $columns, $defaultVisibleColumns);
        $columnOrder = $this->sanitizeColumnKeys($columnOrder, $columns, $defaultColumnOrder);

        $candidates = $this->workforceAccess
            ->scopeCandidates(Candidate::query())
            ->with([
                'person:id,first_name,last_name,person_code',
                'position:id,job_title,position_code',
                'submittedBy:id,first_name,last_name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('candidate_code', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('candidate_fbr', 'like', "%{$search}%")
                        ->orWhereHas('person', function ($personQuery) use ($search) {
                            $personQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('person_code_lookup', app(\App\Services\Encryption\LookupHashService::class)->hash($search));
                        })
                        ->orWhereHas('position', function ($positionQuery) use ($search) {
                            $positionQuery->where('job_title', 'like', "%{$search}%")
                                ->orWhere('position_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('submittedBy', function ($submittedByQuery) use ($search) {
                            $submittedByQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            });

        switch ($sort) {
            case 'id':
            case 'candidate_code':
            case 'status':
            case 'candidate_fbr':
            case 'submitted_at':
            case 'scheduled_start_date':
            case 'created_at':
            case 'updated_at':
                $candidates->orderBy($sort, $direction);
                break;

            default:
                $candidates->orderBy('created_at', 'desc');
                break;
        }

        $candidates = $candidates
            ->paginate(10)
            ->withQueryString()
            ->through(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'candidate_code' => $candidate->candidate_code,
                    'status' => $candidate->status,
                    'candidate_fbr' => $candidate->candidate_fbr,
                    'submitted_at' => $candidate->submitted_at?->format('Y-m-d H:i'),
                    'scheduled_start_date' => $candidate->scheduled_start_date?->format('Y-m-d'),
                    'created_at' => $candidate->created_at?->format('Y-m-d H:i'),

                    'person' => $candidate->person ? [
                        'id' => $candidate->person->id,
                        'person_code' => $candidate->person->person_code,
                        'full_name' => trim(($candidate->person->first_name ?? '') . ' ' . ($candidate->person->last_name ?? '')),
                    ] : null,

                    'position' => $candidate->position ? [
                        'id' => $candidate->position->id,
                        'position_code' => $candidate->position->position_code,
                        'job_title' => $candidate->position->job_title,
                    ] : null,

                    'submitted_by' => $candidate->submittedBy ? [
                        'id' => $candidate->submittedBy->id,
                        'full_name' => trim(($candidate->submittedBy->first_name ?? '') . ' ' . ($candidate->submittedBy->last_name ?? '')),
                    ] : null,
                ];
            });

        return Inertia::render('Portal/Candidates/Index', [
            'candidates' => $candidates,
            'columns' => $columns,
            'visibleColumns' => $visibleColumns,
            'columnOrder' => $columnOrder,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'sort' => $sort,
            'direction' => $direction,
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $workflows = Workflow::query()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_primary']);

        if ($workflows->isEmpty()) {
            abort(500, 'No active workflows are configured.');
        }

        $primaryWorkflow = $workflows->firstWhere('is_primary', true);

        $selectedWorkflowId = $request->integer('workflow_id');

        if (!$selectedWorkflowId) {
            $selectedWorkflowId = $primaryWorkflow?->id ?? $workflows->first()->id;
        }

        $selectedWorkflow = Workflow::query()
            ->with([
                'steps' => function ($query) {
                    $query->where('is_active', true)->orderBy('step_order');
                },
                'steps.statuses' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                },
            ])
            ->where('is_active', true)
            ->find($selectedWorkflowId);

        if (!$selectedWorkflow) {
            $selectedWorkflow = Workflow::query()
                ->with([
                    'steps' => function ($query) {
                        $query->where('is_active', true)->orderBy('step_order');
                    },
                    'steps.statuses' => function ($query) {
                        $query->where('is_active', true)->orderBy('sort_order');
                    },
                ])
                ->findOrFail($primaryWorkflow?->id ?? $workflows->first()->id);
        }

        return Inertia::render('Portal/Candidates/Create', [
            'people' => $this->getPeopleOptions(),
            'positions' => $this->getPositionOptions(),
            'workflows' => $workflows->map(function ($workflow) {
                return [
                    'id' => $workflow->id,
                    'name' => $workflow->name,
                    'code' => $workflow->code,
                    'is_primary' => (bool) $workflow->is_primary,
                ];
            })->values(),
            'workflow' => [
                'id' => $selectedWorkflow->id,
                'name' => $selectedWorkflow->name,
                'code' => $selectedWorkflow->code,
                'is_primary' => (bool) $selectedWorkflow->is_primary,
            ],
            'workflowSteps' => $selectedWorkflow->steps->map(function ($step) {
                return [
                    'id' => $step->id,
                    'code' => $step->code,
                    'name' => $step->name,
                    'step_order' => $step->step_order,
                    'is_active' => (bool) $step->is_active,
                    'allows_requested_at' => (bool) $step->allows_requested_at,
                    'allows_scheduled_at' => (bool) $step->allows_scheduled_at,
                    'allows_completed_at' => (bool) $step->allows_completed_at,
                    'allows_notes' => (bool) $step->allows_notes,
                    'allows_comments' => (bool) $step->allows_comments,
                    'allows_status' => (bool) $step->allows_status,
                    'default_status' => $step->default_status,
                    'statuses' => $step->statuses->map(function ($status) {
                        return [
                            'id' => $status->id,
                            'status_code' => $status->status_code,
                            'status_label' => $status->status_label,
                            'sort_order' => $status->sort_order,
                            'is_default' => (bool) $status->is_default,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'person_id' => ['required', $this->candidatePersonRule()],
            'position_id' => ['required', 'exists:positions,id'],
            'workflow_id' => ['required', 'exists:workflows,id'],
            'status' => ['required', 'in:submitted,selected,approved,assigned'],
            'candidate_fbr' => ['nullable', 'numeric'],
            'submitted_at' => ['nullable', 'date'],
            'submitted_by_person_id' => ['nullable', 'exists:people,id'],
            'scheduled_start_date' => ['nullable', 'date'],

            'step_events' => ['nullable', 'array'],
            'step_events.*.workflow_step_id' => ['required', 'exists:workflow_steps,id'],
            'step_events.*.status_code' => ['nullable', 'string', 'max:255'],
            'step_events.*.requested_at' => ['nullable', 'date'],
            'step_events.*.scheduled_at' => ['nullable', 'date'],
            'step_events.*.completed_at' => ['nullable', 'date'],
            'step_events.*.performed_by_person_id' => ['nullable', 'exists:people,id'],
            'step_events.*.notes' => ['nullable', 'string', 'max:2500'],
            'step_events.*.comments' => ['nullable', 'string', 'max:2500'],
        ]);

        $selectedWorkflow = Workflow::query()
            ->where('id', $data['workflow_id'])
            ->where('is_active', true)
            ->first();

        if (!$selectedWorkflow) {
            throw ValidationException::withMessages([
                'workflow_id' => 'The selected workflow is not active or does not exist.',
            ]);
        }

        $allowedStepIds = $selectedWorkflow->steps()
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->validateStepEventsBelongToWorkflow($data['step_events'] ?? [], $allowedStepIds);

        $candidate = Candidate::create([
            'person_id' => $data['person_id'],
            'position_id' => $data['position_id'],
            'workflow_id' => $selectedWorkflow->id,
            'status' => $data['status'],
            'candidate_fbr' => $data['candidate_fbr'] ?? null,
            'submitted_at' => $data['submitted_at'] ?? null,
            'submitted_by_person_id' => $data['submitted_by_person_id'] ?? null,
            'scheduled_start_date' => $data['scheduled_start_date'] ?? null,
        ]);

        foreach ($data['step_events'] ?? [] as $event) {
            if (!$this->stepEventHasAnyValue($event)) {
                continue;
            }

            $candidate->stepEvents()->create([
                'workflow_step_id' => $event['workflow_step_id'],
                'status_code' => $event['status_code'] ?? null,
                'requested_at' => $event['requested_at'] ?? null,
                'scheduled_at' => $event['scheduled_at'] ?? null,
                'completed_at' => $event['completed_at'] ?? null,
                'performed_by_person_id' => $event['performed_by_person_id'] ?? null,
                'notes' => $event['notes'] ?? null,
                'comments' => $event['comments'] ?? null,
            ]);
        }

        $candidate->loadMissing(['person:id,first_name,preferred_name,last_name', 'position:id,position_code,job_title']);
        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'create',
            module: 'candidates',
            action: 'create',
            subject: $candidate,
            subjectLabel: $this->auditCandidateLabel($candidate),
            description: 'Created candidate workflow '.$this->auditCandidateLabel($candidate).'.',
            metadata: ['status' => $candidate->status, 'workflow_id' => $candidate->workflow_id],
        );

        return redirect()
            ->route('portal.candidates.index')
            ->with('success', 'Candidate created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Candidate $candidate): Response
    {
        $this->workforceAccess->authorizeCandidateView($candidate);

        $candidate->load([
            'person:id,first_name,last_name,person_code,email',
            'position:id,job_title,position_code,status',
            'workflow:id,name,code',
            'submittedBy:id,first_name,last_name',
            'stepEvents.workflowStep:id,workflow_id,code,name,step_order,allows_requested_at,allows_scheduled_at,allows_completed_at,allows_notes,allows_comments,allows_status,default_status',
            'stepEvents.performedBy:id,first_name,last_name',
            'workflow.steps' => function ($query) {
                $query->orderBy('step_order');
            },
        ]);

        if (!$candidate->workflow) {
            abort(500, 'This candidate does not have an associated workflow.');
        }

        $stepEventsByWorkflowStepId = $candidate->stepEvents->keyBy('workflow_step_id');

        $workflowDisplay = $candidate->workflow->steps->map(function ($step) use ($stepEventsByWorkflowStepId) {
            $event = $stepEventsByWorkflowStepId->get($step->id);

            return [
                'workflow_step_id' => $step->id,
                'status_code' => $event?->status_code,
                'requested_at' => $event?->requested_at,
                'scheduled_at' => $event?->scheduled_at,
                'completed_at' => $event?->completed_at,
                'performed_by_person_id' => $event?->performed_by_person_id,
                'notes' => $event?->notes,
                'comments' => $event?->comments,
                'workflow_step' => [
                    'id' => $step->id,
                    'code' => $step->code,
                    'name' => $step->name,
                    'step_order' => $step->step_order,
                    'allows_requested_at' => (bool) $step->allows_requested_at,
                    'allows_scheduled_at' => (bool) $step->allows_scheduled_at,
                    'allows_completed_at' => (bool) $step->allows_completed_at,
                    'allows_notes' => (bool) $step->allows_notes,
                    'allows_comments' => (bool) $step->allows_comments,
                    'allows_status' => (bool) $step->allows_status,
                    'default_status' => $step->default_status,
                ],
                'performed_by' => $event?->performedBy ? [
                    'id' => $event->performedBy->id,
                    'full_name' => trim(($event->performedBy->first_name ?? '') . ' ' . ($event->performedBy->last_name ?? '')),
                ] : null,
                'has_event' => (bool) $event,
            ];
        })->values();

        $candidateData = [
            'id' => $candidate->id,
            'candidate_code' => $candidate->candidate_code,
            'workflow_id' => $candidate->workflow_id,
            'status' => $candidate->status,
            'candidate_fbr' => $candidate->candidate_fbr,
            'submitted_at' => $candidate->submitted_at,
            'submitted_by_person_id' => $candidate->submitted_by_person_id,
            'scheduled_start_date' => $candidate->scheduled_start_date,

            'workflow' => [
                'id' => $candidate->workflow->id,
                'name' => $candidate->workflow->name,
                'code' => $candidate->workflow->code,
            ],

            'person' => $candidate->person ? [
                'id' => $candidate->person->id,
                'person_code' => $candidate->person->person_code,
                'first_name' => $candidate->person->first_name,
                'last_name' => $candidate->person->last_name,
                'full_name' => trim(($candidate->person->first_name ?? '') . ' ' . ($candidate->person->last_name ?? '')),
                'email' => $candidate->person->email,
            ] : null,

            'position' => $candidate->position ? [
                'id' => $candidate->position->id,
                'position_code' => $candidate->position->position_code,
                'job_title' => $candidate->position->job_title,
                'status' => $candidate->position->status,
            ] : null,

            'submitted_by' => $candidate->submittedBy ? [
                'id' => $candidate->submittedBy->id,
                'full_name' => trim(($candidate->submittedBy->first_name ?? '') . ' ' . ($candidate->submittedBy->last_name ?? '')),
            ] : null,

            'step_events' => $workflowDisplay,
        ];

        return Inertia::render('Portal/Candidates/Show', [
            'candidate' => $candidateData,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Candidate $candidate): Response
    {
        $candidate->load([
            'workflow.steps' => function ($query) {
                $query->orderBy('step_order');
            },
            'workflow.steps.statuses' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'stepEvents',
        ]);

        if (!$candidate->workflow) {
            abort(500, 'This candidate does not have an associated workflow.');
        }

        $candidateData = [
            'id' => $candidate->id,
            'candidate_code' => $candidate->candidate_code,
            'person_id' => $candidate->person_id,
            'position_id' => $candidate->position_id,
            'workflow_id' => $candidate->workflow_id,
            'status' => $candidate->status,
            'candidate_fbr' => $candidate->candidate_fbr,
            'submitted_at' => $candidate->submitted_at?->format('Y-m-d\TH:i'),
            'submitted_by_person_id' => $candidate->submitted_by_person_id,
            'scheduled_start_date' => $candidate->scheduled_start_date?->format('Y-m-d'),
            'step_events' => $candidate->stepEvents->map(function ($event) {
                return [
                    'id' => $event->id,
                    'workflow_step_id' => $event->workflow_step_id,
                    'status_code' => $event->status_code,
                    'requested_at' => $event->requested_at?->format('Y-m-d\TH:i'),
                    'scheduled_at' => $event->scheduled_at?->format('Y-m-d\TH:i'),
                    'completed_at' => $event->completed_at?->format('Y-m-d\TH:i'),
                    'performed_by_person_id' => $event->performed_by_person_id,
                    'notes' => $event->notes,
                    'comments' => $event->comments,
                ];
            })->values(),
        ];

        return Inertia::render('Portal/Candidates/Edit', [
            'candidate' => $candidateData,
            'people' => $this->getPeopleOptions(),
            'positions' => $this->getPositionOptions(),
            'workflow' => [
                'id' => $candidate->workflow->id,
                'name' => $candidate->workflow->name,
                'code' => $candidate->workflow->code,
            ],
            'workflowSteps' => $candidate->workflow->steps->map(function ($step) {
                return [
                    'id' => $step->id,
                    'code' => $step->code,
                    'name' => $step->name,
                    'step_order' => $step->step_order,
                    'is_active' => (bool) $step->is_active,
                    'allows_requested_at' => (bool) $step->allows_requested_at,
                    'allows_scheduled_at' => (bool) $step->allows_scheduled_at,
                    'allows_completed_at' => (bool) $step->allows_completed_at,
                    'allows_notes' => (bool) $step->allows_notes,
                    'allows_comments' => (bool) $step->allows_comments,
                    'allows_status' => (bool) $step->allows_status,
                    'default_status' => $step->default_status,
                    'statuses' => $step->statuses->map(function ($status) {
                        return [
                            'id' => $status->id,
                            'status_code' => $status->status_code,
                            'status_label' => $status->status_label,
                            'sort_order' => $status->sort_order,
                            'is_default' => (bool) $status->is_default,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $candidate->load('workflow.steps');
        $before = $candidate->only([
            'person_id', 'position_id', 'workflow_id', 'status',
            'candidate_fbr', 'submitted_at', 'scheduled_start_date',
        ]);
        $beforeStepCount = $candidate->stepEvents()->count();

        if (!$candidate->workflow) {
            return back()->withErrors([
                'workflow' => 'This candidate does not have an associated workflow.',
            ]);
        }

        $data = $request->validate([
            'person_id' => ['required', $this->candidatePersonRule()],
            'position_id' => ['required', 'exists:positions,id'],
            'status' => ['required', 'in:submitted,selected,approved,assigned'],
            'candidate_fbr' => ['nullable', 'numeric'],
            'submitted_at' => ['nullable', 'date'],
            'submitted_by_person_id' => ['nullable', 'exists:people,id'],
            'scheduled_start_date' => ['nullable', 'date'],

            'step_events' => ['nullable', 'array'],
            'step_events.*.workflow_step_id' => ['required', 'exists:workflow_steps,id'],
            'step_events.*.status_code' => ['nullable', 'string', 'max:255'],
            'step_events.*.requested_at' => ['nullable', 'date'],
            'step_events.*.scheduled_at' => ['nullable', 'date'],
            'step_events.*.completed_at' => ['nullable', 'date'],
            'step_events.*.performed_by_person_id' => ['nullable', 'exists:people,id'],
            'step_events.*.notes' => ['nullable', 'string', 'max:2500'],
            'step_events.*.comments' => ['nullable', 'string', 'max:2500'],
        ]);

        $allowedStepIds = $candidate->workflow->steps->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->validateStepEventsBelongToWorkflow($data['step_events'] ?? [], $allowedStepIds);

        $candidate->update([
            'person_id' => $data['person_id'],
            'position_id' => $data['position_id'],
            'status' => $data['status'],
            'candidate_fbr' => $data['candidate_fbr'] ?? null,
            'submitted_at' => $data['submitted_at'] ?? null,
            'submitted_by_person_id' => $data['submitted_by_person_id'] ?? null,
            'scheduled_start_date' => $data['scheduled_start_date'] ?? null,
        ]);

        $candidate->stepEvents()->delete();

        foreach ($data['step_events'] ?? [] as $event) {
            if (!$this->stepEventHasAnyValue($event)) {
                continue;
            }

            $candidate->stepEvents()->create([
                'workflow_step_id' => $event['workflow_step_id'],
                'status_code' => $event['status_code'] ?? null,
                'requested_at' => $event['requested_at'] ?? null,
                'scheduled_at' => $event['scheduled_at'] ?? null,
                'completed_at' => $event['completed_at'] ?? null,
                'performed_by_person_id' => $event['performed_by_person_id'] ?? null,
                'notes' => $event['notes'] ?? null,
                'comments' => $event['comments'] ?? null,
            ]);
        }

        $candidate->refresh()->loadMissing(['person:id,first_name,preferred_name,last_name', 'position:id,position_code,job_title']);
        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'update',
            module: 'candidates',
            action: 'update_workflow',
            subject: $candidate,
            subjectLabel: $this->auditCandidateLabel($candidate),
            description: 'Updated candidate workflow '.$this->auditCandidateLabel($candidate).'.',
            before: $before,
            after: $candidate->only(array_keys($before)),
            metadata: [
                'workflow_step_events_before' => $beforeStepCount,
                'workflow_step_events_after' => $candidate->stepEvents()->count(),
            ],
        );

        return redirect()
            ->route('portal.candidates.index')
            ->with('success', 'Candidate updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Candidate $candidate): RedirectResponse
    {
        $candidate->loadMissing(['person:id,first_name,preferred_name,last_name', 'position:id,position_code,job_title']);
        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'delete',
            module: 'candidates',
            action: 'delete',
            subject: $candidate,
            subjectLabel: $this->auditCandidateLabel($candidate),
            description: 'Deleted candidate workflow '.$this->auditCandidateLabel($candidate).'.',
            metadata: ['deleted' => true],
        );

        $candidate->delete();

        return redirect()
            ->route('portal.candidates.index')
            ->with('success', 'Candidate deleted successfully.');
    }

    /**
     * Save candidate index column preferences.
     */
    public function savePreferences(Request $request): RedirectResponse
    {
        $columns = $this->getColumnDefinitions();
        $defaultVisibleColumns = $this->getDefaultVisibleColumns();
        $defaultColumnOrder = $this->getDefaultColumnOrder();

        $data = $request->validate([
            'visible_columns' => ['nullable', 'array'],
            'visible_columns.*' => ['string'],
            'column_order' => ['nullable', 'array'],
            'column_order.*' => ['string'],
        ]);

        $visibleColumns = $this->sanitizeColumnKeys(
            $data['visible_columns'] ?? [],
            $columns,
            $defaultVisibleColumns
        );

        $columnOrder = $this->sanitizeColumnKeys(
            $data['column_order'] ?? [],
            $columns,
            $defaultColumnOrder
        );

        session([
            'portal.candidates.visible_columns' => $visibleColumns,
            'portal.candidates.column_order' => $columnOrder,
        ]);

        return back()->with('success', 'Candidate column preferences saved.');
    }

    /**
     * Reset candidate index column preferences.
     */
    public function resetPreferences(): RedirectResponse
    {
        session()->forget([
            'portal.candidates.visible_columns',
            'portal.candidates.column_order',
        ]);

        return back()->with('success', 'Candidate column preferences reset.');
    }

    /**
     * Export candidates to CSV.
     */
    public function exportCsv(Request $request)
    {
        app(UserEventLogger::class)->record(
            eventType: 'export', module: 'candidates', action: 'export',
            description: 'Exported candidates to CSV.',
            metadata: ['format' => 'csv', 'filters' => $request->only(['search', 'status', 'sort', 'direction'])],
        );

        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));

        $columns = $this->getColumnDefinitions();
        $defaultVisibleColumns = $this->getDefaultVisibleColumns();
        $defaultColumnOrder = $this->getDefaultColumnOrder();

        $visibleColumns = $this->sanitizeColumnKeys(
            $request->input('visible_columns', $defaultVisibleColumns),
            $columns,
            $defaultVisibleColumns
        );

        $columnOrder = $this->sanitizeColumnKeys(
            $request->input('column_order', $defaultColumnOrder),
            $columns,
            $defaultColumnOrder
        );

        $activeColumnKeys = array_values(array_filter(
            $columnOrder,
            fn ($key) => in_array($key, $visibleColumns, true)
        ));

        $columnLabels = collect($columns)->keyBy('key');

        $rows = Candidate::query()
            ->with([
                'person:id,first_name,last_name,person_code',
                'position:id,job_title,position_code',
                'submittedBy:id,first_name,last_name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('candidate_code', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('candidate_fbr', 'like', "%{$search}%")
                        ->orWhereHas('person', function ($personQuery) use ($search) {
                            $personQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('person_code_lookup', app(\App\Services\Encryption\LookupHashService::class)->hash($search));
                        })
                        ->orWhereHas('position', function ($positionQuery) use ($search) {
                            $positionQuery->where('job_title', 'like', "%{$search}%")
                                ->orWhere('position_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('submittedBy', function ($submittedByQuery) use ($search) {
                            $submittedByQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'candidates-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows, $activeColumnKeys, $columnLabels) {
            $file = fopen('php://output', 'w');

            fputcsv($file, collect($activeColumnKeys)
                ->map(fn ($key) => $columnLabels[$key]['label'] ?? $key)
                ->all());

            foreach ($rows as $candidate) {
                $mapped = [
                    'id' => $candidate->id,
                    'candidate_code' => $candidate->candidate_code,
                    'status' => $candidate->status,
                    'person_name' => trim(($candidate->person->first_name ?? '') . ' ' . ($candidate->person->last_name ?? '')),
                    'person_code' => $candidate->person->person_code ?? '',
                    'position_title' => $candidate->position->job_title ?? '',
                    'position_code' => $candidate->position->position_code ?? '',
                    'candidate_fbr' => $candidate->candidate_fbr,
                    'submitted_at' => $candidate->submitted_at?->format('Y-m-d H:i'),
                    'submitted_by' => trim(($candidate->submittedBy->first_name ?? '') . ' ' . ($candidate->submittedBy->last_name ?? '')),
                    'scheduled_start_date' => $candidate->scheduled_start_date?->format('Y-m-d'),
                    'created_at' => $candidate->created_at?->format('Y-m-d H:i'),
                ];

                $row = [];
                foreach ($activeColumnKeys as $key) {
                    $row[] = $mapped[$key] ?? '';
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return ResponseFacade::stream($callback, 200, $headers);
    }

    /**
     * Build the people select options.
     */
    private function getPeopleOptions()
    {
        return Person::query()
            ->whereHas('user.roles', function ($query) {
                $query->where('roles.name', 'candidate');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get([
                'id',
                'person_code',
                'first_name',
                'last_name',
                'email',
            ])
            ->map(function ($person) {
                return [
                    'id' => $person->id,
                    'person_code' => $person->person_code,
                    'first_name' => $person->first_name,
                    'last_name' => $person->last_name,
                    'full_name' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                    'email' => $person->email,
                ];
            })
            ->values();
    }

    /**
     * Require the selected person to be linked to a user with the candidate role.
     */
    private function candidatePersonRule()
    {
        return Rule::exists('people', 'id')->where(function ($query) {
            $query->whereIn('user_id', function ($userQuery) {
                $userQuery
                    ->select('users.id')
                    ->from('users')
                    ->join('role_user', 'role_user.user_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->where('roles.name', 'candidate');
            });
        });
    }

    /**
     * Build the position select options.
     */
    private function getPositionOptions()
    {
        return Position::query()
            ->orderBy('job_title')
            ->get([
                'id',
                'position_code',
                'job_title',
                'status',
            ])
            ->map(function ($position) {
                return [
                    'id' => $position->id,
                    'position_code' => $position->position_code,
                    'job_title' => $position->job_title,
                    'status' => $position->status,
                ];
            })
            ->values();
    }

    /**
     * Candidate status options.
     */
    private function getStatusOptions(): array
    {
        return [
            ['value' => 'submitted', 'label' => 'Submitted'],
            ['value' => 'selected', 'label' => 'Selected'],
            ['value' => 'approved', 'label' => 'Approved'],
            ['value' => 'assigned', 'label' => 'Assigned'],
        ];
    }

    /**
     * Candidate index column definitions.
     */
    private function getColumnDefinitions(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'candidate_code', 'label' => 'Candidate Code', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'person_name', 'label' => 'Person', 'sortable' => false],
            ['key' => 'person_code', 'label' => 'Person Code', 'sortable' => false],
            ['key' => 'position_title', 'label' => 'Position', 'sortable' => false],
            ['key' => 'position_code', 'label' => 'Position Code', 'sortable' => false],
            ['key' => 'candidate_fbr', 'label' => 'FBR', 'sortable' => true],
            ['key' => 'submitted_at', 'label' => 'Submitted At', 'sortable' => true],
            ['key' => 'submitted_by', 'label' => 'Submitted By', 'sortable' => false],
            ['key' => 'scheduled_start_date', 'label' => 'Scheduled Start', 'sortable' => true],
            ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
        ];
    }

    /**
     * Default visible columns.
     */
    private function getDefaultVisibleColumns(): array
    {
        return [
            'id',
            'candidate_code',
            'status',
            'person_name',
            'position_title',
            'candidate_fbr',
            'submitted_at',
            'scheduled_start_date',
        ];
    }

    /**
     * Default column order.
     */
    private function getDefaultColumnOrder(): array
    {
        return [
            'id',
            'candidate_code',
            'status',
            'person_name',
            'person_code',
            'position_title',
            'position_code',
            'candidate_fbr',
            'submitted_at',
            'submitted_by',
            'scheduled_start_date',
            'created_at',
        ];
    }

    /**
     * Clean column keys against allowed definitions.
     */
    private function sanitizeColumnKeys(array $requestedKeys, array $columns, array $fallback): array
    {
        $allowedKeys = collect($columns)->pluck('key')->all();

        $cleaned = collect($requestedKeys)
            ->filter(fn ($key) => in_array($key, $allowedKeys, true))
            ->unique()
            ->values()
            ->all();

        if (empty($cleaned)) {
            return $fallback;
        }

        foreach ($allowedKeys as $allowedKey) {
            if (!in_array($allowedKey, $cleaned, true)) {
                $cleaned[] = $allowedKey;
            }
        }

        return $cleaned;
    }

    /**
     * Determine if a step event contains any meaningful data.
     */
    private function stepEventHasAnyValue(array $event): bool
    {
        return
            !empty($event['status_code']) ||
            !empty($event['requested_at']) ||
            !empty($event['scheduled_at']) ||
            !empty($event['completed_at']) ||
            !empty($event['performed_by_person_id']) ||
            !empty(trim((string) ($event['notes'] ?? ''))) ||
            !empty(trim((string) ($event['comments'] ?? '')));
    }

    /**
     * Ensure submitted step events belong to the expected workflow.
     */
    private function validateStepEventsBelongToWorkflow(array $stepEvents, array $allowedStepIds): void
    {
        $allowedStepIds = array_map('intval', $allowedStepIds);
        $errors = [];

        foreach ($stepEvents as $index => $event) {
            $workflowStepId = isset($event['workflow_step_id'])
                ? (int) $event['workflow_step_id']
                : null;

            if (!$workflowStepId) {
                continue;
            }

            if (!in_array($workflowStepId, $allowedStepIds, true)) {
                $errors["step_events.{$index}.workflow_step_id"] = 'This workflow step does not belong to the assigned workflow.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
    private function auditCandidateLabel(Candidate $candidate): string
    {
        $name = $candidate->person
            ? trim(($candidate->person->preferred_name ?: $candidate->person->first_name ?: '').' '.($candidate->person->last_name ?: ''))
            : '';
        $position = $candidate->position?->position_code ?: $candidate->position?->job_title;
        $parts = array_values(array_filter([$name, $position]));

        return $parts !== []
            ? implode(' — ', $parts).' Workflow'
            : ($candidate->candidate_code ?: 'Candidate '.$candidate->id);
    }

}