<?php

use App\Core\Modules\ModuleActivator;
use App\Http\Controllers\PositionCandidateController as HostPositionCandidateController;
use App\Models\Candidate;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use App\Models\Workflow;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Insite\Modules\Positions\Http\Controllers\PositionCandidateController as ModulePositionCandidateController;

uses(RefreshDatabase::class);

it('keeps the candidate-add host fallback when Positions is unselected', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions');

    foreach (['positions.candidates.store', 'portal.positions.candidates.store'] as $name) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull()
            ->and($route->getControllerClass())->toBe(HostPositionCandidateController::class)
            ->and($route->getActionMethod())->toBe('store');
    }
});

it('assigns both candidate-add routes to the activated Positions module with their existing permissions', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    foreach (['positions.candidates.store', 'portal.positions.candidates.store'] as $name) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull()
            ->and($route->getControllerClass())->toBe(ModulePositionCandidateController::class)
            ->and($route->getActionMethod())->toBe('store')
            ->and($route->gatherMiddleware())->toContain('insite-module:positions')
            ->and($route->gatherMiddleware())->toContain('permission:create_candidates');
    }
});

it('creates a candidate through the selected module and preserves the legacy redirect', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    $user = User::factory()->create();
    $permission = Permission::query()->firstOrCreate(['name' => 'create_candidates'], [
        'group_name' => 'Test', 'label' => 'Create candidates',
        'description' => 'Create candidates', 'is_system' => false, 'is_locked' => false,
    ]);
    $user->permissions()->syncWithoutDetaching([$permission->id]);
    app(PermissionService::class)->clearUserPermissionCache($user->id);
    $this->actingAs($user);

    $position = Position::factory()->create();
    $person = Person::factory()->create();
    $workflow = Workflow::query()->create([
        'name' => 'Module Candidate Workflow',
        'code' => 'module-candidate-workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $this->post(route('positions.candidates.store', $position->id), [
        'person_id' => $person->id,
    ])->assertRedirect(route('positions.edit', [
        'id' => $position->id, 'section' => 'candidates',
    ]))->assertSessionHas('success', 'Candidate added to the position successfully.');

    $this->assertDatabaseHas('candidates', [
        'person_id' => $person->id,
        'position_id' => $position->id,
        'workflow_id' => $workflow->id,
        'status' => 'submitted',
    ]);

    $this->from(route('positions.edit', $position->id))
        ->post(route('positions.candidates.store', $position->id), ['person_id' => $person->id])
        ->assertSessionHasErrors('person_id');
    expect(Candidate::query()->count())->toBe(1);
});

it('rejects a duplicate candidate through the selected Portal route without creating another row', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    $user = User::factory()->create();
    $permission = Permission::query()->firstOrCreate(['name' => 'create_candidates'], [
        'group_name' => 'Test', 'label' => 'Create candidates',
        'description' => 'Create candidates', 'is_system' => false, 'is_locked' => false,
    ]);
    $portalAccess = Permission::query()->firstOrCreate(['name' => 'access_portal'], [
        'group_name' => 'Test', 'label' => 'Access portal',
        'description' => 'Access portal', 'is_system' => false, 'is_locked' => false,
    ]);
    $user->permissions()->syncWithoutDetaching([$permission->id, $portalAccess->id]);
    app(PermissionService::class)->clearUserPermissionCache($user->id);
    $this->actingAs($user);

    $position = Position::factory()->create();
    $person = Person::factory()->create();
    $workflow = Workflow::query()->create([
        'name' => 'Portal Candidate Workflow',
        'code' => 'portal-candidate-duplicate-test',
        'is_active' => true,
        'is_primary' => true,
    ]);

    Candidate::query()->create([
        'person_id' => $person->id,
        'position_id' => $position->id,
        'workflow_id' => $workflow->id,
        'status' => 'submitted',
    ]);

    $this->from('/portal/positions/'.$position->id.'/edit?section=candidates')
        ->post(route('portal.positions.candidates.store', $position->id), [
            'person_id' => $person->id,
            'workflow_id' => $workflow->id,
        ])
        ->assertSessionHasErrors('person_id');

    expect(Candidate::query()
        ->where('person_id', $person->id)
        ->where('position_id', $position->id)
        ->count())->toBe(1);
});

it('blocks candidate-add requests when a previously active module becomes unselected', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();
    config()->set('insite_modules.enabled', []);

    $this->post('/positions/123/candidates')->assertNotFound();
    $this->post('/portal/positions/123/candidates')->assertNotFound();
});
