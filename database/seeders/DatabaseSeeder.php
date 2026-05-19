<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            PositionSeeder::class,
            WorkflowStepSeeder::class,
            PageHelpSeeder::class,
            OrganizationSeeder::class,
            TeamSeeder::class,
            GroupSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}