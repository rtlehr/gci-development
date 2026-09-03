<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Keep Laravel's default --seed behavior pointed at the development profile.
     *
     * QA/UAT data must be selected explicitly with QaDatabaseSeeder or the
     * app:reset-qa command so a normal development reset never surprises us.
     */
    public function run(): void
    {
        $this->call(DevelopmentDatabaseSeeder::class);
    }
}
