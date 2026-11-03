<?php

use App\Core\Modules\ModuleActivator;
use App\Http\Controllers\Portal\PositionController as HostPortalPositionController;
use App\Http\Controllers\PositionCandidateController;
use Illuminate\Support\Facades\Route;
use Insite\Modules\Positions\Http\Controllers\Portal\PositionController as ModulePortalPositionController;

it('preserves the host Portal Positions controller when the module is not selected', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions');

    $actions = [
        'portal.positions.index' => 'index',
        'portal.positions.preferences.save' => 'savePreferences',
        'portal.positions.preferences.reset' => 'resetPreferences',
        'portal.positions.export.csv' => 'exportCsv',
        'portal.positions.create' => 'create',
        'portal.positions.store' => 'store',
        'portal.positions.show' => 'show',
        'portal.positions.edit' => 'edit',
        'portal.positions.update' => 'update',
        'portal.positions.destroy' => 'destroy',
    ];

    foreach ($actions as $name => $method) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull()
            ->and($route->getControllerClass())->toBe(HostPortalPositionController::class)
            ->and($route->getActionMethod())->toBe($method)
            ->and($route->gatherMiddleware())->not->toContain('insite-module:positions');
    }
});

it('routes all ten Portal Positions controller actions to the activated module', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    $actions = [
        'portal.positions.index' => ['index', 'permission:portal_view_positions'],
        'portal.positions.preferences.save' => ['savePreferences', 'permission:portal_view_positions'],
        'portal.positions.preferences.reset' => ['resetPreferences', 'permission:portal_view_positions'],
        'portal.positions.export.csv' => ['exportCsv', 'permission:portal_view_positions'],
        'portal.positions.create' => ['create', 'permission:create_positions'],
        'portal.positions.store' => ['store', 'permission:create_positions'],
        'portal.positions.show' => ['show', 'permission:portal_view_positions'],
        'portal.positions.edit' => ['edit', 'permission:update_positions'],
        'portal.positions.update' => ['update', 'permission:update_positions'],
        'portal.positions.destroy' => ['destroy', 'permission:delete_positions'],
    ];

    foreach ($actions as $name => [$method, $permission]) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull()
            ->and($route->getControllerClass())->toBe(ModulePortalPositionController::class)
            ->and($route->getActionMethod())->toBe($method)
            ->and($route->gatherMiddleware())->toContain('insite-module:positions')
            ->and($route->gatherMiddleware())->toContain('web')
            ->and($route->gatherMiddleware())->toContain('auth')
            ->and($route->gatherMiddleware())->toContain('permission:access_portal')
            ->and($route->gatherMiddleware())->toContain($permission);
    }

    // The candidate integration remains owned by its existing controller.
    expect(Route::getRoutes()->getByName('portal.positions.candidates.store')->getControllerClass())
        ->toBe(PositionCandidateController::class);
});

it('keeps selected Portal Positions routes inaccessible after the module becomes unselected', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();
    config()->set('insite_modules.enabled', []);

    $this->get('/portal/positions')->assertNotFound();
});
