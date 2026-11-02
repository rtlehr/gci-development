<?php

use App\Core\Modules\ModuleActivator;
use App\Core\Modules\ModuleCatalog;
use App\Core\Modules\ModuleDeploymentPreflight;
use Insite\Modules\Positions\PositionsServiceProvider;

it('discovers the positions module and validates its declared core dependency', function () {
    $module = app(ModuleCatalog::class)->find('positions');

    expect($module)->not->toBeNull()
        ->and($module['id'])->toBe('positions')
        ->and($module['version'])->toBe('1.0.0')
        ->and($module['core'])->toBe('^1.0.0')
        ->and($module['requires'])->toBe([])
        ->and($module['provider'])->toBe(PositionsServiceProvider::class);
});

it('does not register the positions provider when the module is not selected', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions')
        ->and(app()->getLoadedProviders()[PositionsServiceProvider::class] ?? false)->toBeFalse();
});

it('activates the positions shell without registering module-owned routes', function () {
    config()->set('insite_modules.enabled', ['positions']);

    $activated = app(ModuleActivator::class)->activate();

    expect($activated)->toBe(['positions'])
        ->and(app()->getLoadedProviders()[PositionsServiceProvider::class] ?? false)->toBeTrue();

    // Activation of the shell must not migrate legacy routes or data ownership.
    expect(is_file(base_path('modules/positions/routes/web.php')))->toBeTrue();
});

it('rejects an incompatible core version during deployment preflight', function () {
    expect(fn () => app(ModuleDeploymentPreflight::class)->inspect(['positions'], '2.0.0'))
        ->toThrow(InvalidArgumentException::class);

    $plan = app(ModuleDeploymentPreflight::class)->inspect(['positions'], '1.0.0');
    expect($plan['order'])->toBe(['positions'])
        ->and($plan['migration_files']['positions'])->toBe(0);
});
