<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Support\ListDefinitions\TeamDefinition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $definition = TeamDefinition::get();

        $columns = collect($definition['columns'])
            ->sortBy('default_order')
            ->values()
            ->all();

        $visibleColumns = session(
            'teams.visible_columns',
            collect($columns)
                ->where('default_visible', true)
                ->pluck('key')
                ->values()
                ->all()
        );

        $columnOrder = session(
            'teams.column_order',
            collect($columns)
                ->pluck('key')
                ->values()
                ->all()
        );

        $sort = $request->input('sort', $definition['default_sort']);
        $direction = $request->input('direction', $definition['default_direction']);

        $teams = Team::query()
            ->when($request->search, function ($query, $search) {
                $query->where('team_name', 'like', "%{$search}%");
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Teams/Index', [
            'teams' => $teams,
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
        return Inertia::render('Admin/Teams/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'team_name' => ['required', 'string', 'max:255', 'unique:teams,team_name'],
        ]);

        Team::create($validated);

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team created successfully.');
    }

    public function edit(Team $team)
    {
        return Inertia::render('Admin/Teams/Edit', [
            'team' => $team,
        ]);
    }

    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'team_name' => [
                'required',
                'string',
                'max:255',
                'unique:teams,team_name,' . $team->id,
            ],
        ]);

        $team->update($validated);

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team)
    {
        $team->delete();

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team deleted successfully.');
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
            'teams.visible_columns' => $validated['visible_columns'],
            'teams.column_order' => $validated['column_order'],
        ]);

        return back()->with('success', 'Team column preferences saved.');
    }

    public function resetPreferences()
    {
        session()->forget([
            'teams.visible_columns',
            'teams.column_order',
        ]);

        return back()->with('success', 'Team column preferences reset.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $definition = TeamDefinition::get();

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

        $query = Team::query();

        if ($request->filled('search')) {
            $query->where('team_name', 'like', '%' . $request->search . '%');
        }

        $teams = $query
            ->orderBy('team_name')
            ->get();

        $filename = 'teams-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($teams, $exportColumns) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $exportColumns->pluck('label')->all());

            foreach ($teams as $team) {
                fputcsv($handle, $exportColumns
                    ->map(fn ($column) => $team->{$column['key']} ?? '')
                    ->all()
                );
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}