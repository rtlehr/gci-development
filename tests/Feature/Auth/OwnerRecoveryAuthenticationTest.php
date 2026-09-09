<?php

use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\OwnerRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('identity.driver', 'adfs');
    config()->set('identity.middleware_in_testing', true);
    config()->set('identity.drivers.adfs.person_code_source', 'HTTP_PERSON_CODE');
    config()->set('owner_recovery.enabled', true);
    config()->set('owner_recovery.owner_person_code', '1111111');
    config()->set('owner_recovery.session_minutes', 30);

    Route::middleware(['web', 'auth'])
        ->get('/_tests/owner-recovery/protected', fn () => response()->json([
            'user_id' => auth()->id(),
        ]));
});

function createRecoveryOwner(): User
{
    $ownerRole = Role::query()->create([
        'name' => 'owner',
        'label' => 'Owner',
        'description' => 'Test owner role.',
    ]);

    $owner = User::factory()->create([
        'email' => 'owner@example.test',
        'password' => Hash::make('Recovery-Password-123!'),
    ]);

    Person::query()->create([
        'user_id' => $owner->id,
        'person_code' => '1111111',
        'first_name' => 'Recovery',
        'last_name' => 'Owner',
        'email' => $owner->email,
    ]);

    $owner->roles()->sync([$ownerRole->id]);

    return $owner;
}


it('redirects an unknown adfs user from a public page to owner recovery', function () {
    createRecoveryOwner();

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-ADFS-PUBLIC'])
        ->get('/')
        ->assertRedirect(route('owner-recovery.show', ['reason' => 'unknown']));
});

it('redirects an unknown adfs user to the access denied owner recovery screen when enabled', function () {
    createRecoveryOwner();

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-ADFS-USER'])
        ->get('/_tests/owner-recovery/protected')
        ->assertRedirect(route('owner-recovery.show', ['reason' => 'unknown']));

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-ADFS-USER'])
        ->get(route('owner-recovery.show', ['reason' => 'unknown']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/OwnerRecovery')
            ->where('enabled', true));
});


it('allows the owner recovery page to render for an unknown adfs identity without a redirect loop', function () {
    createRecoveryOwner();

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-ADFS-PUBLIC'])
        ->get(route('owner-recovery.show', ['reason' => 'unknown']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/OwnerRecovery')
            ->where('enabled', true));
});

it('allows only the designated owner credentials to start recovery access', function () {
    $owner = createRecoveryOwner();

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-ADFS-USER'])
        ->post(route('owner-recovery.store'), [
            'email' => $owner->email,
            'password' => 'Recovery-Password-123!',
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertAuthenticatedAs($owner);
    expect(session(OwnerRecoveryService::SESSION_KEY))->not->toBeNull();
});

it('rejects incorrect owner recovery credentials', function () {
    $owner = createRecoveryOwner();

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-ADFS-USER'])
        ->post(route('owner-recovery.store'), [
            'email' => $owner->email,
            'password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('keeps the owner authenticated while the upstream adfs user remains invalid', function () {
    $owner = createRecoveryOwner();

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-ADFS-USER'])
        ->post(route('owner-recovery.store'), [
            'email' => $owner->email,
            'password' => 'Recovery-Password-123!',
        ]);

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-ADFS-USER'])
        ->get('/_tests/owner-recovery/protected')
        ->assertOk()
        ->assertJson(['user_id' => $owner->id]);

    $this->assertAuthenticatedAs($owner);
});

it('lets a valid mapped adfs identity override an owner recovery session', function () {
    $owner = createRecoveryOwner();
    $adfsUser = User::factory()->create();

    Person::query()->create([
        'user_id' => $adfsUser->id,
        'person_code' => 'ADFS-VALID-200',
        'first_name' => 'Valid',
        'last_name' => 'ADFS',
        'email' => $adfsUser->email,
    ]);

    $this->actingAs($owner)
        ->withSession([OwnerRecoveryService::SESSION_KEY => now()->timestamp])
        ->withServerVariables(['HTTP_PERSON_CODE' => 'ADFS-VALID-200'])
        ->get('/_tests/owner-recovery/protected')
        ->assertOk()
        ->assertJson(['user_id' => $adfsUser->id])
        ->assertSessionMissing(OwnerRecoveryService::SESSION_KEY);

    $this->assertAuthenticatedAs($adfsUser);
});

it('ends owner recovery access when the owner exits the session', function () {
    $owner = createRecoveryOwner();

    $this->actingAs($owner)
        ->withSession([OwnerRecoveryService::SESSION_KEY => now()->timestamp])
        ->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-ADFS-USER'])
        ->post(route('owner-recovery.destroy'))
        ->assertRedirect(route('owner-recovery.show', ['reason' => 'signed-out']));

    $this->assertGuest();
    expect(session(OwnerRecoveryService::SESSION_KEY))->toBeNull();
});
