<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\UserListPreference;
use App\Services\ListEngine;
use App\Services\ListExportService;
use App\Services\PermissionService;
use App\Services\UserResolver;
use App\Support\ListDefinitions\RolesDefinition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoleController extends Controller
{
    public function index(
        Request $request,
        UserResolver $userResolver,
        ListEngine $listEngine
    ) {
        $definition = RolesDefinition::get();
        $userId = $userResolver->resolveUserId();

        $list = $listEngine->run(
            request: $request,
            definition: $definition,
            userId: $userId,
            query: Role::query()->withCount('permissions'),
            filterCallback: function ($query, $request) {
                $search = $request->input('search', '');

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('label', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            }
        );

        $list['filters']['search'] = $request->input('search', '');

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $list['rows'],
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
        $permissionGroups = Permission::query()
            ->orderBy('group_name')
            ->orderBy('name')
            ->get(['id', 'name', 'group_name', 'label', 'description'])
            ->groupBy(fn ($permission) => $permission->group_name ?: 'Other')
            ->map(fn ($groupPermissions, $groupName) => [
                'group' => $groupName,
                'permissions' => $groupPermissions->values(),
            ])
            ->values();

        return Inertia::render('Admin/Roles/Create', [
            'permissionGroups' => $permissionGroups,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $role = Role::create($validated);
        $role->permissions()->sync($permissionIds);

        $this->clearCachesForUsersWithRole($role);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');

        $permissionGroups = Permission::query()
            ->orderBy('group_name')
            ->orderBy('name')
            ->get(['id', 'name', 'group_name', 'label', 'description'])
            ->groupBy(fn ($permission) => $permission->group_name ?: 'Other')
            ->map(fn ($groupPermissions, $groupName) => [
                'group' => $groupName,
                'permissions' => $groupPermissions->values(),
            ])
            ->values();

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'permissionGroups' => $permissionGroups,
            'selectedPermissions' => $role->permissions->pluck('id')->toArray(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $role->update($validated);
        $role->permissions()->sync($permissionIds);

        $this->clearCachesForUsersWithRole($role);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $userIds = $role->users()->pluck('users.id')->toArray();

        $role->delete();

        $permissionService = app(PermissionService::class);

        foreach ($userIds as $userId) {
            $permissionService->clearUserPermissionCache($userId);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    public function savePreferences(Request $request, UserResolver $userResolver)
    {
        $definition = RolesDefinition::get();
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
            ->route('admin.roles.index')
            ->with('success', 'Column preferences saved.');
    }

    public function resetPreferences(UserResolver $userResolver)
    {
        $definition = RolesDefinition::get();
        $userId = $userResolver->resolveUserId();

        UserListPreference::where('user_id', $userId)
            ->where('list_key', $definition['list_key'])
            ->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Column preferences reset to defaults.');
    }

    public function exportCsv(
        Request $request,
        ListExportService $listExportService
    ): StreamedResponse {
        return $listExportService->exportCsv(
            request: $request,
            definition: RolesDefinition::get(),
            query: Role::query()->withCount('permissions'),
            filenamePrefix: 'roles-export',
            filterCallback: function ($query, $request) {
                $search = $request->input('search', '');

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('label', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            }
        );
    }

    protected function clearCachesForUsersWithRole(Role $role): void
    {
        $permissionService = app(PermissionService::class);

        $userIds = $role->users()->pluck('users.id')->toArray();

        foreach ($userIds as $userId) {
            $permissionService->clearUserPermissionCache($userId);
        }
    }
}