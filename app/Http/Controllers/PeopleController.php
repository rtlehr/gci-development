<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;

use App\Models\UserListPreference;
use App\Services\ListPreferenceService;
use App\Support\ListDefinitions\PeopleDefinition;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeopleController extends Controller
{
    public function index(Request $request)
    {
        $definition = PeopleDefinition::get();

        $search = $request->input('search', '');
        $sort = $request->input('sort', $definition['default_sort']);
        $direction = $request->input('direction', $definition['default_direction']);

        $preferences = ListPreferenceService::getUserPreferences(
            Auth::id(),
            $definition['list_key']
        );

        $merged = ListPreferenceService::merge($definition, $preferences);

        $query = Person::query();

        if ($search) {
            $searchableFields = collect($definition['columns'])
                ->whereIn('key', $merged['visible'])
                ->where('searchable', true)
                ->pluck('db_field');

            $query->where(function ($q) use ($searchableFields, $search) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        $sortableColumn = collect($definition['columns'])
            ->firstWhere('key', $sort);

        if (
            $sortableColumn &&
            in_array($sort, $merged['visible']) &&
            ($sortableColumn['sortable'] ?? false)
        ) {
            $query->orderBy($sortableColumn['db_field'], $direction);
        } else {
            $defaultSortCol = collect($definition['columns'])
                ->firstWhere('key', $definition['default_sort']);

            $query->orderBy($defaultSortCol['db_field'], $definition['default_direction']);
            $sort = $definition['default_sort'];
            $direction = $definition['default_direction'];
        }

        $people = $query->paginate(10)->withQueryString();

        return inertia('People/Index', [
            'people' => $people,
            'columns' => $definition['columns'],
            'visibleColumns' => $merged['visible'],
            'columnOrder' => $merged['order'],
            'filters' => [
                'search' => $search,
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        return inertia('People/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_code' => ['nullable', 'string', 'max:255', 'unique:people,person_code'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'employment_status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $person = Person::create($validated);

        return redirect()
            ->route('people.show', $person->id)
            ->with('success', 'Person created successfully.');
    }

    public function show($id)
    {
        $person = Person::with(['assignments.position'])->findOrFail($id);

        return inertia('People/Show', [
            'person' => $person,
        ]);
    }

    public function edit($id)
    {
        $person = Person::findOrFail($id);

        return inertia('People/Edit', [
            'person' => $person,
        ]);
    }

    public function update(Request $request, $id)
    {
        $person = Person::findOrFail($id);

        $validated = $request->validate([
            'person_code' => ['nullable', 'string', 'max:255', 'unique:people,person_code,' . $person->id],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'employment_status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $person->update($validated);

        return redirect()
            ->route('people.index')
            ->with('success', 'Person updated successfully.');
    }

    public function destroy($id)
    {
        $person = Person::with('assignments')->findOrFail($id);

        if ($person->assignments()->exists()) {
            return redirect()
                ->route('people.index')
                ->with('error', 'This person cannot be deleted because they have assignments.');
        }

        $person->delete();

        return redirect()
            ->route('people.index')
            ->with('success', 'Person deleted successfully.');
    }

    public function savePreferences(Request $request)
    {
        $definition = PeopleDefinition::get();
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
            ->route('people.index')
            ->with('success', 'Column preferences saved.');
    }

    public function resetPreferences()
    {
        $definition = PeopleDefinition::get();

        UserListPreference::where('user_id', Auth::id())
            ->where('list_key', $definition['list_key'])
            ->delete();

        return redirect()
            ->route('people.index')
            ->with('success', 'Column preferences reset to defaults.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $definition = PeopleDefinition::get();

        $visibleColumns = $request->input('visible_columns', []);
        $columnOrder = $request->input('column_order', []);
        $search = $request->input('search', '');

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

        $query = Person::query();

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

        $people = $query->get();

        $filename = 'people-export-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return Response::streamDownload(function () use ($people, $activeColumns) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, $activeColumns->pluck('label')->toArray());

            // Data rows
            foreach ($people as $person) {
                $row = [];

                foreach ($activeColumns as $column) {
                    $key = $column['key'];
                    $row[] = $person->{$key} ?? '';
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}