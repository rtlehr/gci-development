<?php

namespace App\Providers;

use App\Core\Modules\ModuleCatalog;
use App\Core\Modules\ModuleActivator;
use App\Core\Modules\ModuleDependencyResolver;
use App\Core\Modules\ModuleDeploymentPreflight;
use Illuminate\Support\ServiceProvider;

final class ModuleCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleCatalog::class, fn (): ModuleCatalog => new ModuleCatalog(base_path('modules')));
        $this->app->bind(ModuleDependencyResolver::class, fn ($app): ModuleDependencyResolver => new ModuleDependencyResolver(
            $app->make(ModuleCatalog::class),
            $app['config']->get('insite_modules.enabled', []),
        ));
        $this->app->bind(ModuleDeploymentPreflight::class, fn ($app): ModuleDeploymentPreflight => new ModuleDeploymentPreflight($app->make(ModuleCatalog::class)));
        $this->app->bind(ModuleActivator::class, fn ($app): ModuleActivator => new ModuleActivator(
            $app,
            $app->make(ModuleCatalog::class),
            $app->make(ModuleDependencyResolver::class),
        ));
    }

    public function boot(): void
    {
        $this->app->make(ModuleActivator::class)->activate();
    }
}
