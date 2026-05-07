<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\UserListPreference;
use App\Services\ListEngine;
use App\Services\UserResolver;
use App\Services\ListExportService;
use App\Support\ListDefinitions\PermissionsDefinition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PermissionController extends Controller
{
    public function index(
        Request $request,
        UserResolver $userResolver,
        ListEngine $listEngine
    ) {
        $definition = PermissionsDefinition::get();
        $userId = $userResolver->resolveUserId();

        $list = $listEngine->run(
            request: $request,
            definition: $definition,
            userId: $userId,
            query: Permission::query(),
            filterCallback: function ($query, $request) {
                $search = $request->input('search', '');

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('label', 'like', "%{$search}%")
                            ->orWhere('group_name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            }
        );

        $list['filters']['search'] = $request->input('search', '');

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $list['rows'],
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

    public function savePreferences(Request $request, UserResolver $userResolver)
    {
        $definition = PermissionsDefinition::get();
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
            ->route('admin.permissions.index')
            ->with('success', 'Column preferences saved.');
    }

    public function resetPreferences(UserResolver $userResolver)
    {
        $definition = PermissionsDefinition::get();
        $userId = $userResolver->resolveUserId();

        UserListPreference::where('user_id', $userId)
            ->where('list_key', $definition['list_key'])
            ->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Column preferences reset to defaults.');
    }

    public function exportCsv(
        Request $request,
        ListExportService $listExportService
    ): StreamedResponse {
        return $listExportService->exportCsv(
            request: $request,
            definition: PermissionsDefinition::get(),
            query: Permission::query(),
            filenamePrefix: 'permissions-export',
            filterCallback: function ($query, $request) {
                $search = $request->input('search', '');

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('label', 'like', "%{$search}%")
                            ->orWhere('group_name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            }
        );
    }

    public function editPermissions(User $user)
    {
        return Inertia::render('Admin/Users/Permissions', [
            'user' => $user,
            
            'roles' => Role::with('permissions')
                ->orderBy('name')
                ->get(),

            'selectedRoles' => $user->roles()
                ->pluck('roles.id')
                ->toArray(),

            'permissionGroups' => Permission::orderBy('group_name')
                ->orderBy('name')
                ->get()
                ->groupBy('group_name')
                ->map(function ($permissions, $group) {
                    return [
                        'group' => $group ?: 'Other',
                        'permissions' => $permissions->values(),
                    ];
                })
                ->values(),

            'selectedPermissions' => $user->permissions()
                ->pluck('permissions.id')
                ->toArray(),
        ]);
    }

}