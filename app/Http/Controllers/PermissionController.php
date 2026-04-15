<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $permissions = Permission::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%")
                        ->orWhere('group_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('group_name')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Permissions/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_system' => ['boolean'],
            'is_locked' => ['boolean'],
        ]);

        $validated['is_system'] = $request->boolean('is_system');
        $validated['is_locked'] = $request->boolean('is_locked');

        Permission::create($validated);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission)
    {
        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {   

        if ($permission->is_locked) {
            return redirect()
                ->route('admin.permissions.index')
                ->with('error', 'This permission is locked and cannot be edited.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,' . $permission->id],
            'group_name' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_system' => ['boolean'],
            'is_locked' => ['boolean'],
        ]);

        $validated['is_system'] = $request->boolean('is_system');
        $validated['is_locked'] = $request->boolean('is_locked');

        $permission->update($validated);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {

        if ($permission->is_locked) {
            return redirect()
                ->route('admin.permissions.index')
                ->with('error', 'This permission is locked and cannot be deleted.');
        }

        $permission->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}