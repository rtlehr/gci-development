<?php

namespace App\Console\Commands;

use Database\Seeders\DevelopmentDatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetDevelopmentDatabase extends Command
{
    protected $signature = 'app:reset-development';

    protected $description = 'Drop, migrate, and seed the database with the Insite Portal development profile';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('REFUSED: app:reset-development cannot run in production.');

            return self::FAILURE;
        }

        $this->warn('Rebuilding the database with the DEVELOPMENT seed profile...');

        $exitCode = Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--seeder' => DevelopmentDatabaseSeeder::class,
            '--force' => true,
        ]);

        $this->output->write(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        $this->newLine();
        $this->info('=====================================================');
        $this->info(' INSITE PORTAL DEVELOPMENT DATABASE READY');
        $this->info(' Seed profile: DEVELOPMENT');
        $this->info('=====================================================');

        return self::SUCCESS;
    }
}
