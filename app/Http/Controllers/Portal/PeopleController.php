<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Person;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\UserListPreference;
use App\Services\AddressService;
use App\Services\AttachmentService;
use App\Services\ListEngine;
use App\Services\ListExportService;
use App\Services\PersonPhoneService;
use App\Services\PersonUserAccountService;
use App\Services\UserResolver;
use App\Support\ListDefinitions\PeopleDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeopleController extends Controller
{
    private function peopleListQuery()
    {
        return Person::query()
            ->leftJoin('person_phone_numbers as primary_phone', function ($join) {
                $join->on('primary_phone.person_id', '=', 'people.id')
                    ->where('primary_phone.is_primary', true);
            })
            ->leftJoin('addresses as primary_address', function ($join) {
                $join->on('primary_address.person_id', '=', 'people.id')
                    ->where('primary_address.is_primary', true);
            })
            ->select('people.*')
            ->selectRaw('primary_phone.phone_number as primary_phone_number')
            ->selectRaw("
                TRIM(
                    CONCAT(
                        COALESCE(primary_address.line_1, ''),
                        CASE
                            WHEN primary_address.line_1 IS NOT NULL
                                AND primary_address.line_1 <> ''
                                AND primary_address.city IS NOT NULL
                                AND primary_address.city <> ''
                            THEN ', '
                            ELSE ''
                        END,
                        COALESCE(primary_address.city, ''),
                        CASE
                            WHEN primary_address.city IS NOT NULL
                                AND primary_address.city <> ''
                                AND primary_address.state IS NOT NULL
                                AND primary_address.state <> ''
                            THEN ', '
                            ELSE ''
                        END,
                        COALESCE(primary_address.state, ''),
                        CASE
                            WHEN primary_address.state IS NOT NULL
                                AND primary_address.state <> ''
                                AND primary_address.postal_code IS NOT NULL
                                AND primary_address.postal_code <> ''
                            THEN ' '
                            ELSE ''
                        END,
                        COALESCE(primary_address.postal_code, '')
                    )
                ) as primary_address_display
            ");
    }

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
            query: $this->peopleListQuery()
        );

        return inertia('Portal/People/Index', [
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

        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'label', 'description']);

        $groups = Group::orderBy('group_name')->get([
            'id',
            'group_name',
        ]);

        $teams = Team::orderBy('team_name')->get([
            'id',
            'team_name',
        ]);

        return inertia('Portal/People/Create', [
            'users' => $users,
            'roles' => $roles,
            'groups' => $groups,
            'teams' => $teams,
        ]);
    }

    public function store(
        Request $request,
        PersonPhoneService $personPhoneService,
        AddressService $addressService,
        PersonUserAccountService $personUserAccountService,
        AttachmentService $attachmentService
    ) {
        $validated = $request->validate([
            'person_code' => ['required', 'string', 'max:255', 'unique:people,person_code'],
            'first_name' => ['required', 'string', 'max:255'],
            'alternate_first_name' => ['nullable', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'alternate_last_name' => ['nullable', 'string', 'max:255'],
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

            'addresses' => ['nullable', 'array'],
            'addresses.*.id' => ['nullable', 'integer'],
            'addresses.*.address_type' => ['nullable', 'string', 'max:50'],
            'addresses.*.line_1' => ['nullable', 'string', 'max:255'],
            'addresses.*.line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['nullable', 'string', 'max:255'],
            'addresses.*.state' => ['nullable', 'string', 'max:100'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['nullable', 'string', 'max:100'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'addresses.*.notes' => ['nullable', 'string'],

            'new_attachments' => ['nullable', 'array'],
            'new_attachments.*' => ['file', 'max:10240'],
            'attachment_meta' => ['nullable', 'array'],
            'attachment_meta.*.category' => ['nullable', 'string', 'max:100'],
            'attachment_meta.*.description' => ['nullable', 'string'],
            'attachment_meta.*.is_primary' => ['nullable', 'boolean'],
            'attachment_meta.*.sort_order' => ['nullable', 'integer'],

            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],

            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],

            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $newAttachments = $request->file('new_attachments', []);

        return DB::transaction(function () use (
            $validated,
            $newAttachments,
            $personPhoneService,
            $addressService,
            $personUserAccountService,
            $attachmentService
        ) {
            $phoneNumbersInput = $validated['phone_numbers'] ?? [];
            $addressesInput = $validated['addresses'] ?? [];
            $attachmentMeta = $validated['attachment_meta'] ?? [];
            $groupIds = $validated['group_ids'] ?? [];
            $teamIds = $validated['team_ids'] ?? [];
            $roleIds = $validated['role_ids'] ?? [];

            unset(
                $validated['phone_numbers'],
                $validated['addresses'],
                $validated['new_attachments'],
                $validated['attachment_meta'],
                $validated['group_ids'],
                $validated['team_ids'],
                $validated['role_ids']
            );

            $user = $personUserAccountService->createForPerson($validated);

            $validated['user_id'] = $user->id;

            $person = Person::create($validated);

            $user->roles()->sync($roleIds);

            $person->groups()->sync($groupIds);
            $person->teams()->sync($teamIds);

            $personPhoneService->createForPerson($person, $phoneNumbersInput);
            $addressService->createForPerson($person, $addressesInput);

            $attachmentService->validatePrimaryPerCategory($attachmentMeta);
            $attachmentService->uploadForModel(
                model: $person,
                files: $newAttachments,
                metadata: $attachmentMeta,
                uploadedByUserId: $user->id
            );

            return redirect()
                ->route('portal.people.show', $person->id)
                ->with('success', 'Person and user account created successfully.');
        });
    }

    public function show($id)
    {
        $person = Person::with([
            'assignments.position',
            'phoneNumbers',
            'primaryPhoneNumber',
            'addresses',
            'primaryAddress',
            'attachments',
            'groups',
            'teams',
            'user.roles:id,name,label,description',
        ])->findOrFail($id);

        return inertia('Portal/People/Show', [
            'person' => $person,
        ]);
    }

    public function edit(
        $id,
        PersonPhoneService $personPhoneService,
        AddressService $addressService,
        AttachmentService $attachmentService
    ) {
        $person = Person::with([
            'phoneNumbers',
            'primaryPhoneNumber',
            'addresses',
            'primaryAddress',
            'attachments',
            'groups:id,group_name',
            'teams:id,team_name',
            'user.roles:id,name,label,description',
        ])->findOrFail($id);

        $users = User::orderBy('name')->get(['id', 'name']);

        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'label', 'description']);

        $groups = Group::orderBy('group_name')->get([
            'id',
            'group_name',
        ]);

        $teams = Team::orderBy('team_name')->get([
            'id',
            'team_name',
        ]);

        $person->phone_numbers = $personPhoneService->normalizeForForm($person->phoneNumbers);
        $person->addresses = $addressService->normalizeForForm($person->addresses);
        $person->attachments_for_ui = $attachmentService->normalizeForUi($person->attachments);

        $selectedRoleIds = $person->user
            ? $person->user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->values()
            : collect();

        return inertia('Portal/People/Edit', [
            'person' => $person,
            'selectedRoleIds' => $selectedRoleIds,
            'users' => $users,
            'roles' => $roles,
            'groups' => $groups,
            'teams' => $teams,
        ]);
    }

    public function update(
        Request $request,
        $id,
        PersonPhoneService $personPhoneService,
        AddressService $addressService,
        PersonUserAccountService $personUserAccountService,
        AttachmentService $attachmentService
    ) {
        $person = Person::findOrFail($id);

        $validated = $request->validate([
            'person_code' => ['required', 'string', 'max:255', 'unique:people,person_code,' . $person->id],
            'first_name' => ['required', 'string', 'max:255'],
            'alternate_first_name' => ['nullable', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'alternate_last_name' => ['nullable', 'string', 'max:255'],
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

            'addresses' => ['nullable', 'array'],
            'addresses.*.id' => ['nullable', 'integer'],
            'addresses.*.address_type' => ['nullable', 'string', 'max:50'],
            'addresses.*.line_1' => ['nullable', 'string', 'max:255'],
            'addresses.*.line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['nullable', 'string', 'max:255'],
            'addresses.*.state' => ['nullable', 'string', 'max:100'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['nullable', 'string', 'max:100'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'addresses.*.notes' => ['nullable', 'string'],

            'new_attachments' => ['nullable', 'array'],
            'new_attachments.*' => ['file', 'max:10240'],
            'attachment_meta' => ['nullable', 'array'],
            'attachment_meta.*.category' => ['nullable', 'string', 'max:100'],
            'attachment_meta.*.description' => ['nullable', 'string'],
            'attachment_meta.*.is_primary' => ['nullable', 'boolean'],
            'attachment_meta.*.sort_order' => ['nullable', 'integer'],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer'],

            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],

            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],

            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $newAttachments = $request->file('new_attachments', []);

        return DB::transaction(function () use (
            $person,
            $validated,
            $newAttachments,
            $personPhoneService,
            $addressService,
            $personUserAccountService,
            $attachmentService
        ) {
            $phoneNumbersInput = $validated['phone_numbers'] ?? [];
            $addressesInput = $validated['addresses'] ?? [];
            $attachmentMeta = $validated['attachment_meta'] ?? [];
            $removeAttachmentIds = $validated['remove_attachment_ids'] ?? [];
            $groupIds = $validated['group_ids'] ?? [];
            $teamIds = $validated['team_ids'] ?? [];
            $roleIds = $validated['role_ids'] ?? [];

            unset(
                $validated['phone_numbers'],
                $validated['addresses'],
                $validated['new_attachments'],
                $validated['attachment_meta'],
                $validated['remove_attachment_ids'],
                $validated['group_ids'],
                $validated['team_ids'],
                $validated['role_ids']
            );

            $person->update($validated);

            $person->groups()->sync($groupIds);
            $person->teams()->sync($teamIds);

            $personUserAccountService->syncFromPerson($person, $validated);

            $person->refresh();
            if ($person->user) {
                $person->user->roles()->sync($roleIds);
            }
            $personPhoneService->sync($person, $phoneNumbersInput);
            $addressService->sync($person, $addressesInput);

            $attachmentService->validatePrimaryPerCategory($attachmentMeta);
            $attachmentService->syncForModel(
                model: $person,
                newFiles: $newAttachments,
                metadata: $attachmentMeta,
                removeIds: $removeAttachmentIds,
                uploadedByUserId: $person->user_id
            );

            return redirect()
                ->route('portal.people.index')
                ->with('success', 'Person updated successfully.');
        });
    }

    public function destroy($id)
    {
        $person = Person::with('assignments')->findOrFail($id);

        if ($person->assignments()->exists()) {
            return redirect()
                ->route('portal.people.index')
                ->with('error', 'This person cannot be deleted because they have assignments.');
        }

        $person->delete();

        return redirect()
            ->route('portal.people.index')
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
            ->route('portal.people.index')
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
            ->route('portal.people.index')
            ->with('success', 'Column preferences reset to defaults.');
    }

    public function exportCsv(
        Request $request,
        ListExportService $listExportService
    ): StreamedResponse {
        return $listExportService->exportCsv(
            request: $request,
            definition: PeopleDefinition::get(),
            query: $this->peopleListQuery(),
            filenamePrefix: 'people-export'
        );
    }
}