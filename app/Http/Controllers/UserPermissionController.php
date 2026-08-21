<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserListPreference;
use App\Services\ListEngine;
use App\Services\ListExportService;
use App\Services\Encryption\LookupHashService;
use App\Services\PermissionService;
use App\Services\UserResolver;
use App\Support\ListDefinitions\UserPermissionsDefinition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserPermissionController extends Controller
{
    public function index(
        Request $request,
        UserResolver $userResolver,
        ListEngine $listEngine
    ) {
        $definition = UserPermissionsDefinition::get();
        $userId = $userResolver->resolveUserId();

        $list = $listEngine->run(
            request: $request,
            definition: $definition,
            userId: $userId,
            query: User::query()
                ->leftJoin('people', 'people.user_id', '=', 'users.id')
                ->with(['person', 'permissions', 'roles'])
                ->select('users.*')
                ->selectRaw("
                    TRIM(
                        CONCAT(
                            COALESCE(people.first_name, ''),
                            ' ',
                            COALESCE(people.last_name, '')
                        )
                    ) as full_name
                "),
            filterCallback: function ($query, $request) {
                $search = $request->input('search', '');

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('users.email', 'like', "%{$search}%")
                            ->orWhere('people.first_name', 'like', "%{$search}%")
                            ->orWhere('people.last_name', 'like', "%{$search}%")
                            ->orWhere('people.person_code_lookup', app(LookupHashService::class)->hash($search));
                    });
                }
            }
        );

        $list['filters']['search'] = $request->input('search', '');

        return Inertia::render('Admin/Users/Index', [
            'users' => $list['rows'],
            'columns' => $list['columns'],
            'visibleColumns' => $list['visibleColumns'],
            'columnOrder' => $list['columnOrder'],
            'filters' => $list['filters'],
            'sort' => $list['sort'],
            'direction' => $list['direction'],
        ]);
    }

    public function edit(User $user)
    {
        $user->load(['permissions', 'roles']);

        $roles = Role::query()
            ->with([
                'permissions' => function ($query) {
                    $query->select(
                        'permissions.id',
                        'permissions.name',
                        'permissions.group_name',
                        'permissions.label',
                        'permissions.description'
                    );
                },
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'label', 'description']);

        $permissionGroups = Permission::query()
            ->orderBy('group_name')
            ->orderBy('name')
            ->get(['id', 'name', 'group_name', 'label', 'description'])
            ->groupBy(function ($permission) {
                return $permission->group_name ?: 'Other';
            })
            ->map(function ($groupPermissions, $groupName) {
                return [
                    'group' => $groupName,
                    'permissions' => $groupPermissions->values(),
                ];
            })
            ->values();

        return Inertia::render('Admin/Users/EditPermissions', [
            'user' => $user,
            'roles' => $roles,
            'selectedRoles' => $user->roles->pluck('id')->toArray(),
            'permissionGroups' => $permissionGroups,
            'selectedPermissions' => $user->permissions->pluck('id')->toArray(),
        ]);
    }

    public function update(Request $request, User $user, PermissionService $permissionService)
    {
        $validated = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $roleIds = $validated['roles'] ?? [];
        $permissionIds = $validated['permissions'] ?? [];

        $user->roles()->sync($roleIds);
        $user->permissions()->sync($permissionIds);

        $permissionService->clearUserPermissionCache($user->id);

        return redirect()
            ->route('admin.users.permissions.edit', $user->id)
            ->with('success', 'User access updated successfully.');
    }

    public function savePreferences(Request $request, UserResolver $userResolver)
    {
        $definition = UserPermissionsDefinition::get();
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
            ->route('admin.users.index')
            ->with('success', 'Column preferences saved.');
    }

    public function resetPreferences(UserResolver $userResolver)
    {
        $definition = UserPermissionsDefinition::get();
        $userId = $userResolver->resolveUserId();

        UserListPreference::where('user_id', $userId)
            ->where('list_key', $definition['list_key'])
            ->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Column preferences reset to defaults.');
    }

    public function exportCsv(
        Request $request,
        ListExportService $listExportService
    ): StreamedResponse {
        return $listExportService->exportCsv(
            request: $request,
            definition: UserPermissionsDefinition::get(),
            query: User::query()
                ->leftJoin('people', 'people.user_id', '=', 'users.id')
                ->with(['person', 'permissions', 'roles'])
                ->select('users.*'),
            filenamePrefix: 'user-permissions-export',
            filterCallback: function ($query, $request) {
                $search = $request->input('search', '');

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('users.email', 'like', "%{$search}%")
                            ->orWhere('people.first_name', 'like', "%{$search}%")
                            ->orWhere('people.last_name', 'like', "%{$search}%")
                            ->orWhere('people.person_code_lookup', app(LookupHashService::class)->hash($search));
                    });
                }
            }
        );
    }
}