<?php

namespace App\Core\Modules;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;

/**
 * Discovers module metadata without loading or executing module code.
 * Module activation, dependencies, and provider registration are later milestones.
 */
final class ModuleCatalog
{
    public function __construct(private readonly string $directory) {}

    /** @return list<array{id: string, name: string, version: string, path: string, requires: array<string, string>, provider: ?string}> */
    public function all(): array
    {
        if (! File::isDirectory($this->directory)) {
            return [];
        }

        $modules = [];

        foreach (File::directories($this->directory) as $directory) {
            $manifest = $directory.DIRECTORY_SEPARATOR.'module.json';

            if (! File::isFile($manifest)) {
                continue;
            }

            try {
                $data = json_decode(File::get($manifest), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException("Invalid module manifest [{$manifest}].", 0, $exception);
            }

            if (! is_array($data)) {
                throw new InvalidArgumentException("Module manifest must be an object [{$manifest}].");
            }

            foreach (['id', 'name', 'version'] as $field) {
                if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                    throw new InvalidArgumentException("Module manifest [{$manifest}] requires a non-empty string [{$field}].");
                }
            }

            if (! preg_match('/^[a-z][a-z0-9-]*$/D', $data['id'])) {
                throw new InvalidArgumentException("Invalid module identifier [{$data['id']}].");
            }

            if (basename($directory) !== $data['id']) {
                throw new InvalidArgumentException("Module [{$data['id']}] must reside in a directory with the same name.");
            }

            if (isset($modules[$data['id']])) {
                throw new InvalidArgumentException("Duplicate module identifier [{$data['id']}].");
            }

            if (! preg_match('/^\d+\.\d+\.\d+$/D', $data['version'])) {
                throw new InvalidArgumentException("Module [{$data['id']}] must declare a stable semantic version (major.minor.patch).");
            }

            $requires = $data['requires'] ?? [];
            if (! is_array($requires) || array_is_list($requires) && $requires !== []) {
                throw new InvalidArgumentException("Module [{$data['id']}] requires must be an object mapping IDs to version constraints.");
            }
            foreach ($requires as $dependency => $constraint) {
                if (! is_string($dependency) || ! preg_match('/^[a-z][a-z0-9-]*$/D', $dependency)
                    || $dependency === $data['id'] || ! is_string($constraint)
                    || ! ModuleVersion::isValidConstraint($constraint)) {
                    throw new InvalidArgumentException("Invalid dependency declaration on module [{$data['id']}].");
                }
            }
            ksort($requires);

            $provider = $data['provider'] ?? null;
            if ($provider !== null && (! is_string($provider)
                || ! str_starts_with($provider, 'Insite\\Modules\\')
                || ! preg_match('/^[A-Za-z0-9_\\\\]+$/D', substr($provider, strlen('Insite\\Modules\\')))
                || str_ends_with($provider, '\\'))) {
                throw new InvalidArgumentException("Module [{$data['id']}] has an invalid provider class.");
            }

            $modules[$data['id']] = ['provider' => $provider,
                'id' => $data['id'],
                'name' => $data['name'],
                'version' => $data['version'],
                'path' => $directory,
                'requires' => $requires,
            ];
        }

        ksort($modules);

        return array_values($modules);
    }

    /** @return array{id: string, name: string, version: string, path: string, requires: array<string, string>, provider: ?string}|null */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $module) {
            if ($module['id'] === $id) {
                return $module;
            }
        }

        return null;
    }
}
