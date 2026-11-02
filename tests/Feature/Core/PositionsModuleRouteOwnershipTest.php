<?php

use App\Core\Modules\ModuleActivator;
use Illuminate\Support\Facades\Route;

it('preserves legacy position routes while positions remains unselected', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions');
    $route = Route::getRoutes()->getByName('positions.index');
    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('positions')
        ->and($route->gatherMiddleware())->not->toContain('insite-module:positions');
});

it('transfers the existing legacy URLs and permissions to the selected module', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    $routes = [
        'positions.index' => ['GET', 'positions', null],
        'positions.create' => ['GET', 'positions/create', null],
        'positions.show' => ['GET', 'positions/{id}', null],
        'positions.preferences.save' => ['POST', 'positions/preferences', 'permission:access_positions'],
        'positions.export.csv' => ['POST', 'positions/export/csv', 'permission:access_positions'],
        'positions.store' => ['POST', 'positions', 'permission:create_positions'],
        'positions.update' => ['PUT', 'positions/{id}', 'permission:update_positions'],
        'positions.destroy' => ['DELETE', 'positions/{id}', 'permission:delete_positions'],
        'positions.candidates.store' => ['POST', 'positions/{id}/candidates', 'permission:create_candidates'],
    ];

    foreach ($routes as $name => [$method, $uri, $permission]) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull()
            ->and($route->uri())->toBe($uri)
            ->and($route->methods())->toContain($method)
            ->and($route->gatherMiddleware())->toContain('insite-module:positions');
        if ($permission !== null) {
            expect($route->gatherMiddleware())->toContain($permission);
        }
    }
});

it('fails closed when a registered module owned position route is disabled', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();
    config()->set('insite_modules.enabled', []);

    $this->get('/positions')->assertNotFound();
});
