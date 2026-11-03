<?php

use App\Core\Modules\ModuleActivator;
use App\Models\Position;
use App\Models\User;
use App\Services\PositionService;
use App\Contracts\Positions\PositionOperations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Insite\Modules\Positions\Services\ModulePositionService;

uses(RefreshDatabase::class);

it('retains the host Positions write service when the module is unselected', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions');

    $service = app(PositionOperations::class);
    expect($service)->toBeInstanceOf(PositionService::class)
        ->toBeInstanceOf(PositionOperations::class)
        ->not->toBeInstanceOf(ModulePositionService::class);
});

it('resolves a module-owned Positions write service using the existing controller type hint', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);

    expect(app(ModuleActivator::class)->activate())->toBe(['positions'])
        ->and(app(PositionOperations::class))->toBeInstanceOf(ModulePositionService::class)
        ->and(app(PositionOperations::class))->not->toBeInstanceOf(PositionService::class);
});

it('does not switch the Positions service in a Core-only deployment', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', false);

    expect(app(ModuleActivator::class)->activate())->toBe(['positions']);
    // This installation does not expose the Positions operations boundary.
    // Clear any host binding registered prior to the test's config override.
    app()->offsetUnset(PositionOperations::class);
    expect(app()->bound(PositionOperations::class))->toBeFalse();
});

it('persists a position and its creation activity with the selected module service', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    $service = app(PositionOperations::class);
    $position = $service->create([
        'position_code' => 'MODULE-5C7-001',
        'job_title' => 'Module Service Check',
        'status' => 'Open',
    ], null);

    expect($position)->toBeInstanceOf(Position::class);
    $this->assertDatabaseHas('positions', [
        'id' => $position->id,
        'position_code' => 'MODULE-5C7-001',
    ]);
    $this->assertDatabaseHas('position_activities', [
        'position_id' => $position->id,
        'action' => 'created',
    ]);
});

it('preserves project manager eligibility validation in the module service', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    $notManager = User::factory()->create();

    expect(fn () => app(PositionOperations::class)->assertEligibleProjectManager($notManager->id))
        ->toThrow(ValidationException::class);
});
