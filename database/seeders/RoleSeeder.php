<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $pmoPermissions = [
            'access_people',
            'create_people',
            'read_people',
            'update_people',
            'delete_people',
            'access_positions',
            'create_positions',
            'read_positions',
            'update_positions',
            'delete_positions',
            'access_candidates',
            'create_candidates',
            'read_candidates',
            'update_candidates',
            'delete_candidates',
            'create_tickets',
            'portal_view_own_tickets',
            'portal_create_tickets',
            'portal_view_positions',
            'access_position_titles',
            'create_position_titles',
            'read_position_titles',
            'update_position_titles',
            'delete_position_titles',
        ];

        $roles = [
            [
                'name' => 'owner',
                'label' => 'Owner',
                'description' => 'Full super administrative access.',
                'permissions' => [
                    'view_owner', 'view_admin',
                    'access_people', 'create_people', 'read_people', 'update_people', 'delete_people',
                    'access_positions', 'create_positions', 'read_positions', 'update_positions', 'delete_positions',
                    'access_candidates', 'create_candidates', 'read_candidates', 'update_candidates', 'delete_candidates',
                    'access_tickets', 'create_tickets', 'read_tickets', 'update_tickets', 'delete_tickets',
                    'portal_view_own_tickets', 'portal_create_tickets', 'portal_view_positions',
                    'access_groups', 'create_groups', 'read_groups', 'update_groups', 'delete_groups',
                    'access_teams', 'create_teams', 'read_teams', 'update_teams', 'delete_teams',
                    'access_permissions', 'create_permissions', 'read_permissions', 'update_permissions', 'delete_permissions',
                    'access_roles', 'create_roles', 'read_roles', 'update_roles', 'delete_roles',
                    'access_position_titles', 'create_position_titles', 'read_position_titles', 'update_position_titles', 'delete_position_titles',
                ],
            ],
            [
                'name' => 'admin',
                'label' => 'Admin',
                'description' => 'Full administrative access.',
                'permissions' => [
                    'view_admin',
                    'access_people', 'create_people', 'read_people', 'update_people', 'delete_people',
                    'access_positions', 'create_positions', 'read_positions', 'update_positions', 'delete_positions',
                    'access_candidates', 'create_candidates', 'read_candidates', 'update_candidates', 'delete_candidates',
                    'access_tickets', 'create_tickets', 'read_tickets', 'update_tickets', 'delete_tickets',
                    'portal_view_own_tickets', 'portal_create_tickets', 'portal_view_positions',
                    'access_groups', 'create_groups', 'read_groups', 'update_groups', 'delete_groups',
                    'access_teams', 'create_teams', 'read_teams', 'update_teams', 'delete_teams',
                    'access_permissions', 'create_permissions', 'read_permissions', 'update_permissions', 'delete_permissions',
                    'access_roles', 'create_roles', 'read_roles', 'update_roles', 'delete_roles',
                    'access_position_titles', 'create_position_titles', 'read_position_titles', 'update_position_titles', 'delete_position_titles',
                ],
            ],
            [
                'name' => 'cotr',
                'label' => 'COTR',
                'description' => 'Can edit all people, positions and candidates.',
                'permissions' => [
                    'access_people', 'create_people', 'read_people', 'update_people', 'delete_people',
                    'access_positions', 'create_positions', 'read_positions', 'update_positions', 'delete_positions',
                    'access_candidates', 'create_candidates', 'read_candidates', 'update_candidates', 'delete_candidates',
                    'create_tickets',
                    'portal_view_own_tickets', 'portal_create_tickets', 'portal_view_positions',
                ],
            ],
            [
                'name' => 'pmo',
                'label' => 'PMO',
                'description' => 'Can edit their people, positions and candidates.',
                'permissions' => $pmoPermissions,
            ],
            [
                'name' => 'project_manager',
                'label' => 'Project Manager',
                'description' => 'Project manager with the same permissions as the PMO role.',
                'permissions' => $pmoPermissions,
            ],
            [
                'name' => 'candidate',
                'label' => 'Candidate',
                'description' => 'People who are candidates for positions.',
                'permissions' => ['create_tickets', 'portal_view_own_tickets', 'portal_create_tickets', 'portal_view_positions'],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionNames = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            $permissionIds = Permission::query()
                ->whereIn('name', $permissionNames)
                ->pluck('id');

            $role->permissions()->sync($permissionIds);
        }
    }
}
