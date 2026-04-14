<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\UserListPreference;
use App\Services\ListEngine;
use App\Services\UserResolver;
use App\Support\ListDefinitions\PositionsDefinition;
use App\Services\ListExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PositionsController extends Controller
{
    public function index(
        Request $request,
        UserResolver $userResolver,
        ListEngine $listEngine
    ) {
        $definition = PositionsDefinition::get();
        $userId = $userResolver->resolveUserId();

        $list = $listEngine->run(
            request: $request,
            definition: $definition,
            userId: $userId,
            query: Position::query(),
            filterCallback: function ($query, $request) {
                $status = $request->input('status', '');

                if ($status) {
                    $query->where('status', $status);
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
        return inertia('Positions/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'position_code' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Open,In Process,Closed'],
            'labor_category' => ['nullable', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'integer'],
            'project_team_name' => ['nullable', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'customer_lead_name' => ['nullable', 'string', 'max:255'],
            'customer_created_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date', 'required_if:status,Closed'],
            'closed_reason' => ['nullable', 'string', 'required_if:status,Closed'],
            'notes' => ['nullable', 'string'],
        ]);

        $position = Position::create($validated);

        return redirect()
            ->route('positions.show', $position->id)
            ->with('success', 'Position created successfully.');
    }

    public function show($id)
    {
        $position = Position::with(['currentAssignment.person', 'assignments.person'])
            ->findOrFail($id);

        return inertia('Positions/Show', [
            'position' => $position,
        ]);
    }

    public function edit($id)
    {
        $position = Position::findOrFail($id);

        return inertia('Positions/Edit', [
            'position' => $position,
        ]);
    }

    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $validated = $request->validate([
            'position_code' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Open,In Process,Closed'],
            'labor_category' => ['nullable', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'integer'],
            'project_team_name' => ['nullable', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'customer_lead_name' => ['nullable', 'string', 'max:255'],
            'customer_created_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date', 'required_if:status,Closed'],
            'closed_reason' => ['nullable', 'string', 'required_if:status,Closed'],
            'notes' => ['nullable', 'string'],
        ]);

        $position->update($validated);

        return redirect()
            ->route('positions.index')
            ->with('success', 'Position updated successfully.');
    }

    public function destroy($id)
    {
        $position = Position::with('assignments')->findOrFail($id);

        if ($position->assignments()->exists()) {
            return redirect()
                ->route('positions.index')
                ->with('error', 'This position cannot be deleted because it has assignments.');
        }

        $position->delete();

        return redirect()
            ->route('positions.index')
            ->with('success', 'Position deleted successfully.');
    }

    public function savePreferences(Request $request, UserResolver $userResolver)
    {
        $definition = PositionsDefinition::get();
        $validKeys = collect($definition['columns'])->pluck('key')->toArray();

        $validated = $request->validate([
            'visible_columns' => ['required', 'array'],
            'visible_columns.*' => ['string'],
            'column_order' => ['required', 'array'],
            'column_order.*' => ['string'],
        ]);

        $visibleColumns = collect($validated['visible_columns'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $columnOrder = collect($validated['column_order'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $userId = $userResolver->resolveUserId();

        UserListPreference::updateOrCreate(
            [
                'user_id' => $userId,
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
        $userId = $userResolver->resolveUserId();

        UserListPreference::where('user_id', $userId)
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
                $status = $request->input('status', '');

                if ($status) {
                    $query->where('status', $status);
                }
            }
        );
    }
    
}