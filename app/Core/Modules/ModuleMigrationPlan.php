<?php

namespace App\Core\Modules;

use InvalidArgumentException;

/** Builds a migration plan from trusted local module paths; never touches the database. */
final class ModuleMigrationPlan
{
    public function __construct(
        private readonly ModuleCatalog $catalog,
        private readonly ModuleDeploymentPreflight $preflight,
    ) {}

    /** @return array{id: string, directory: string, files: list<string>, core_version: string} */
    public function inspect(string $id, array $selected, string $coreVersion): array
    {
        // Validate the entire selected dependency graph and Core compatibility first.
        $validated = $this->preflight->inspect($selected, $coreVersion);
        if (! in_array($id, $validated['order'], true)) {
            throw new InvalidArgumentException("Module [{$id}] is not enabled or installed in this deployment.");
        }

        $module = $this->catalog->find($id);
        if ($module === null) {
            throw new InvalidArgumentException("Module [{$id}] is not installed.");
        }

        $directory = $module['path'].DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        if (is_link($directory)) {
            throw new InvalidArgumentException("Module [{$id}] migration directory must not be a symbolic link.");
        }
        if (! is_dir($directory)) {
            return ['id' => $id, 'directory' => $directory, 'files' => [], 'core_version' => $coreVersion];
        }

        $files = glob($directory.DIRECTORY_SEPARATOR.'*.php');
        if ($files === false) {
            throw new InvalidArgumentException("Cannot inspect module [{$id}] migrations.");
        }
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            if (is_link($file) || ! is_file($file)) {
                throw new InvalidArgumentException("Module [{$id}] contains an unsupported migration file.");
            }
        }

        return [
            'id' => $id,
            'directory' => $directory,
            'files' => array_values(array_map('basename', $files)),
            'core_version' => $coreVersion,
        ];
    }
}
