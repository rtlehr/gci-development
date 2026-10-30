<?php

namespace App\Providers;

use App\Core\Modules\ModuleCatalog;
use Illuminate\Support\ServiceProvider;

final class ModuleCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleCatalog::class, fn (): ModuleCatalog => new ModuleCatalog(base_path('modules')));
    }
}
