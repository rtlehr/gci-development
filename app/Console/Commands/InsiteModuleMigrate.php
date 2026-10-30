<?php

namespace App\Console\Commands;

use App\Core\Modules\ModuleMigrationPlan;
use Illuminate\Console\Command;
use Throwable;

/** Explicit module-only migration entry point. No implicit rollbacks or schema removal. */
final class InsiteModuleMigrate extends Command
{
    protected $signature = 'insite:modules:migrate {module : Installed and enabled module identifier} {--apply : Execute pending migrations; without this option only inspect} {--force : Permit execution in production after external backup approval}';

    protected $description = 'Preview or explicitly execute migrations belonging to one enabled Insite module';

    public function handle(ModuleMigrationPlan $planner): int
    {
        try {
            $selected = config('insite_modules.enabled', []);
            $coreVersion = config('insite_modules.core_version');
            if (! is_array($selected) || ! is_string($coreVersion)) {
                throw new \InvalidArgumentException('Invalid module deployment configuration.');
            }
            $plan = $planner->inspect((string) $this->argument('module'), $selected, $coreVersion);
        } catch (Throwable $exception) {
            $this->components->error('Module migration preflight failed: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->line('Module: '.$plan['id']);
        $this->line('Insite Core contract: '.$plan['core_version']);
        $this->line('Migration files: '.count($plan['files']));
        foreach ($plan['files'] as $file) {
            $this->line('  '.$file);
        }

        if (! $this->option('apply')) {
            $this->components->info('Preview only. No migrations executed; pending status and database backup not verified.');
            return self::SUCCESS;
        }

        if ($plan['files'] === []) {
            $this->components->info('No module migration files to execute.');
            return self::SUCCESS;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->components->error('Production execution requires --force after verifying a restorable backup and maintenance plan.');
            return self::FAILURE;
        }

        $this->components->warn('This executes database migrations. Confirm the current database, a restorable backup, and maintenance/rollback plan before running.');
        if (! $this->option('force') && ! $this->confirm('Apply migrations for this module?', false)) {
            $this->components->warn('Cancelled; no migrations executed.');
            return self::FAILURE;
        }

        // --realpath scopes Laravel migrator to this one module directory. The Laravel
        // migrations table tracks applied files; never run migrate:rollback automatically.
        return $this->call('migrate', [
            '--path' => $plan['directory'],
            '--realpath' => true,
            '--force' => (bool) $this->option('force'),
        ]) === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }
}
