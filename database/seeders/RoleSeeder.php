<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $standardPortal = [
            'access_portal',
            'portal_view_dashboard',
            'portal_view_personal_information',
            'portal_view_own_tickets',
            'portal_create_tickets',
            'portal_view_resources',
            'portal_view_program_contacts',
        ];

        $pmoCotr = [
            ...$standardPortal,
            'portal_view_directory',
            'portal_view_positions',
            'portal_view_all_positions',

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

            'access_position_titles',
            'create_position_titles',
            'read_position_titles',
            'update_position_titles',
            'delete_position_titles',
        ];

        $adminOperational = [
            'view_admin',
            ...$standardPortal,
            'portal_view_directory',
            'portal_view_positions',
            'portal_view_all_positions',
            'portal_view_requests',
            'portal_create_requests',

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

            'access_tickets',
            'create_tickets',
            'read_tickets',
            'update_tickets',
            'delete_tickets',

            'access_groups',
            'create_groups',
            'read_groups',
            'update_groups',
            'delete_groups',

            'access_teams',
            'create_teams',
            'read_teams',
            'update_teams',
            'delete_teams',

            'access_organizations',
            'manage_organizations',
            'access_workflows',
            'manage_workflows',
            'access_content_pages',
            'manage_content_pages',
            'access_custom_fields',
            'manage_custom_fields',
            'access_user_event_log',
            'export_user_event_log',
            'access_data_import',
            'manage_data_import',
            'rollback_data_import',

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
                'description' => 'Unrestricted system owner access.',
                'permissions' => Permission::query()->pluck('name')->all(),
            ],
            [
                'name' => 'admin',
                'label' => 'Admin',
                'description' => 'Operational administrator without application-setup permissions.',
                'permissions' => $adminOperational,
            ],
            [
                'name' => 'developer',
                'label' => 'Developer',
                'description' => 'Technical support access, audited impersonation, and no operational-data administration by default.',
                'permissions' => [
                    'view_admin',
                    ...$standardPortal,
                    'access_tickets',
                    'read_tickets',
                    'update_tickets',
                    'impersonate_users',
                    'view_impersonation_log',
                ],
            ],
            [
                'name' => 'cotr',
                'label' => 'COTR',
                'description' => 'Portal management access equivalent to PMO.',
                'permissions' => $pmoCotr,
            ],
            [
                'name' => 'pmo',
                'label' => 'PMO',
                'description' => 'Can view all positions and manage Portal workforce workflows.',
                'permissions' => $pmoCotr,
            ],
            [
                'name' => 'project_manager',
                'label' => 'Project Manager',
                'description' => 'Can view assigned positions and related progress without edit permissions.',
                'permissions' => [
                    ...$standardPortal,
                    'portal_view_positions',
                    'portal_view_assigned_positions',
                ],
            ],
            [
                'name' => 'candidate',
                'label' => 'Candidate',
                'description' => 'Can view personal information, assigned opportunities, and candidate progress.',
                'permissions' => [
                    ...$standardPortal,
                    'portal_view_positions',
                    'portal_view_candidate_positions',
                    'portal_view_candidate_progress',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionNames = array_values(array_unique($roleData['permissions']));
            unset($roleData['permissions']);

            $role = Role::query()->updateOrCreate(
                ['name' => $roleData['name']],
                $roleData,
            );

            $permissionIds = Permission::query()
                ->whereIn('name', $permissionNames)
                ->pluck('id');

            $role->permissions()->sync($permissionIds);
        }
    }
}
