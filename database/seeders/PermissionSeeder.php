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
                'name' => 'edit_people',
                'group_name' => 'People',
                'label' => 'Edit People',
                'description' => 'Can create and edit people.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'edit_positions',
                'group_name' => 'Positions',
                'label' => 'Edit Positions',
                'description' => 'Can create and edit positions.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'create_candidates',
                'group_name' => 'candidates',
                'label' => 'Create Candidates',
                'description' => 'Can create and edit candidates.',
                'is_system' => false,
                'is_locked' => false,
            ],
            [
                'name' => 'crud_all',
                'group_name' => 'CRUD',
                'label' => 'CRUD All',
                'description' => 'Can perform all CRUD operations on all entities.',
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