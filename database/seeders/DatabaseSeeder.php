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
            WorkflowStepSeeder::class,
            PageHelpSeeder::class,
            PositionSeeder::class,
            OrganizationSeeder::class,
            TeamSeeder::class,
            GroupSeeder::class,
            AdminUserSeeder::class,
            CandidateSeeder::class,
        ]);
    }
}