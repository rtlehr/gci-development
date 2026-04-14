<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use App\Models\UserListPreference;
use App\Services\ListEngine;
use App\Services\UserResolver;
use App\Support\ListDefinitions\PeopleDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeopleController extends Controller
{
    public function index(
        Request $request,
        UserResolver $userResolver,
        ListEngine $listEngine
    ) {
        $definition = PeopleDefinition::get();
        $userId = $userResolver->resolveUserId();

        $list = $listEngine->run(
            request: $request,
            definition: $definition,
            userId: $userId,
            query: Person::query(),
        );

        return inertia('People/Index', [
            'people' => $list['rows'],
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
        $users = User::orderBy('name')->get(['id', 'name']);

        return inertia('People/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_code' => ['required', 'string', 'max:255', 'unique:people,person_code'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'employment_status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name' => $validated['person_code'],
            'email' => $validated['email'] ?? null,
            'password' => bcrypt(Str::random(32)),
        ]);

        $validated['user_id'] = $user->id;

        $person = Person::create($validated);

        return redirect()
            ->route('people.show', $person->id)
            ->with('success', 'Person and user account created successfully.');
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
        $users = User::orderBy('name')->get(['id', 'name']);

        return inertia('People/Edit', [
            'person' => $person,
            'users' => $users,
        ]);
    }

    public function update(Request $request, $id)
    {
        $person = Person::findOrFail($id);

        $validated = $request->validate([
            'person_code' => ['required', 'string', 'max:255', 'unique:people,person_code,' . $person->id],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'employment_status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'user_id' => ['nullable', 'exists:users,id', 'unique:people,user_id,' . $person->id],
        ]);

        $person->update($validated);

        if ($person->user_id) {
            $user = User::find($person->user_id);

            if ($user) {
                $user->name = $validated['person_code'];
                $user->email = $validated['email'] ?? null;
                $user->save();
            }
        }

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

    public function savePreferences(Request $request, UserResolver $userResolver)
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
            ->route('people.index')
            ->with('success', 'Column preferences saved.');
    }

    public function resetPreferences(UserResolver $userResolver)
    {
        $definition = PeopleDefinition::get();
        $userId = $userResolver->resolveUserId();

        UserListPreference::where('user_id', $userId)
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

            fputcsv($handle, $activeColumns->pluck('label')->toArray());

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