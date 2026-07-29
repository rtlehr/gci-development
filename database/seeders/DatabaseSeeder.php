<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SiteSettingSeeder::class,
            ContentPageSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            OrganizationSeeder::class,
            TeamSeeder::class,
            GroupSeeder::class,
            JobTitleSeeder::class,
            JobTitleRequirementSeeder::class,
            WorkflowStepSeeder::class,
            WorkflowStepStatusSeeder::class,
            AdminUserSeeder::class,
            PositionSeeder::class,
            PageHelpSeeder::class,
            TicketSeeder::class,
            CandidateSeeder::class,
        ]);
    }
}
