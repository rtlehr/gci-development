<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'label' => 'Admin',
                'description' => 'Full administrative access.',
                'permissions' => [
                    'view_admin',
                    'special_export_access',
                    'edit_people',
                    'edit_positions',
                ],
            ],
            [
                'name' => 'editor',
                'label' => 'Editor',
                'description' => 'Can edit people and positions.',
                'permissions' => [
                    'edit_people',
                    'edit_positions',
                ],
            ],
            [
                'name' => 'viewer',
                'label' => 'Viewer',
                'description' => 'Read-only access.',
                'permissions' => [],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionNames = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');

            $role->permissions()->sync($permissionIds);
        }
    }
}