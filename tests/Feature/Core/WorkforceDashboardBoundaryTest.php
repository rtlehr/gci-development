<?php

use App\Contracts\Dashboard\WorkforceDashboardProvider;
use App\Models\Permission;
use App\Models\User;
use App\Services\Dashboard\EmptyWorkforceDashboardProvider;
use App\Services\ProjectManagerDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('legacy installations resolve the existing workforce dashboard provider', function () {
    config()->set('insite_modules.legacy_positions_enabled', true);

    expect(app(WorkforceDashboardProvider::class))
        ->toBeInstanceOf(ProjectManagerDashboardService::class);
});

test('core-only installations resolve a provider which never queries workforce models', function () {
    config()->set('insite_modules.legacy_positions_enabled', false);

    $provider = app(WorkforceDashboardProvider::class);

    expect($provider)->toBeInstanceOf(EmptyWorkforceDashboardProvider::class)
        ->and($provider->allPositionsForPmo()->isEmpty())->toBeTrue()
        ->and($provider->candidateOpportunitiesFor(User::factory()->make())->isEmpty())->toBeTrue()
        ->and($provider->staffingColumns())->toBe([])
        ->and($provider->staffingSummary(collect())['vacant'])->toBe(0);
});

test('core dashboard remains available when legacy workforce integration is disabled', function () {
    config()->set('insite_modules.legacy_positions_enabled', false);
    $user = User::factory()->create();

    foreach (['access_portal', 'portal_view_dashboard', 'portal_view_all_positions'] as $name) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            ['description' => $name],
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $this->actingAs($user)->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Dashboard')
            ->where('showPmoPositions', false)
            ->where('showProjectManagerPositions', false)
            ->where('showCandidateOpportunities', false)
            ->has('pmoPositions', 0)
            ->has('assignedPositions', 0)
            ->has('candidateOpportunities', 0)
            ->where('summary.assignedPositions', 0));
});
