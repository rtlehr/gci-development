<?php

namespace App\Console\Commands;

use App\Core\Modules\ModuleDeploymentPreflight;
use Illuminate\Console\Command;
use Throwable;

/** Validates a proposed deployment without executing modules or changing data. */
final class InsiteModulesPreflight extends Command
{
    protected $signature = 'insite:modules:preflight';

    protected $description = 'Read-only Insite module configuration and Core compatibility preflight';

    public function handle(ModuleDeploymentPreflight $preflight): int
    {
        try {
            $selected = config('insite_modules.enabled', []);
            $coreVersion = config('insite_modules.core_version');
            if (! is_array($selected) || ! is_string($coreVersion)) {
                throw new \InvalidArgumentException('Module selection or Core version configuration is invalid.');
            }
            $result = $preflight->inspect($selected, $coreVersion);
        } catch (Throwable $exception) {
            $this->components->error('Deployment preflight failed: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->line('Insite Core contract version: '.$result['core_version']);
        $this->line('Module load order: '.($result['order'] === [] ? '(none)' : implode(' -> ', $result['order'])));
        foreach ($result['migration_files'] as $id => $count) {
            $this->line("Module [{$id}] migration PHP files: {$count} (not executed)");
        }
        foreach ($result['warnings'] as $warning) {
            $this->components->warn($warning);
        }
        $this->components->info('Deployment preflight passed (static checks only).');
        return self::SUCCESS;
    }
}
