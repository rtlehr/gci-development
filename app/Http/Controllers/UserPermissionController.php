<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserPermissionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $users = User::query()
            ->leftJoin('people', 'people.user_id', '=', 'users.id')
            ->with(['person', 'permissions', 'roles'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('users.email', 'like', "%{$search}%")
                        ->orWhere('people.first_name', 'like', "%{$search}%")
                        ->orWhere('people.last_name', 'like', "%{$search}%")
                        ->orWhere('people.person_code', 'like', "%{$search}%");
                });
            })
            ->select('users.*')
            ->orderBy('people.last_name')
            ->orderBy('people.first_name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function edit(User $user)
    {
        $user->load(['permissions', 'roles']);

        $roles = \App\Models\Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'label', 'description']);

        $permissionGroups = \App\Models\Permission::query()
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

        return \Inertia\Inertia::render('Admin/Users/EditPermissions', [
            'user' => $user,
            'roles' => $roles,
            'selectedRoles' => $user->roles->pluck('id')->toArray(),
            'permissionGroups' => $permissionGroups,
            'selectedPermissions' => $user->permissions->pluck('id')->toArray(),
        ]);
    }

    public function update(Request $request, User $user)
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

        app(\App\Services\PermissionService::class)->clearUserPermissionCache($user->id);

        return redirect()
            ->route('admin.users.permissions.edit', $user->id)
            ->with('success', 'User access updated successfully.');
    }
}