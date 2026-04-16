<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use App\Models\UserListPreference;
use App\Services\ListEngine;
use App\Services\ListExportService;
use App\Services\UserResolver;
use App\Support\ListDefinitions\PeopleDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\PersonPhoneNumber;
use App\Services\PersonPhoneService;
use App\Services\PersonUserAccountService;
use Illuminate\Support\Facades\DB;

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
            query: Person::query()
                ->leftJoin('person_phone_numbers as primary_phone', function ($join) {
                    $join->on('primary_phone.person_id', '=', 'people.id')
                        ->where('primary_phone.is_primary', true);
                })
                ->select('people.*')
                ->selectRaw('primary_phone.phone_number as primary_phone_number')
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

    public function store(
    Request $request,
    PersonPhoneService $personPhoneService,
    PersonUserAccountService $personUserAccountService
    ) {
        $validated = $request->validate([
            'person_code' => ['required', 'string', 'max:255', 'unique:people,person_code'],
            'first_name' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'employment_status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'phone_numbers' => ['nullable', 'array'],
            'phone_numbers.*.id' => ['nullable', 'integer'],
            'phone_numbers.*.phone_number' => ['nullable', 'string', 'max:50'],
            'phone_numbers.*.phone_type' => ['nullable', 'string', 'max:50'],
            'phone_numbers.*.is_primary' => ['nullable', 'boolean'],
            'phone_numbers.*.extension' => ['nullable', 'string', 'max:20'],
            'phone_numbers.*.notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $personPhoneService, $personUserAccountService) {
            $phoneNumbersInput = $validated['phone_numbers'] ?? [];
            unset($validated['phone_numbers']);

            $user = $personUserAccountService->createForPerson($validated);

            $validated['user_id'] = $user->id;

            $person = Person::create($validated);

            $personPhoneService->createForPerson($person, $phoneNumbersInput);

            return redirect()
                ->route('people.show', $person->id)
                ->with('success', 'Person and user account created successfully.');
        });
    }

    public function show($id)
    {
        $person = Person::with([
            'assignments.position',
            'phoneNumbers',
            'primaryPhoneNumber',
        ])->findOrFail($id);

        return inertia('People/Show', [
            'person' => $person,
        ]);
    }

    public function edit($id, PersonPhoneService $personPhoneService)
    {
        $person = Person::with([
            'phoneNumbers',
            'primaryPhoneNumber',
        ])->findOrFail($id);

        $users = User::orderBy('name')->get(['id', 'name']);

        $person->phone_numbers = $personPhoneService->normalizeForForm($person->phoneNumbers);

        return inertia('People/Edit', [
            'person' => $person,
            'users' => $users,
        ]);
    }

    public function update(
    Request $request,
    $id,
    PersonPhoneService $personPhoneService,
    PersonUserAccountService $personUserAccountService
    ) {
        $person = Person::findOrFail($id);

        $validated = $request->validate([
            'person_code' => ['required', 'string', 'max:255', 'unique:people,person_code,' . $person->id],
            'first_name' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'employment_status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'user_id' => ['nullable', 'exists:users,id', 'unique:people,user_id,' . $person->id],
            'phone_numbers' => ['nullable', 'array'],
            'phone_numbers.*.id' => ['nullable', 'integer'],
            'phone_numbers.*.phone_number' => ['nullable', 'string', 'max:50'],
            'phone_numbers.*.phone_type' => ['nullable', 'string', 'max:50'],
            'phone_numbers.*.is_primary' => ['nullable', 'boolean'],
            'phone_numbers.*.extension' => ['nullable', 'string', 'max:20'],
            'phone_numbers.*.notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($person, $validated, $personPhoneService, $personUserAccountService) {
            $phoneNumbersInput = $validated['phone_numbers'] ?? [];
            unset($validated['phone_numbers']);

            $person->update($validated);

            $personUserAccountService->syncFromPerson($person, $validated);
            $personPhoneService->sync($person, $phoneNumbersInput);

            return redirect()
                ->route('people.index')
                ->with('success', 'Person updated successfully.');
        });
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

    public function exportCsv(
        Request $request,
        ListExportService $listExportService
    ): StreamedResponse {
        return $listExportService->exportCsv(
            request: $request,
            definition: PeopleDefinition::get(),
            query: Person::query()
                ->leftJoin('person_phone_numbers as primary_phone', function ($join) {
                    $join->on('primary_phone.person_id', '=', 'people.id')
                        ->where('primary_phone.is_primary', true);
                })
                ->select('people.*')
                ->selectRaw('primary_phone.phone_number as primary_phone_number'),
            filenamePrefix: 'people-export'
        );
    }
}