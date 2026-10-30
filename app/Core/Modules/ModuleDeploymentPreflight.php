<?php

namespace App\Core\Modules;

use InvalidArgumentException;

/** Read-only checks for a prospective deployment. Never activates modules or runs migrations. */
final class ModuleDeploymentPreflight
{
    public function __construct(private readonly ModuleCatalog $catalog) {}

    /** @param list<string> $selected
     *  @return array{core_version: string, selected: list<string>, order: list<string>, migration_files: array<string, int>, warnings: list<string>}
     */
    public function inspect(array $selected, string $coreVersion): array
    {
        if (! preg_match('/^\d+\.\d+\.\d+$/D', $coreVersion)) {
            throw new InvalidArgumentException('Insite Core version must use major.minor.patch.');
        }

        $modules = [];
        foreach ($this->catalog->all() as $module) {
            $modules[$module['id']] = $module;
        }

        $order = (new ModuleDependencyResolver($this->catalog, $selected))->resolve();
        $migrationFiles = [];
        foreach ($order as $id) {
            $module = $modules[$id];
            if ($module['core'] !== null && ! ModuleVersion::matches($coreVersion, $module['core'])) {
                throw new InvalidArgumentException("Module [{$id}] requires Insite Core [{$module['core']}], found [{$coreVersion}].");
            }

            $directory = $module['path'].DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
            $files = is_dir($directory) ? glob($directory.DIRECTORY_SEPARATOR.'*.php') : [];
            if ($files === false) {
                throw new InvalidArgumentException("Unable to inspect migrations for module [{$id}].");
            }
            $migrationFiles[$id] = count($files);
        }

        return [
            'core_version' => $coreVersion,
            'selected' => $selected,
            'order' => $order,
            'migration_files' => $migrationFiles,
            'warnings' => ['Read-only preflight: migration execution, schema compatibility, asset builds, database backups, and rollback readiness are NOT verified.'],
        ];
    }
}
