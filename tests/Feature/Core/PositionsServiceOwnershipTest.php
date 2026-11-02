<?php

use App\Contracts\Dashboard\WorkforceDashboardProvider;
use App\Contracts\Workforce\PersonPositionRelationships;
use App\Core\Modules\ModuleActivator;
use App\Services\Positions\LegacyPersonPositionRelationships;
use App\Services\ProjectManagerDashboardService;
use Insite\Modules\Positions\PositionsServiceProvider;

it('keeps legacy services usable in installations without the module selected', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions')
        ->and(app()->bound(PersonPositionRelationships::class))->toBeTrue()
        ->and(app(PersonPositionRelationships::class))->toBeInstanceOf(LegacyPersonPositionRelationships::class)
        ->and(app(WorkforceDashboardProvider::class))->toBeInstanceOf(ProjectManagerDashboardService::class);
});

it('lets the positions provider own workforce service bindings when activated', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);

    // A marker verifies that activation replaces the host dashboard binding.
    app()->bind(WorkforceDashboardProvider::class, \App\Services\Dashboard\EmptyWorkforceDashboardProvider::class);
    app()->offsetUnset(PersonPositionRelationships::class);

    expect(app(ModuleActivator::class)->activate())->toBe(['positions'])
        ->and(app()->getLoadedProviders()[PositionsServiceProvider::class] ?? false)->toBeTrue()
        ->and(app(PersonPositionRelationships::class))->toBeInstanceOf(LegacyPersonPositionRelationships::class)
        ->and(app(WorkforceDashboardProvider::class))->toBeInstanceOf(ProjectManagerDashboardService::class);
});

it('does not expose the workforce bindings when activated with legacy integration disabled', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', false);
    app()->offsetUnset(PersonPositionRelationships::class);

    expect(app(ModuleActivator::class)->activate())->toBe(['positions'])
        ->and(app()->bound(PersonPositionRelationships::class))->toBeFalse()
        ->and(app(WorkforceDashboardProvider::class))
            ->toBeInstanceOf(\App\Services\Dashboard\EmptyWorkforceDashboardProvider::class);
});
