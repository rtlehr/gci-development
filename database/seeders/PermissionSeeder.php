<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'view_owner',
                'group_name' => 'Admin',
                'label' => 'View Owner',
                'description' => 'Can access the owner area.',
                'is_system' => true,
                'is_locked' => true,
            ],
            [
                'name' => 'view_admin',
                'group_name' => 'Admin',
                'label' => 'View Admin',
                'description' => 'Can access the admin area.',
                'is_system' => true,
                'is_locked' => true,
            ],
            [
                'name' => 'create_people',
                'group_name' => 'People',
                'label' => 'Create People',
                'description' => 'Can create people.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'read_people',
                'group_name' => 'People',
                'label' => 'Read People',
                'description' => 'Can View peoples information.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'update_people',
                'group_name' => 'People',
                'label' => 'Update People',
                'description' => 'Can update a persons information.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'delete_people',
                'group_name' => 'People',
                'label' => 'Delete People',
                'description' => 'Can delete a person.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'create_positions',
                'group_name' => 'Positions',
                'label' => 'Create Positions',
                'description' => 'Can create positions.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'read_positions',
                'group_name' => 'Positions',
                'label' => 'Read Positions',
                'description' => 'Can view positions information.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'update_positions',
                'group_name' => 'Positions',
                'label' => 'Update Positions',
                'description' => 'Can update positions information.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'delete_positions',
                'group_name' => 'Positions',
                'label' => 'Delete a Position',
                'description' => 'Can delete positions.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'create_candidates',
                'group_name' => 'Candidates',
                'label' => 'Create Candidates',
                'description' => 'Can create candidates.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'read_candidates',
                'group_name' => 'Candidates',
                'label' => 'Read Candidates',
                'description' => 'Can view a candidates information.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'update_candidates',
                'group_name' => 'Candidates',
                'label' => 'Update Candidate',
                'description' => 'Can update candidates information.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'delete_candidates',
                'group_name' => 'Candidates',
                'label' => 'Create Candidates',
                'description' => 'Can delete candidates.',
                'is_system' => false,
                'is_locked' => false,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}