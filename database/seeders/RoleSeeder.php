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
                'name' => 'owner',
                'label' => 'Owner',
                'description' => 'Full super administrative access.',
                'permissions' => [
                    'view_owner',
                    'view_admin',
                    'view_owner',
                    'edit_people',
                    'edit_positions',
                    'create_candidates',
                    'crud_all'
                ],
            ],
            [
                'name' => 'admin',
                'label' => 'Admin',
                'description' => 'Full administrative access.',
                'permissions' => [
                    'view_admin',
                    'view_owner',
                    'edit_people',
                    'edit_positions',
                    'create_candidates',
                    'crud_all'
                ],
            ],
            [
                'name' => 'cotr',
                'label' => 'COTR',
                'description' => 'Can edit all people, positions and candidates.',
                'permissions' => [
                    'edit_people',
                    'edit_positions',
                    'create_candidates',
                    'crud_all',
                ],
            ],
            [
                'name' => 'pmo',
                'label' => 'PMO',
                'description' => 'Can edit their people, positions and candidates.',
                'permissions' => [
                    'edit_people',
                    'edit_positions',
                    'create_candidates',
                ],
            ],
            [
                'name' => 'candidate',
                'label' => 'Candidate',
                'description' => 'People who are candidates for positions',
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