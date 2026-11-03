<?php

use App\Core\Modules\ModuleActivator;
use App\Http\Controllers\PositionsController as HostPositionsController;
use Insite\Modules\Positions\Http\Controllers\PositionsController as ModulePositionsController;
use Illuminate\Support\Facades\Route;

it('retains host legacy Positions controller actions for unselected installations', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions');

    foreach (['positions.preferences.save', 'positions.export.csv', 'positions.store', 'positions.update', 'positions.destroy'] as $name) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull()
            ->and($route->getControllerClass())->toBe(HostPositionsController::class);
    }
});

it('moves every existing legacy Positions controller action to the selected module', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    $expected = [
        'positions.preferences.save' => ['savePreferences', 'permission:access_positions'],
        'positions.export.csv' => ['exportCsv', 'permission:access_positions'],
        'positions.store' => ['store', 'permission:create_positions'],
        'positions.update' => ['update', 'permission:update_positions'],
        'positions.destroy' => ['destroy', 'permission:delete_positions'],
    ];

    foreach ($expected as $name => [$method, $permission]) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull()
            ->and($route->getControllerClass())->toBe(ModulePositionsController::class)
            ->and($route->getActionMethod())->toBe($method)
            ->and($route->gatherMiddleware())->toContain('insite-module:positions')
            ->and($route->gatherMiddleware())->toContain('web')
            ->and($route->gatherMiddleware())->toContain($permission);
    }

    // Recruitment/Candidates ownership is intentionally still outside this extraction.
    expect(Route::getRoutes()->getByName('positions.candidates.store')->getControllerClass())
        ->toBe(\App\Http\Controllers\PositionCandidateController::class);
});

it('keeps selected legacy Positions controller actions guarded if the module becomes disabled', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();
    config()->set('insite_modules.enabled', []);

    $this->post('/positions/preferences')->assertNotFound();
});
