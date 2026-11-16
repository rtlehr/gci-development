<?php

use App\Contracts\Dashboard\WorkforceDashboardProvider;
use App\Core\Modules\ModuleActivator;
use App\Models\Position;
use App\Models\User;
use App\Services\ProjectManagerDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insite\Modules\Positions\Services\ModuleWorkforceDashboardService;

uses(RefreshDatabase::class);

it('implements the Core workforce dashboard contract without inheriting the legacy service', function () {
    $provider = app(ModuleWorkforceDashboardService::class);

    expect($provider)->toBeInstanceOf(WorkforceDashboardProvider::class)
        ->and($provider)->not->toBeInstanceOf(ProjectManagerDashboardService::class)
        ->and($provider->staffingColumns())->toBe(app(ProjectManagerDashboardService::class)->staffingColumns())
        ->and($provider->staffingSummary(collect()))->toBe(app(ProjectManagerDashboardService::class)->staffingSummary(collect()));
});

it('activates the module-owned dashboard provider and retains legacy response compatibility', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    $user = User::factory()->create();
    Position::factory()->create(['project_manager_user_id' => $user->id]);
    Position::factory()->create();

    $module = app(WorkforceDashboardProvider::class);
    $legacy = app(ProjectManagerDashboardService::class);

    expect($module)->toBeInstanceOf(ModuleWorkforceDashboardService::class)
        ->and($module->positionsFor($user)->toArray())->toBe($legacy->positionsFor($user)->toArray())
        ->and($module->allPositionsForPmo()->toArray())->toBe($legacy->allPositionsForPmo()->toArray())
        ->and($module->staffingColumns())->toBe($legacy->staffingColumns())
        ->and($module->staffingSummary($module->allPositionsForPmo()))->toBe($legacy->staffingSummary($legacy->allPositionsForPmo()))
        ->and($module->candidateOpportunitiesFor($user)->toArray())->toBe($legacy->candidateOpportunitiesFor($user)->toArray());
});

it('preserves the host dashboard binding when Positions is not selected', function () {
    config()->set('insite_modules.enabled', []);
    config()->set('insite_modules.legacy_positions_enabled', true);

    expect(app(WorkforceDashboardProvider::class))->toBeInstanceOf(ProjectManagerDashboardService::class);
});
