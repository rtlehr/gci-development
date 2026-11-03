<?php

use App\Core\Modules\ModuleActivator;
use App\Http\Controllers\PositionCustomSkillTaskController as LegacyCustomRequirementsController;
use App\Models\Position;
use App\Models\User;
use App\Models\Person;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Insite\Modules\Positions\Http\Controllers\PositionCustomSkillTaskController as ModuleCustomRequirementsController;

uses(RefreshDatabase::class);

/** Use the real route middleware so Laravel performs implicit model binding. */
function moduleCustomRequirementsAuthorizedUser(): User
{
    $user = User::factory()->create();
    Person::factory()->forUser($user)->create();

    foreach (['access_portal', 'update_positions'] as $name) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            [
                'group_name' => 'Test',
                'label' => $name,
                'description' => $name,
                'is_system' => false,
                'is_locked' => false,
            ],
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    app(PermissionService::class)->clearUserPermissionCache($user->id);

    return $user;
}

it('preserves host custom requirement actions for unselected installations', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions');
    foreach (['storeSkill', 'destroySkill', 'storeTask', 'destroyTask'] as $method) {
        $routeName = match ($method) {
            'storeSkill' => 'portal.positions.custom-skills.store',
            'destroySkill' => 'portal.positions.custom-skills.destroy',
            'storeTask' => 'portal.positions.custom-tasks.store',
            'destroyTask' => 'portal.positions.custom-tasks.destroy',
        };
        expect(Route::getRoutes()->getByName($routeName)->getActionName())
            ->toBe(LegacyCustomRequirementsController::class.'@'.$method);
    }
});

it('routes custom requirement actions into the selected positions module', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();
    foreach ([
        'portal.positions.custom-skills.store' => 'storeSkill',
        'portal.positions.custom-skills.destroy' => 'destroySkill',
        'portal.positions.custom-tasks.store' => 'storeTask',
        'portal.positions.custom-tasks.destroy' => 'destroyTask',
    ] as $routeName => $method) {
        $route = Route::getRoutes()->getByName($routeName);
        expect($route->getActionName())->toBe(ModuleCustomRequirementsController::class.'@'.$method)
            ->and($route->gatherMiddleware())->toContain('insite-module:positions')
            ->and($route->gatherMiddleware())->toContain('permission:update_positions');
    }
});

it('creates and removes custom skills and tasks through the module controller', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();
    $this->actingAs(moduleCustomRequirementsAuthorizedUser());
    $position = Position::factory()->create();

    $this->post(route('portal.positions.custom-skills.store', ['position' => $position->id]), [
        'name' => 'Extracted skill',
        'description' => 'Module-owned skill',
        'requirement_type' => 'required',
    ])->assertRedirect()->assertSessionHas('success', 'Custom skill added.');
    $skill = $position->customSkills()->where('name', 'Extracted skill')->firstOrFail();
    $this->assertDatabaseHas($skill->getTable(), ['id' => $skill->id, 'position_id' => $position->id]);

    $this->post(route('portal.positions.custom-tasks.store', ['position' => $position->id]), [
        'name' => 'Extracted task',
        'description' => 'Module-owned task',
    ])->assertRedirect()->assertSessionHas('success', 'Custom task added.');
    $task = $position->customTasks()->where('name', 'Extracted task')->firstOrFail();
    $this->assertDatabaseHas($task->getTable(), ['id' => $task->id, 'position_id' => $position->id]);

    $this->delete(route('portal.positions.custom-skills.destroy', ['position' => $position->id, 'skill' => $skill->id]))
        ->assertRedirect()->assertSessionHas('success', 'Custom skill deleted.');
    $this->assertDatabaseMissing($skill->getTable(), ['id' => $skill->id]);

    $this->delete(route('portal.positions.custom-tasks.destroy', ['position' => $position->id, 'task' => $task->id]))
        ->assertRedirect()->assertSessionHas('success', 'Custom task deleted.');
    $this->assertDatabaseMissing($task->getTable(), ['id' => $task->id]);
});

it('rejects cross-position deletion in the module controller', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();
    $this->actingAs(moduleCustomRequirementsAuthorizedUser());
    $owner = Position::factory()->create();
    $other = Position::factory()->create();
    $skill = $owner->customSkills()->create(['name' => 'Protected skill', 'requirement_type' => 'desired']);
    $task = $owner->customTasks()->create(['name' => 'Protected task']);

    $this->delete(route('portal.positions.custom-skills.destroy', ['position' => $other->id, 'skill' => $skill->id]))
        ->assertNotFound();
    $this->delete(route('portal.positions.custom-tasks.destroy', ['position' => $other->id, 'task' => $task->id]))
        ->assertNotFound();
    $this->assertDatabaseHas($skill->getTable(), ['id' => $skill->id]);
    $this->assertDatabaseHas($task->getTable(), ['id' => $task->id]);
});
