<?php

namespace Database\Seeders;

use Database\Seeders\Qa\QaScenarioSeeder;
use Illuminate\Database\Seeder;

class QaDatabaseSeeder extends Seeder
{
    /**
     * Build the small, deterministic database used by the manual QA/UAT plan.
     */
    public function run(): void
    {
        // Shared application configuration. These are product configuration,
        // not development-only sample records.
        $this->call([
            SiteSettingSeeder::class,
            ContentPageSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            WorkflowStepSeeder::class,
            WorkflowStepStatusSeeder::class,
            PageHelpSeeder::class,
        ]);

        // Purpose-built records referenced by the manual test matrix.
        $this->call(QaScenarioSeeder::class);
    }
}
