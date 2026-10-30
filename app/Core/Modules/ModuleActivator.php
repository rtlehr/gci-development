<?php

namespace App\Core\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

/** Activates only explicitly selected, dependency-valid modules from trusted local packages. */
final class ModuleActivator
{
    public function __construct(
        private readonly Application $app,
        private readonly ModuleCatalog $catalog,
        private readonly ModuleDependencyResolver $resolver,
    ) {}

    /** @return list<string> Activated module identifiers in dependency-first order. */
    public function activate(): array
    {
        // Resolve the entire graph before registering any executable module code.
        $ordered = $this->resolver->resolve();
        $available = array_column($this->catalog->all(), null, 'id');
        $providers = [];

        foreach ($ordered as $id) {
            $provider = $available[$id]['provider'];
            if ($provider === null) {
                throw new InvalidArgumentException("Enabled module [{$id}] must declare a provider.");
            }
            if (! class_exists($provider) || ! is_subclass_of($provider, ServiceProvider::class)) {
                throw new InvalidArgumentException("Module [{$id}] provider [{$provider}] must extend Laravel ServiceProvider.");
            }
            $providers[$id] = $provider;
        }

        foreach ($providers as $provider) {
            $this->app->register($provider);
        }

        // Routes are owned by the module, but loaded by the Core activator.
        // This works both during normal application boot and when tests activate
        // modules after Laravel has already booted. Providers need not rely on
        // a late boot callback to register routes.
        if (! $this->app->routesAreCached()) {
            $loadedRoutes = false;

            foreach ($ordered as $id) {
                $routes = $available[$id]['path'].DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';
                if (is_file($routes)) {
                    require $routes;
                    $loadedRoutes = true;
                }
            }

            // When modules are activated after the router has booted (for
            // example in a test), Laravel's name lookup may already be built.
            // Refresh it so newly registered module route names can be found.
            if ($loadedRoutes) {
                Route::getRoutes()->refreshNameLookups();
            }
        }

        return $ordered;
    }
}
