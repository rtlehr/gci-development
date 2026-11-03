<?php

use App\Contracts\Positions\PositionOperations;
use App\Core\Modules\ModuleActivator;
use App\Http\Controllers\PositionsController;
use App\Http\Controllers\Portal\PositionController;
use App\Services\PositionService;
use Insite\Modules\Positions\Services\ModulePositionService;

it('keeps a host fallback behind the Positions operations interface by default', function () {
    expect(app()->bound(PositionOperations::class))->toBeTrue()
        ->and(app(PositionOperations::class))->toBeInstanceOf(PositionService::class);
});

it('activates an independent module service without inheriting the legacy concrete class', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    expect(app(PositionOperations::class))->toBeInstanceOf(ModulePositionService::class)
        ->not->toBeInstanceOf(PositionService::class);
});

it('makes both position controllers request the contract in their write actions', function () {
    foreach ([PositionsController::class, PositionController::class] as $controller) {
        foreach (['store', 'update'] as $method) {
            $parameters = (new ReflectionMethod($controller, $method))->getParameters();
            $types = array_map(static fn (ReflectionParameter $p) => $p->getType()?->getName(), $parameters);
            expect($types)->toContain(PositionOperations::class)
                ->not->toContain(PositionService::class);
        }
    }
});
