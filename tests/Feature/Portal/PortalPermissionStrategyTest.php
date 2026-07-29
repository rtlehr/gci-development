<?php

use App\Models\Permission;
use App\Models\Person;
use App\Models\User;
use App\Services\CurrentUserContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function permissionStrategyUser(array $permissions): User
{
    $user = User::factory()->create();

    Person::factory()
        ->forUser($user)
        ->create();

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

test('access portal is required for every portal route', function () {
    $user = permissionStrategyUser(['portal_view_dashboard']);

    $this->actingAs($user)
        ->get('/portal/dashboard')
        ->assertForbidden();
});

test('portal dashboard uses portal permissions instead of view admin', function () {
    $user = permissionStrategyUser([
        'access_portal',
        'portal_view_dashboard',
    ]);

    $this->actingAs($user)
        ->get('/portal/dashboard')
        ->assertOk();
});

test('portal directory visibility does not require access people', function () {
    $user = permissionStrategyUser([
        'access_portal',
        'portal_view_directory',
    ]);

    $this->actingAs($user)
        ->get('/portal/people')
        ->assertOk();
});

test('portal positions visibility does not require access positions', function () {
    $user = permissionStrategyUser([
        'access_portal',
        'portal_view_positions',
    ]);

    $this->actingAs($user)
        ->get('/portal/positions')
        ->assertOk();
});

test('portal create actions still require existing action permissions', function () {
    $viewer = permissionStrategyUser([
        'access_portal',
        'portal_view_positions',
    ]);

    $this->actingAs($viewer)
        ->get('/portal/positions/create')
        ->assertForbidden();

    app(CurrentUserContext::class)->forget();

    $creator = permissionStrategyUser([
        'access_portal',
        'portal_view_positions',
        'create_positions',
    ]);

    $this->actingAs($creator)
        ->get('/portal/positions/create')
        ->assertOk();
});
