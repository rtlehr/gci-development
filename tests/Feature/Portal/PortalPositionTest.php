<?php

use App\Models\JobTitle;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function portalCrudPositionUser(array $permissions): User
{
    $user = User::factory()->create();
    Person::factory()->forUser($user)->create();

    $permissions = array_values(array_unique([
        'access_portal',
        'portal_view_positions',
        'portal_view_all_positions',
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

it('renders the full portal positions list for authorized users', function () {
    $user = portalCrudPositionUser(['access_positions', 'read_positions']);
    Position::factory()->create(['position_code' => 'PORTAL-CRUD-001']);

    $this->actingAs($user)
        ->get(route('portal.positions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Positions/Index')
            ->has('positions.data', 1)
            ->has('columns')
            ->has('visibleColumns')
            ->has('columnOrder')
        );
});

it('allows authorized users to open the portal create position page', function () {
    $user = portalCrudPositionUser(['access_positions', 'create_positions']);

    $this->actingAs($user)
        ->get(route('portal.positions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Positions/Create')
            ->has('organizations')
            ->has('jobTitles')
            ->has('projectManagers')
        );
});

it('allows authorized users to create a position from the portal', function () {
    $user = portalCrudPositionUser(['access_positions', 'create_positions', 'update_positions']);
    $jobTitle = JobTitle::query()->create([
        'name' => 'Portal Systems Engineer',
        'slug' => 'portal-systems-engineer',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('portal.positions.store'), [
            'position_code' => 'PORTAL-NEW-001',
            'status' => 'Open',
            'job_title_id' => $jobTitle->id,
            'level' => 2,
            'is_essential' => false,
            'travel_required' => false,
            'high_risk_role' => false,
            'request_to_close' => false,
        ])
        ->assertRedirect(route('portal.positions.edit', Position::query()->where('position_code', 'PORTAL-NEW-001')->value('id')));

    $this->assertDatabaseHas('positions', ['position_code' => 'PORTAL-NEW-001']);
});

it('renders full position detail and edit pages inside the portal shell', function () {
    $user = portalCrudPositionUser(['access_positions', 'read_positions', 'update_positions']);
    $position = Position::factory()->create([
        'funding_info' => 'Authorized operational funding information.',
        'notes' => 'Authorized operational notes.',
    ]);

    $this->actingAs($user)
        ->get(route('portal.positions.show', $position->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Positions/Show')
            ->where('position.id', $position->id)
            ->where('position.funding_info', 'Authorized operational funding information.')
        );

    $this->actingAs($user)
        ->get(route('portal.positions.edit', $position->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Positions/Edit')
            ->where('position.id', $position->id)
            ->has('jobTitleSkills')
            ->has('jobTitleTasks')
            ->has('positionCandidates')
        );
});

it('protects portal position actions with the existing granular permissions', function () {
    $user = User::factory()->create();
    Person::factory()->forUser($user)->create();
    $position = Position::factory()->create();

    $this->actingAs($user)->get(route('portal.positions.index'))->assertForbidden();
    $this->actingAs($user)->get(route('portal.positions.create'))->assertForbidden();
    $this->actingAs($user)->get(route('portal.positions.edit', $position->id))->assertForbidden();
    $this->actingAs($user)->delete(route('portal.positions.destroy', $position->id))->assertForbidden();
});
