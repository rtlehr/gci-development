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
            ->with(['person', 'permissions'])
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
        $user->load('permissions');

        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name', 'label', 'description']);

        return Inertia::render('Admin/Users/EditPermissions', [
            'user' => $user,
            'permissions' => $permissions,
            'selectedPermissions' => $user->permissions->pluck('id')->toArray(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = $validated['permissions'] ?? [];

        $user->permissions()->sync($permissionIds);

        return redirect()
            ->route('admin.users.permissions.edit', $user->id)
            ->with('success', 'Permissions updated successfully.');
    }
}