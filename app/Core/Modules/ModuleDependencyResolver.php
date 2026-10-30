<?php

namespace App\Core\Modules;

use InvalidArgumentException;

/** Validates selection and computes dependency-first order; never executes module code. */
final class ModuleDependencyResolver
{
    public function __construct(private readonly ModuleCatalog $catalog, private readonly array $enabledIds) {}

    /** @return list<string> Enabled module IDs in dependency-first, deterministic order. */
    public function resolve(): array
    {
        $available = [];
        foreach ($this->catalog->all() as $module) {
            $available[$module['id']] = $module;
        }

        $enabled = [];
        foreach ($this->enabledIds as $id) {
            if (! is_string($id) || ! preg_match('/^[a-z][a-z0-9-]*$/D', $id)) {
                throw new InvalidArgumentException('Enabled module IDs must be valid strings.');
            }
            if (isset($enabled[$id])) {
                throw new InvalidArgumentException("Module [{$id}] is enabled more than once.");
            }
            if (! isset($available[$id])) {
                throw new InvalidArgumentException("Enabled module [{$id}] is not installed.");
            }
            $enabled[$id] = true;
        }

        $state = [];
        $order = [];
        $visit = function (string $id) use (&$visit, &$state, &$order, $available, $enabled): void {
            if (($state[$id] ?? null) === 'visiting') {
                throw new InvalidArgumentException("Circular module dependency detected involving [{$id}].");
            }
            if (($state[$id] ?? null) === 'done') {
                return;
            }
            $state[$id] = 'visiting';
            foreach ($available[$id]['requires'] as $dependency => $constraint) {
                if (! isset($available[$dependency])) {
                    throw new InvalidArgumentException("Module [{$id}] requires missing module [{$dependency}].");
                }
                if (! isset($enabled[$dependency])) {
                    throw new InvalidArgumentException("Module [{$id}] requires disabled module [{$dependency}].");
                }
                if (! ModuleVersion::matches($available[$dependency]['version'], $constraint)) {
                    throw new InvalidArgumentException("Module [{$id}] requires [{$dependency}] version [{$constraint}], found [{$available[$dependency]['version']}].");
                }
                $visit($dependency);
            }
            $state[$id] = 'done';
            $order[] = $id;
        };

        $ids = array_keys($enabled);
        sort($ids);
        foreach ($ids as $id) {
            $visit($id);
        }

        return $order;
    }
}
