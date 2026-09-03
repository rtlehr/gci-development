<?php

use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\BootstrapOwnerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('identity.driver', 'adfs');
    config()->set('identity.middleware_in_testing', true);
    config()->set('identity.drivers.adfs.person_code_source', 'HTTP_PERSON_CODE');
    config()->set('bootstrap_login.enabled', true);
    config()->set('bootstrap_login.owner_person_code', '1111111');
    config()->set('bootstrap_login.enforce_in_testing', true);

    Route::middleware(['web', 'auth'])
        ->get('/_tests/bootstrap/protected', fn () => response()->json([
            'user_id' => auth()->id(),
        ]));
});

function createBootstrapOwner(): User
{
    $ownerRole = Role::query()->create([
        'name' => 'owner',
        'label' => 'Owner',
        'description' => 'Test owner role.',
    ]);

    $user = User::factory()->create([
        'email' => 'owner@localhost',
        'password' => Hash::make('Bootstrap-Test-123!'),
    ]);

    Person::query()->create([
        'user_id' => $user->id,
        'person_code' => '1111111',
        'first_name' => 'Initial',
        'last_name' => 'Owner',
        'email' => 'owner@localhost',
    ]);

    $user->roles()->sync([$ownerRole->id]);

    return $user;
}

it('redirects a protected request without adfs identity to bootstrap login only while setup is incomplete', function () {
    createBootstrapOwner();

    $this->get('/_tests/bootstrap/protected')
        ->assertRedirect(route('login'));

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/BootstrapLogin'));
});

it('allows only the designated owner to use bootstrap password authentication', function () {
    $owner = createBootstrapOwner();

    $response = $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'Bootstrap-Test-123!',
    ]);

    $response->assertRedirect(route('setup.index'));
    $this->assertAuthenticatedAs($owner);
    expect(session(BootstrapOwnerService::SESSION_KEY))->toBeTrue();
});

it('does not allow a non-owner account to authenticate through bootstrap login', function () {
    createBootstrapOwner();

    $other = User::factory()->create([
        'email' => 'other@example.test',
        'password' => Hash::make('Bootstrap-Test-123!'),
    ]);

    Person::query()->create([
        'user_id' => $other->id,
        'person_code' => '9000002',
        'first_name' => 'Other',
        'last_name' => 'User',
        'email' => $other->email,
    ]);

    $this->post(route('login.store'), [
        'email' => $other->email,
        'password' => 'Bootstrap-Test-123!',
    ]);

    $this->assertGuest();
});

it('allows a valid bootstrap owner session to survive a missing adfs claim before setup completes', function () {
    $owner = createBootstrapOwner();

    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'Bootstrap-Test-123!',
    ])->assertRedirect(route('setup.index'));

    $this->get('/_tests/bootstrap/protected')
        ->assertOk()
        ->assertJson(['user_id' => $owner->id]);
});

it('permanently disables bootstrap login after initial setup is completed', function () {
    $owner = createBootstrapOwner();

    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'Bootstrap-Test-123!',
    ])->assertRedirect(route('setup.index'));

    $this->post(route('setup.complete'))
        ->assertRedirect(route('home'));

    $this->assertGuest();

    $this->get(route('login'))->assertNotFound();

    $this->get('/_tests/bootstrap/protected')
        ->assertStatus(401)
        ->assertSee('Unable to identify your network account');

    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'Bootstrap-Test-123!',
    ]);

    $this->assertGuest();
});

it('lets a real adfs identity override a bootstrap session', function () {
    $owner = createBootstrapOwner();
    $adfsUser = User::factory()->create();

    Person::query()->create([
        'user_id' => $adfsUser->id,
        'person_code' => 'ADFS-200',
        'first_name' => 'ADFS',
        'last_name' => 'User',
        'email' => $adfsUser->email,
    ]);

    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'Bootstrap-Test-123!',
    ]);

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'ADFS-200'])
        ->get('/_tests/bootstrap/protected')
        ->assertOk()
        ->assertJson(['user_id' => $adfsUser->id]);

    $this->assertAuthenticatedAs($adfsUser);
    expect(session(BootstrapOwnerService::SESSION_KEY))->toBeNull();
});
