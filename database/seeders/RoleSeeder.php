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
                    'create_people',
                    'read_people',
                    'update_people',
                    'delete_people',
                    'create_positions',
                    'read_positions',
                    'update_positions',
                    'delete_positions',
                    'create_candidates',
                    'read_candidates',
                    'update_candidates',
                    'delete_candidates'
                ],
            ],
            [
                'name' => 'admin',
                'label' => 'Admin',
                'description' => 'Full administrative access.',
                'permissions' => [
                    'view_admin',
                    'create_people',
                    'read_people',
                    'update_people',
                    'delete_people',
                    'create_positions',
                    'read_positions',
                    'update_positions',
                    'delete_positions',
                    'create_candidates',
                    'read_candidates',
                    'update_candidates',
                    'delete_candidates'
                ],
            ],
            [
                'name' => 'cotr',
                'label' => 'COTR',
                'description' => 'Can edit all people, positions and candidates.',
                'permissions' => [
                    'create_people',
                    'read_people',
                    'update_people',
                    'delete_people',
                    'create_positions',
                    'read_positions',
                    'update_positions',
                    'delete_positions',
                    'create_candidates',
                    'read_candidates',
                    'update_candidates',
                    'delete_candidates'
                ],
            ],
            [
                'name' => 'pmo',
                'label' => 'PMO',
                'description' => 'Can edit their people, positions and candidates.',
                'permissions' => [
                    'create_people',
                    'read_people',
                    'update_people',
                    'delete_people',
                    'create_positions',
                    'read_positions',
                    'update_positions',
                    'delete_positions',
                    'create_candidates',
                    'read_candidates',
                    'update_candidates',
                    'delete_candidates'
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