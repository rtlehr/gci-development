<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $roles = Role::query()
            ->withCount('permissions')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
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

    protected function clearCachesForUsersWithRole(Role $role): void
    {
        $permissionService = app(PermissionService::class);

        $userIds = $role->users()->pluck('users.id')->toArray();

        foreach ($userIds as $userId) {
            $permissionService->clearUserPermissionCache($userId);
        }
    }
}