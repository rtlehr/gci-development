<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'last_name');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = [
            'id',
            'person_code',
            'first_name',
            'last_name',
            'company_name',
            'email',
            'employment_status',
            'created_at',
        ];

        if (!in_array($sort, $allowedSorts)) {
            $sort = 'last_name';
        }

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $people = Person::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('person_code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('cell_phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employment_status', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return inertia('People/Index', [
            'people' => $people,
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
}