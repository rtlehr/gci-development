<?php

use App\Models\Permission;
use App\Models\Person;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function portalCrudPeopleUser(array $permissions): User
{
    $user = User::factory()->create();
    Person::factory()->forUser($user)->create();

    $permissions = array_values(array_unique([
        'access_portal',
        'portal_view_directory',
        ...$permissions,
    ]));

    $permissionIds = Permission::query()
        ->whereIn('name', $permissions)
        ->pluck('id');

    $user->permissions()->sync($permissionIds);
    app(PermissionService::class)->clearUserPermissionCache($user->id);

    return $user;
}

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('renders the portal people list with list configuration', function () {
    $user = portalCrudPeopleUser(['access_people', 'read_people']);
    Person::factory()->create(['person_code' => 'PORTAL-PERSON-001']);

    $this->actingAs($user)
        ->get(route('portal.people.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/People/Index')
            ->has('people.data', 2)
            ->has('columns')
            ->has('visibleColumns')
            ->has('columnOrder')
        );
});

it('allows an authorized user to open the portal create person page', function () {
    $user = portalCrudPeopleUser(['access_people', 'create_people']);

    $this->actingAs($user)
        ->get(route('portal.people.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/People/Create')
            ->has('roles')
            ->has('groups')
            ->has('teams')
        );
});

it('allows an authorized user to create a person in the portal', function () {
    $user = portalCrudPeopleUser(['access_people', 'create_people', 'read_people']);

    $response = $this->actingAs($user)
        ->post(route('portal.people.store'), [
            'person_code' => 'PORTAL-NEW-PERSON',
            'first_name' => 'Portal',
            'last_name' => 'Person',
            'email' => 'portal.person@example.test',
            'employment_status' => 'active',
            'phone_numbers' => [],
            'addresses' => [],
            'group_ids' => [],
            'team_ids' => [],
            'role_ids' => [],
        ]);

    $person = Person::query()->where('person_code', 'PORTAL-NEW-PERSON')->firstOrFail();

    $response->assertRedirect(route('portal.people.show', $person->id));
    $this->assertNotNull($person->user_id);
});

it('renders portal person detail and edit pages', function () {
    $user = portalCrudPeopleUser(['access_people', 'read_people', 'update_people']);
    $person = Person::factory()->create();

    $this->actingAs($user)
        ->get(route('portal.people.show', $person->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/People/Show')
            ->where('person.id', $person->id)
        );

    $this->actingAs($user)
        ->get(route('portal.people.edit', $person->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/People/Edit')
            ->where('person.id', $person->id)
            ->has('roles')
            ->has('groups')
            ->has('teams')
        );
});

it('protects portal people actions with existing granular permissions', function () {
    $user = User::factory()->create();
    Person::factory()->forUser($user)->create();
    $person = Person::factory()->create();

    $this->actingAs($user)->get(route('portal.people.index'))->assertForbidden();
    $this->actingAs($user)->get(route('portal.people.create'))->assertForbidden();
    $this->actingAs($user)->get(route('portal.people.edit', $person->id))->assertForbidden();
    $this->actingAs($user)->delete(route('portal.people.destroy', $person->id))->assertForbidden();
});
