<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use App\Support\ListDefinitions\PositionsDefinition;
use App\Services\ListPreferenceService;
use Illuminate\Support\Facades\Auth;
use App\Models\UserListPreference;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Response;

class PositionsController extends Controller
{
    public function index(Request $request)
    {

        $definition = PositionsDefinition::get();

        // ✅ Get filters from request
        $search = $request->input('search', '');
        $status = $request->input('status', '');

        // ✅ Get sorting
        $sort = $request->input('sort', $definition['default_sort']);
        $direction = $request->input('direction', $definition['default_direction']);

        // ✅ Get user preferences
        $preferences = ListPreferenceService::getUserPreferences(
            Auth::id(),
            $definition['list_key']
        );

        $merged = ListPreferenceService::merge($definition, $preferences);

        // ✅ Build query
        $query = Position::query();

        // 🔍 Apply search (ONLY on visible + searchable columns)
        if ($search) {
            $searchableColumns = collect($definition['columns'])
                ->whereIn('key', $merged['visible'])
                ->where('searchable', true)
                ->pluck('db_field');

            $query->where(function ($q) use ($searchableColumns, $search) {
                foreach ($searchableColumns as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        // 🎯 Status filter
        if ($status) {
            $query->where('status', $status);
        }

        // 🔄 Sorting (only if valid)
        $validSort = collect($definition['columns'])
            ->pluck('key')
            ->contains($sort);

        if ($validSort) {
            $dbField = collect($definition['columns'])
                ->firstWhere('key', $sort)['db_field'];

            $query->orderBy($dbField, $direction);
        }

        $positions = $query->paginate(10)->withQueryString();

        return inertia('Positions/Index', [
            'positions' => $positions,
            'columns' => $definition['columns'],
            'visibleColumns' => $merged['visible'],
            'columnOrder' => $merged['order'],

            // ✅ These were missing before
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'sort' => $sort,
            'direction' => $direction,
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
            'status' => ['nullable', 'in:Open,In Process,Closed'],
            'labor_category' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
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
        $position = Position::with([
            'currentAssignment.person',
            'assignments.person',
        ])->findOrFail($id);

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

    public function savePreferences(Request $request)
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

        UserListPreference::updateOrCreate(
            [
                'user_id' => Auth::id(),
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

    public function resetPreferences()
    {
        $definition = PositionsDefinition::get();

        UserListPreference::where('user_id', Auth::id())
            ->where('list_key', $definition['list_key'])
            ->delete();

        return redirect()
            ->route('positions.index')
            ->with('success', 'Column preferences reset to defaults.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $definition = PositionsDefinition::get();

        $visibleColumns = $request->input('visible_columns', []);
        $columnOrder = $request->input('column_order', []);
        $search = $request->input('search', '');
        $status = $request->input('status', '');

        $allColumns = collect($definition['columns']);

        $validKeys = $allColumns->pluck('key')->toArray();

        $visibleColumns = collect($visibleColumns)
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $columnOrder = collect($columnOrder)
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $activeColumnKeys = collect($columnOrder)
            ->filter(fn ($key) => in_array($key, $visibleColumns))
            ->values()
            ->toArray();

        $activeColumns = $allColumns
            ->whereIn('key', $activeColumnKeys)
            ->sortBy(function ($col) use ($activeColumnKeys) {
                return array_search($col['key'], $activeColumnKeys);
            })
            ->values();

        $query = Position::query();

        if ($search) {
            $searchableFields = $activeColumns
                ->where('searchable', true)
                ->pluck('db_field');

            $query->where(function ($q) use ($searchableFields, $search) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $positions = $query->get();

        $filename = 'positions-export-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return Response::streamDownload(function () use ($positions, $activeColumns) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, $activeColumns->pluck('label')->toArray());

            // Data rows
            foreach ($positions as $position) {
                $row = [];

                foreach ($activeColumns as $column) {
                    $key = $column['key'];
                    $row[] = $position->{$key} ?? '';
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}