<?php

use App\Models\Permission;
use App\Models\Person;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function dashboardRoleUser(string $roleName, array $permissions): User
{
    $user = User::factory()->create();

    Person::factory()
        ->forUser($user)
        ->create();

    $role = Role::query()->create([
        'name' => $roleName,
        'label' => str($roleName)->replace('_', ' ')->title()->toString(),
        'description' => 'Dashboard test role.',
    ]);

    $user->roles()->attach($role);

    foreach ($permissions as $name) {
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

    return $user;
}

test('pmo portal dashboard receives every position and not the project manager card', function () {
    $pmo = dashboardRoleUser('pmo', [
        'access_portal',
        'portal_view_dashboard',
        'portal_view_positions',
        'update_positions',
    ]);

    Position::factory()->count(3)->create();

    $this->actingAs($pmo)
        ->get('/portal/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Dashboard')
            ->where('showPmoPositions', true)
            ->where('showProjectManagerPositions', false)
            ->has('pmoPositions', 3)
            ->has('assignedPositions', 0)
            ->where('summary.assignedPositions', 3)
            ->where('summary.positionsLabel', 'all positions'));
});

test('project manager portal dashboard receives only assigned positions', function () {
    $projectManager = dashboardRoleUser('project_manager', [
        'access_portal',
        'portal_view_dashboard',
        'portal_view_positions',
    ]);

    Position::factory()->create([
        'project_manager_user_id' => $projectManager->id,
    ]);

    Position::factory()->count(2)->create();

    $this->actingAs($projectManager)
        ->get('/portal/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Dashboard')
            ->where('showPmoPositions', false)
            ->where('showProjectManagerPositions', true)
            ->has('assignedPositions', 1)
            ->has('pmoPositions', 0)
            ->where('summary.assignedPositions', 1)
            ->where('summary.positionsLabel', 'assigned positions'));
});
