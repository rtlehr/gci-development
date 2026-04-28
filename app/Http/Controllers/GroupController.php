<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Support\ListDefinitions\GroupDefinition;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $definition = GroupDefinition::get();

        $columns = collect($definition['columns'])
            ->sortBy('default_order')
            ->values()
            ->all();

        // 👇 RIGHT HERE — replace your old visibleColumns/columnOrder logic
        $visibleColumns = session(
            'groups.visible_columns',
            collect($columns)
                ->where('default_visible', true)
                ->pluck('key')
                ->values()
                ->all()
        );

        $columnOrder = session(
            'groups.column_order',
            collect($columns)
                ->pluck('key')
                ->values()
                ->all()
        );

        // sorting
        $sort = $request->input('sort', $definition['default_sort']);
        $direction = $request->input('direction', $definition['default_direction']);

        $groups = Group::query()
            ->when($request->search, function ($query, $search) {
                $query->where('group_name', 'like', "%{$search}%");
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Groups/Index', [
            'groups' => $groups,
            'columns' => $columns,
            'visibleColumns' => $visibleColumns,
            'columnOrder' => $columnOrder,
            'filters' => [
                'search' => $request->search,
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Groups/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_name' => ['required', 'string', 'max:255', 'unique:groups,group_name'],
        ]);

        Group::create($validated);

        return redirect()
            ->route('groups.index')
            ->with('success', 'Group created successfully.');
    }

    public function edit(Group $group)
    {
        return Inertia::render('Admin/Groups/Edit', [
            'group' => $group,
        ]);
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'group_name' => [
                'required',
                'string',
                'max:255',
                'unique:groups,group_name,' . $group->id,
            ],
        ]);

        $group->update($validated);

        return redirect()
            ->route('groups.index')
            ->with('success', 'Group updated successfully.');
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return redirect()
            ->route('groups.index')
            ->with('success', 'Group deleted successfully.');
    }

    public function exportCsv(Request $request): StreamedResponse
{
    $definition = GroupDefinition::get();

    $columns = collect($definition['columns'])
        ->sortBy('default_order')
        ->values();

    $requestedVisibleColumns = $request->input('visible_columns', []);
    $requestedColumnOrder = $request->input('column_order', []);

    $visibleColumns = !empty($requestedVisibleColumns)
        ? $requestedVisibleColumns
        : $columns->where('default_visible', true)->pluck('key')->values()->all();

    $columnOrder = !empty($requestedColumnOrder)
        ? $requestedColumnOrder
        : $columns->pluck('key')->values()->all();

    $exportColumns = collect($columnOrder)
        ->filter(fn ($key) => in_array($key, $visibleColumns, true))
        ->map(fn ($key) => $columns->firstWhere('key', $key))
        ->filter()
        ->filter(fn ($column) => $column['exportable'] ?? false)
        ->values();

    $query = Group::query();

    if ($request->filled('search')) {
        $query->where('group_name', 'like', '%' . $request->search . '%');
    }

    $groups = $query
        ->orderBy('group_name')
        ->get();

    $filename = 'groups-' . now()->format('Y-m-d-His') . '.csv';

    return response()->streamDownload(function () use ($groups, $exportColumns) {
        $handle = fopen('php://output', 'w');

        fputcsv($handle, $exportColumns->pluck('label')->all());

        foreach ($groups as $group) {
            fputcsv($handle, $exportColumns
                ->map(fn ($column) => $group->{$column['key']} ?? '')
                ->all()
            );
        }

        fclose($handle);
    }, $filename, [
        'Content-Type' => 'text/csv',
    ]);
}

public function savePreferences(Request $request)
{
    $validated = $request->validate([
        'visible_columns' => ['required', 'array'],
        'visible_columns.*' => ['string'],
        'column_order' => ['required', 'array'],
        'column_order.*' => ['string'],
    ]);

    session([
        'groups.visible_columns' => $validated['visible_columns'],
        'groups.column_order' => $validated['column_order'],
    ]);

    return back()->with('success', 'Group column preferences saved.');
}

public function resetPreferences()
{
    session()->forget([
        'groups.visible_columns',
        'groups.column_order',
    ]);

    return back()->with('success', 'Group column preferences reset.');
}

}