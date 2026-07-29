<?php

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function phaseTenUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $name) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            ['description' => $name],
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    return $user;
}

test('legacy people URLs redirect to portal equivalents', function () {
    $user = phaseTenUser(['access_people', 'read_people', 'update_people']);

    $this->actingAs($user)
        ->get('/people')
        ->assertRedirect(route('portal.people.index'));

    $this->actingAs($user)
        ->get('/people/42')
        ->assertRedirect(route('portal.people.show', 42));

    $this->actingAs($user)
        ->get('/people/42/edit')
        ->assertRedirect(route('portal.people.edit', 42));
});

test('legacy workforce indexes redirect to portal', function () {
    $user = phaseTenUser([
        'access_positions',
        'access_candidates',
        'portal_view_own_tickets',
    ]);

    $this->actingAs($user)->get('/positions')
        ->assertRedirect(route('portal.positions.index'));
    $this->actingAs($user)->get('/candidates')
        ->assertRedirect(route('portal.candidates.index'));
    $this->actingAs($user)->get('/job-titles')
        ->assertRedirect(route('portal.job-titles.index'));
    $this->actingAs($user)->get('/job-title-requirements')
        ->assertRedirect(route('portal.job-title-requirements.index'));
});

test('admin remains protected by view admin permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
