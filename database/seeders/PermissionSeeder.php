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
                'name' => 'view_admin',
                'group_name' => 'Admin',
                'label' => 'View Admin',
                'description' => 'Can access the admin area.',
            ],
            [
                'name' => 'special_export_access',
                'group_name' => 'Admin',
                'label' => 'Special Export Access',
                'description' => 'Can use protected export features.',
            ],
            [
                'name' => 'edit_people',
                'group_name' => 'People',
                'label' => 'Edit People',
                'description' => 'Can create and edit people.',
            ],
            [
                'name' => 'edit_positions',
                'group_name' => 'Positions',
                'label' => 'Edit Positions',
                'description' => 'Can create and edit positions.',
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