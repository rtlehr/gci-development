<?php

use App\Core\Modules\ModuleActivator;
use Illuminate\Support\Facades\Route;

/**
 * Route registration remains backward compatible for default installations,
 * and selected-module routes have an additional fail-closed guard.
 */
it('keeps existing portal position routes host-owned when the module is unselected', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions');

    $route = Route::getRoutes()->getByName('portal.positions.index');
    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('portal/positions')
        ->and($route->gatherMiddleware())->toContain('auth')
        ->and($route->gatherMiddleware())->toContain('permission:access_portal')
        ->and($route->gatherMiddleware())->toContain('permission:portal_view_positions')
        ->and($route->gatherMiddleware())->not->toContain('insite-module:positions');
});

it('registers all fifteen portal position routes from the activated module', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    $expected = [
        'portal.positions.index' => ['GET', 'portal/positions', 'permission:portal_view_positions'],
        'portal.positions.preferences.save' => ['POST', 'portal/positions/preferences', 'permission:portal_view_positions'],
        'portal.positions.preferences.reset' => ['DELETE', 'portal/positions/preferences', 'permission:portal_view_positions'],
        'portal.positions.export.csv' => ['POST', 'portal/positions/export/csv', 'permission:portal_view_positions'],
        'portal.positions.create' => ['GET', 'portal/positions/create', 'permission:create_positions'],
        'portal.positions.store' => ['POST', 'portal/positions', 'permission:create_positions'],
        'portal.positions.show' => ['GET', 'portal/positions/{id}', 'permission:portal_view_positions'],
        'portal.positions.edit' => ['GET', 'portal/positions/{id}/edit', 'permission:update_positions'],
        'portal.positions.update' => ['PUT', 'portal/positions/{id}', 'permission:update_positions'],
        'portal.positions.destroy' => ['DELETE', 'portal/positions/{id}', 'permission:delete_positions'],
        'portal.positions.candidates.store' => ['POST', 'portal/positions/{id}/candidates', 'permission:create_candidates'],
        'portal.positions.custom-skills.store' => ['POST', 'portal/positions/{position}/custom-skills', 'permission:update_positions'],
        'portal.positions.custom-skills.destroy' => ['DELETE', 'portal/positions/{position}/custom-skills/{skill}', 'permission:update_positions'],
        'portal.positions.custom-tasks.store' => ['POST', 'portal/positions/{position}/custom-tasks', 'permission:update_positions'],
        'portal.positions.custom-tasks.destroy' => ['DELETE', 'portal/positions/{position}/custom-tasks/{task}', 'permission:update_positions'],
    ];

    foreach ($expected as $name => [$method, $uri, $permission]) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull()
            ->and($route->uri())->toBe($uri)
            ->and($route->methods())->toContain($method)
            ->and($route->gatherMiddleware())->toContain('auth')
            ->and($route->gatherMiddleware())->toContain('permission:access_portal')
            ->and($route->gatherMiddleware())->toContain($permission)
            ->and($route->gatherMiddleware())->toContain('insite-module:positions')
            ->and($route->gatherMiddleware())->toContain('web');
    }
});

it('fails closed on a previously registered portal position route when positions is disabled', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();
    config()->set('insite_modules.enabled', []);

    $this->get('/portal/positions')->assertNotFound();
});
