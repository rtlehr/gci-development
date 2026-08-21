<?php

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('identity.driver', 'adfs');
    config()->set('identity.middleware_in_testing', true);
    config()->set('identity.drivers.adfs.person_code_source', 'HTTP_PERSON_CODE');

    Route::middleware(['web', 'auth'])
        ->get('/_tests/adfs/protected', fn () => response()->json([
            'user_id' => auth()->id(),
        ]));
});

it('authenticates the user linked to the adfs person code', function () {
    $user = User::factory()->create();

    Person::query()->create([
        'user_id' => $user->id,
        'person_code' => 'ADFS-PER-100',
        'first_name' => 'ADFS',
        'last_name' => 'User',
        'email' => 'adfs-user@example.test',
    ]);

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'ADFS-PER-100'])
        ->get('/_tests/adfs/protected')
        ->assertOk()
        ->assertJson(['user_id' => $user->id]);

    $this->assertAuthenticatedAs($user);
});

it('returns a controlled error when adfs does not provide a person code', function () {
    $this->get('/_tests/adfs/protected')
        ->assertStatus(401)
        ->assertSee('Unable to identify your network account');
});

it('returns a controlled error when the adfs person code is unknown to irad', function () {
    $this->withServerVariables(['HTTP_PERSON_CODE' => 'UNKNOWN-PERSON'])
        ->get('/_tests/adfs/protected')
        ->assertStatus(403)
        ->assertSee('Your account is not configured in IRAD');
});

it('returns a controlled error when the person is not linked to a user', function () {
    Person::query()->create([
        'user_id' => null,
        'person_code' => 'ADFS-PER-NO-USER',
        'first_name' => 'Unlinked',
        'last_name' => 'Person',
        'email' => 'unlinked@example.test',
    ]);

    $this->withServerVariables(['HTTP_PERSON_CODE' => 'ADFS-PER-NO-USER'])
        ->get('/_tests/adfs/protected')
        ->assertStatus(403)
        ->assertSee('Your IRAD account is incomplete');
});

it('does not allow a prior laravel session to bypass a missing adfs claim', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/_tests/adfs/protected')
        ->assertStatus(401)
        ->assertSee('Unable to identify your network account');

    $this->assertGuest();
});

it('switches the laravel-authenticated user when adfs identifies another person', function () {
    $oldUser = User::factory()->create();
    $adfsUser = User::factory()->create();

    Person::query()->create([
        'user_id' => $adfsUser->id,
        'person_code' => 'ADFS-PER-200',
        'first_name' => 'Current',
        'last_name' => 'Identity',
        'email' => 'current-identity@example.test',
    ]);

    $this->actingAs($oldUser)
        ->withServerVariables(['HTTP_PERSON_CODE' => 'ADFS-PER-200'])
        ->get('/_tests/adfs/protected')
        ->assertOk()
        ->assertJson(['user_id' => $adfsUser->id]);

    $this->assertAuthenticatedAs($adfsUser);
});

it('returns a controlled configuration error when the adfs person code source is blank', function () {
    config()->set('identity.drivers.adfs.person_code_source', '');

    $this->get('/_tests/adfs/protected')
        ->assertStatus(500)
        ->assertSee('IRAD identity configuration error');
});

