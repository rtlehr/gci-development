<?php

namespace App\Console\Commands;

use App\Core\Modules\ModuleCatalog;
use App\Core\Modules\ModuleDependencyResolver;
use Illuminate\Console\Command;
use Throwable;

/** Read-only status inspection. It never activates, disables or installs modules. */
final class InsiteModulesStatus extends Command
{
    protected $signature = 'insite:modules:status';

    protected $description = 'Show installed Insite modules and validate the deployment module selection';

    public function handle(ModuleCatalog $catalog, ModuleDependencyResolver $resolver): int
    {
        try {
            $modules = $catalog->all();
        } catch (Throwable $exception) {
            $this->components->error('Module manifest validation failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $selected = config('insite_modules.enabled', []);
        if (! is_array($selected)) {
            $this->components->error('insite_modules.enabled must be an array of module IDs.');

            return self::FAILURE;
        }

        $enabled = array_filter($selected, 'is_string');
        $rows = [];
        foreach ($modules as $module) {
            $requirements = [];
            foreach ($module['requires'] as $id => $version) {
                $requirements[] = $id.' '.$version;
            }

            $rows[] = [
                $module['id'],
                $module['name'],
                $module['version'],
                in_array($module['id'], $enabled, true) ? 'selected' : 'disabled',
                $requirements === [] ? '—' : implode(', ', $requirements),
            ];
        }

        $this->table(['ID', 'Name', 'Version', 'Selection', 'Requires'], $rows);
        if ($modules === []) {
            $this->line('No module manifests found.');
        }

        try {
            $order = $resolver->resolve();
        } catch (Throwable $exception) {
            $this->components->error('Module configuration invalid: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Module configuration valid. '.count($order).' selected module(s).');
        if ($order !== []) {
            $this->line('Dependency order: '.implode(' -> ', $order));
        }

        return self::SUCCESS;
    }
}
