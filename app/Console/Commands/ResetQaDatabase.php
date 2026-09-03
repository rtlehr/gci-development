<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\QaDatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetQaDatabase extends Command
{
    protected $signature = 'app:reset-qa';

    protected $description = 'Drop, migrate, and seed the database with the deterministic Insite Portal QA/UAT profile';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('REFUSED: app:reset-qa cannot run in production.');

            return self::FAILURE;
        }

        $this->warn('Rebuilding the database with the QA/UAT seed profile...');

        $exitCode = Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--seeder' => QaDatabaseSeeder::class,
            '--force' => true,
        ]);

        $this->output->write(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        $this->newLine();
        $this->info('=====================================================');
        $this->info(' INSITE PORTAL QA TEST DATABASE READY');
        $this->info(' Seed profile: QA / UAT');
        $this->info(' Identity model: person_code (no tester password login)');
        $this->info(' Owner person_code: 1111111');
        $this->line(' Users:      '.User::query()->where('email', 'like', 'qa.%@localhost')->count());
        $this->line(' People:     '.Person::query()->where('company_name', 'QA Test Organization')->count());
        $this->line(' Positions:  '.Position::query()->where('position_code', 'like', 'QA-POS-%')->count());
        $this->line(' Candidates: '.Candidate::query()->where('candidate_code', 'like', 'QA-CAND-%')->count());
        $this->line(' Tickets:    '.Ticket::query()->where('ticket_number', 'like', 'QA-TCK-%')->count());
        $this->info('=====================================================');
        $this->comment('Use the local development user switcher or set DEV_PERSON_CODE to the QA person_code for role testing.');
        $this->comment('QA-POS-HOLD is intentionally not seeded; current code has no persistent On-Hold staffing input state.');

        return self::SUCCESS;
    }
}
