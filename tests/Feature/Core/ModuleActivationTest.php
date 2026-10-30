<?php

use App\Core\Modules\ModuleActivator;
use App\Core\Modules\ModuleCatalog;
use App\Core\Modules\ModuleDependencyResolver;
use Illuminate\Support\Facades\Route;

// Test the real module package without changing the application's default enablement.
it('does not register the demonstration route when the module is disabled', function () {
    expect(Route::has('insite.modules.demo.health'))->toBeFalse();
    $this->get('/_insite/modules/demo/health')->assertNotFound();
});

it('registers an enabled module provider and serves its route', function () {
    config()->set('insite_modules.enabled', ['demo']);
    // The resolver is bound, not a singleton, so it sees the new selection.
    $activated = app(ModuleActivator::class)->activate();
    $provider = \Insite\Modules\Demo\DemoServiceProvider::class;
    $diagnostics = [
        'activated' => $activated,
        'provider_registered' => app()->getLoadedProviders()[$provider] ?? false,
        'application_booted' => app()->isBooted(),
        'routes_cached' => app()->routesAreCached(),
        'demo_route_file_exists' => is_file(base_path('modules/demo/routes/web.php')),
        'route_count' => count(Route::getRoutes()),
        'demo_route_registered' => Route::has('insite.modules.demo.health'),
    ];
    expect(Route::has('insite.modules.demo.health'))
        ->toBeTrue('Activation diagnostics: '.json_encode($diagnostics));
    $this->get('/_insite/modules/demo/health')->assertOk()->assertJson(['module' => 'demo', 'active' => true]);
});

it('blocks previously registered routes after a module is disabled', function () {
    config()->set('insite_modules.enabled', ['demo']);
    app(ModuleActivator::class)->activate();
    config()->set('insite_modules.enabled', []);
    $this->get('/_insite/modules/demo/health')->assertNotFound();
});

it('does not execute an unselected module provider', function () {
    expect(app()->getLoadedProviders()[\Insite\Modules\Demo\DemoServiceProvider::class] ?? false)->toBeFalse();
});

it('rejects invalid selected modules before any provider is activated', function () {
    $resolver = new ModuleDependencyResolver(app(ModuleCatalog::class), ['demo', 'missing']);
    $activator = new ModuleActivator(app(), app(ModuleCatalog::class), $resolver);
    expect(fn () => $activator->activate())->toThrow(InvalidArgumentException::class, 'not installed');
    expect(app()->getLoadedProviders()[\Insite\Modules\Demo\DemoServiceProvider::class] ?? false)->toBeFalse();
});
