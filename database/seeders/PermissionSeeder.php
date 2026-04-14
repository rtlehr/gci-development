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
                'label' => 'View Admin',
                'description' => 'Can access the admin area.',
            ],
            [
                'name' => 'special_export_access',
                'label' => 'Special Export Access',
                'description' => 'Can use protected export features.',
            ],
            [
                'name' => 'edit_people',
                'label' => 'Edit People',
                'description' => 'Can create and edit people.',
            ],
            [
                'name' => 'edit_positions',
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