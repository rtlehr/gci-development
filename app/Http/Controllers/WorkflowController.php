<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Services\UserEventLogger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'name');
        $direction = strtolower((string) $request->input('direction', 'asc'));

        $columns = $this->getColumnDefinitions();
        $allowedSorts = collect($columns)
            ->filter(fn ($column) => $column['sortable'])
            ->pluck('key')
            ->values()
            ->all();

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $defaultColumnOrder = $this->getDefaultColumnOrder();
        $defaultVisibleColumns = $this->getDefaultVisibleColumns();

        $visibleColumns = session('workflows.visible_columns', $defaultVisibleColumns);
        $columnOrder = session('workflows.column_order', $defaultColumnOrder);

        $visibleColumns = $this->sanitizeColumnKeys($visibleColumns, $columns, $defaultVisibleColumns);
        $columnOrder = $this->sanitizeColumnKeys($columnOrder, $columns, $defaultColumnOrder);

        $workflowsQuery = Workflow::query()
            ->withCount('steps')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });

        switch ($sort) {
            case 'id':
            case 'name':
            case 'code':
            case 'description':
            case 'is_primary':
            case 'is_active':
            case 'created_at':
            case 'updated_at':
                $workflowsQuery->orderBy($sort, $direction);
                break;

            case 'step_count':
                $workflowsQuery->orderBy('steps_count', $direction);
                break;

            default:
                $workflowsQuery->orderBy('name', 'asc');
                break;
        }

        $workflows = $workflowsQuery
            ->paginate(10)
            ->withQueryString()
            ->through(function ($workflow) {
                return [
                    'id' => $workflow->id,
                    'name' => $workflow->name,
                    'code' => $workflow->code,
                    'description' => $workflow->description,
                    'is_primary' => (bool) $workflow->is_primary,
                    'is_active' => (bool) $workflow->is_active,
                    'step_count' => $workflow->steps_count,
                    'created_at' => $workflow->created_at?->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Workflows/Index', [
            'workflows' => $workflows,
            'columns' => $columns,
            'visibleColumns' => $visibleColumns,
            'columnOrder' => $columnOrder,
            'filters' => [
                'search' => $search,
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Workflows/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateWorkflow($request);

        $workflow = DB::transaction(function () use ($data) {
            if (!empty($data['is_primary'])) {
                Workflow::query()->update(['is_primary' => false]);
                $data['is_active'] = true;
            }

            $workflow = Workflow::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_primary' => (bool) ($data['is_primary'] ?? false),
            ]);

            $this->syncWorkflowSteps($workflow, $data['steps'] ?? []);

            return $workflow;
        });

        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'create', module: 'workflows', action: 'create',
            subject: $workflow,
            description: 'Created workflow '.$workflow->name.'.',
            metadata: ['step_count' => $workflow->steps()->count()],
        );

        return redirect()
            ->route('workflows.index')
            ->with('success', 'Workflow created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Workflow $workflow): Response
    {
        $workflow->load([
            'steps' => function ($query) {
                $query->orderBy('step_order');
            },
            'steps.statuses' => function ($query) {
                $query->orderBy('sort_order');
            },
        ]);

        return Inertia::render('Workflows/Edit', [
            'workflow' => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'code' => $workflow->code,
                'description' => $workflow->description,
                'is_active' => (bool) $workflow->is_active,
                'is_primary' => (bool) $workflow->is_primary,
                'steps' => $workflow->steps->map(function ($step) {
                    return [
                        'id' => $step->id,
                        'name' => $step->name,
                        'code' => $step->code,
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
                                'is_default' => (bool) $status->is_default,
                                'is_active' => (bool) $status->is_active,
                                'sort_order' => $status->sort_order,
                            ];
                        })->values(),
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Workflow $workflow): RedirectResponse
    {
        $before = $workflow->only(['name', 'code', 'description', 'is_active', 'is_primary']);
        $beforeStepCount = $workflow->steps()->count();
        $data = $this->validateWorkflow($request, $workflow->id);

        DB::transaction(function () use ($workflow, $data) {
            if (!empty($data['is_primary'])) {
                Workflow::query()
                    ->where('id', '!=', $workflow->id)
                    ->update(['is_primary' => false]);

                $data['is_active'] = true;
            }

            $workflow->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_primary' => (bool) ($data['is_primary'] ?? false),
            ]);

            $this->syncWorkflowSteps($workflow, $data['steps'] ?? []);
        });

        $workflow->refresh();
        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'update', module: 'workflows', action: 'update',
            subject: $workflow,
            description: 'Updated workflow '.$workflow->name.'.',
            before: $before,
            after: $workflow->only(array_keys($before)),
            metadata: ['step_count_before' => $beforeStepCount, 'step_count_after' => $workflow->steps()->count()],
        );

        return redirect()
            ->route('workflows.index')
            ->with('success', 'Workflow updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workflow $workflow): RedirectResponse
    {
        if ($workflow->is_primary) {
            return back()->with('error', 'Primary workflow cannot be deleted until another workflow is made primary.');
        }

        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'delete', module: 'workflows', action: 'delete',
            subject: $workflow, description: 'Deleted workflow '.$workflow->name.'.',
            metadata: ['deleted' => true],
        );

        $workflow->delete();

        return redirect()
            ->route('workflows.index')
            ->with('success', 'Workflow deleted successfully.');
    }

    /**
     * Save workflow list column preferences.
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
            'workflows.visible_columns' => $visibleColumns,
            'workflows.column_order' => $columnOrder,
        ]);

        return back()->with('success', 'Workflow column preferences saved.');
    }

    /**
     * Reset workflow list column preferences.
     */
    public function resetPreferences(): RedirectResponse
    {
        session()->forget([
            'workflows.visible_columns',
            'workflows.column_order',
        ]);

        return back()->with('success', 'Workflow column preferences reset.');
    }

    /**
     * Export workflows to CSV.
     */
    public function exportCsv(Request $request)
    {
        app(UserEventLogger::class)->record(
            eventType: 'export', module: 'workflows', action: 'export',
            description: 'Exported workflows to CSV.',
            metadata: ['format' => 'csv', 'filters' => $request->only(['search'])],
        );

        $search = trim((string) $request->input('search', ''));

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

        $rows = Workflow::query()
            ->withCount('steps')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $filename = 'workflows-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows, $activeColumnKeys, $columnLabels) {
            $file = fopen('php://output', 'w');

            fputcsv($file, collect($activeColumnKeys)
                ->map(fn ($key) => $columnLabels[$key]['label'] ?? $key)
                ->all());

            foreach ($rows as $workflow) {
                $mapped = [
                    'id' => $workflow->id,
                    'name' => $workflow->name,
                    'code' => $workflow->code,
                    'description' => $workflow->description,
                    'is_primary' => $workflow->is_primary ? 'Yes' : 'No',
                    'is_active' => $workflow->is_active ? 'Yes' : 'No',
                    'step_count' => $workflow->steps_count,
                    'created_at' => $workflow->created_at?->format('Y-m-d H:i'),
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
     * Validate workflow data.
     */
    private function validateWorkflow(Request $request, ?int $workflowId = null): array
    {
        $uniqueCodeRule = 'unique:workflows,code';
        if ($workflowId) {
            $uniqueCodeRule .= ',' . $workflowId;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', $uniqueCodeRule],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_primary' => ['nullable', 'boolean'],

            'steps' => ['nullable', 'array'],
            'steps.*.id' => ['nullable', 'integer'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.code' => ['required', 'string', 'max:255'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
            'steps.*.is_active' => ['nullable', 'boolean'],
            'steps.*.allows_requested_at' => ['nullable', 'boolean'],
            'steps.*.allows_scheduled_at' => ['nullable', 'boolean'],
            'steps.*.allows_completed_at' => ['nullable', 'boolean'],
            'steps.*.allows_notes' => ['nullable', 'boolean'],
            'steps.*.allows_comments' => ['nullable', 'boolean'],
            'steps.*.allows_status' => ['nullable', 'boolean'],
            'steps.*.default_status' => ['nullable', 'string', 'max:255'],

            'steps.*.statuses' => ['nullable', 'array'],
            'steps.*.statuses.*.id' => ['nullable', 'integer'],
            'steps.*.statuses.*.status_code' => ['required', 'string', 'max:255'],
            'steps.*.statuses.*.status_label' => ['required', 'string', 'max:255'],
            'steps.*.statuses.*.is_default' => ['nullable', 'boolean'],
            'steps.*.statuses.*.is_active' => ['nullable', 'boolean'],
            'steps.*.statuses.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);
    }

    /**
     * Sync workflow steps and nested statuses.
     */
    private function syncWorkflowSteps(Workflow $workflow, array $steps): void
    {
        $existingStepIds = $workflow->steps()->pluck('id')->all();
        $keptStepIds = [];

        foreach ($steps as $index => $stepData) {
            $step = $workflow->steps()->updateOrCreate(
                [
                    'id' => $stepData['id'] ?? null,
                ],
                [
                    'name' => $stepData['name'],
                    'code' => $stepData['code'],
                    'step_order' => $index + 1,
                    'is_active' => (bool) ($stepData['is_active'] ?? false),
                    'allows_requested_at' => (bool) ($stepData['allows_requested_at'] ?? false),
                    'allows_scheduled_at' => (bool) ($stepData['allows_scheduled_at'] ?? false),
                    'allows_completed_at' => (bool) ($stepData['allows_completed_at'] ?? false),
                    'allows_notes' => (bool) ($stepData['allows_notes'] ?? false),
                    'allows_comments' => (bool) ($stepData['allows_comments'] ?? false),
                    'allows_status' => (bool) ($stepData['allows_status'] ?? false),
                    'default_status' => $stepData['default_status'] ?? null,
                ]
            );

            $keptStepIds[] = $step->id;

            $existingStatusIds = $step->statuses()->pluck('id')->all();
            $keptStatusIds = [];

            $statuses = $stepData['statuses'] ?? [];

            $defaultFound = false;
            foreach ($statuses as $statusIndex => &$statusData) {
                if (!empty($statusData['is_default']) && !$defaultFound) {
                    $defaultFound = true;
                } else {
                    $statusData['is_default'] = false;
                }

                $status = $step->statuses()->updateOrCreate(
                    [
                        'id' => $statusData['id'] ?? null,
                    ],
                    [
                        'status_code' => $statusData['status_code'],
                        'status_label' => $statusData['status_label'],
                        'sort_order' => $statusIndex + 1,
                        'is_default' => (bool) ($statusData['is_default'] ?? false),
                        'is_active' => (bool) ($statusData['is_active'] ?? true),
                    ]
                );

                $keptStatusIds[] = $status->id;
            }
            unset($statusData);

            $statusIdsToDelete = array_diff($existingStatusIds, $keptStatusIds);
            if (!empty($statusIdsToDelete)) {
                $step->statuses()->whereIn('id', $statusIdsToDelete)->delete();
            }
        }

        $stepIdsToDelete = array_diff($existingStepIds, $keptStepIds);
        if (!empty($stepIdsToDelete)) {
            $workflow->steps()->whereIn('id', $stepIdsToDelete)->delete();
        }
    }

    /**
     * Workflow list columns.
     */
    private function getColumnDefinitions(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'code', 'label' => 'Code', 'sortable' => true],
            ['key' => 'description', 'label' => 'Description', 'sortable' => false],
            ['key' => 'is_primary', 'label' => 'Primary', 'sortable' => true],
            ['key' => 'is_active', 'label' => 'Active', 'sortable' => true],
            ['key' => 'step_count', 'label' => 'Steps', 'sortable' => true],
            ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
        ];
    }

    /**
     * Default visible columns.
     */
    private function getDefaultVisibleColumns(): array
    {
        return [
            'name',
            'code',
            'is_primary',
            'is_active',
            'step_count',
            'created_at',
        ];
    }

    /**
     * Default column order.
     */
    private function getDefaultColumnOrder(): array
    {
        return [
            'id',
            'name',
            'code',
            'description',
            'is_primary',
            'is_active',
            'step_count',
            'created_at',
        ];
    }

    /**
     * Clean requested column keys.
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
}