<?php

use App\Models\ImpersonationLog;
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



it('preserves an active insite impersonation when adfs still identifies the original impersonator', function () {
    $impersonator = User::factory()->create();
    $impersonated = User::factory()->create();

    Person::query()->create([
        'user_id' => $impersonator->id,
        'person_code' => 'ADFS-OWNER-300',
        'first_name' => 'Original',
        'last_name' => 'Administrator',
        'email' => 'original-admin@example.test',
    ]);

    $log = ImpersonationLog::query()->create([
        'impersonator_user_id' => $impersonator->id,
        'impersonated_user_id' => $impersonated->id,
        'session_identifier' => '11111111-1111-4111-8111-111111111111',
        'started_at' => now(),
    ]);

    $this->actingAs($impersonated)
        ->withSession([
            'impersonator_user_id' => $impersonator->id,
            'impersonation_log_id' => $log->id,
            'impersonation_session_identifier' => $log->session_identifier,
        ])
        ->withServerVariables(['HTTP_PERSON_CODE' => 'ADFS-OWNER-300'])
        ->get('/_tests/adfs/protected')
        ->assertOk()
        ->assertJson(['user_id' => $impersonated->id]);

    $this->assertAuthenticatedAs($impersonated);
    expect($log->fresh()->ended_at)->toBeNull();
});

it('ends impersonation when adfs changes to a different upstream identity', function () {
    $impersonator = User::factory()->create();
    $impersonated = User::factory()->create();
    $newAdfsUser = User::factory()->create();

    Person::query()->create([
        'user_id' => $newAdfsUser->id,
        'person_code' => 'ADFS-OTHER-400',
        'first_name' => 'Different',
        'last_name' => 'Identity',
        'email' => 'different@example.test',
    ]);

    $log = ImpersonationLog::query()->create([
        'impersonator_user_id' => $impersonator->id,
        'impersonated_user_id' => $impersonated->id,
        'session_identifier' => '22222222-2222-4222-8222-222222222222',
        'started_at' => now(),
    ]);

    $this->actingAs($impersonated)
        ->withSession([
            'impersonator_user_id' => $impersonator->id,
            'impersonation_log_id' => $log->id,
            'impersonation_session_identifier' => $log->session_identifier,
        ])
        ->withServerVariables(['HTTP_PERSON_CODE' => 'ADFS-OTHER-400'])
        ->get('/_tests/adfs/protected')
        ->assertOk()
        ->assertJson(['user_id' => $newAdfsUser->id])
        ->assertSessionMissing('impersonator_user_id');

    $this->assertAuthenticatedAs($newAdfsUser);

    $log->refresh();
    expect($log->ended_at)->not->toBeNull();
    expect($log->termination_reason)->toBe('upstream_identity_changed');
});

it('ends impersonation when the adfs identity disappears', function () {
    $impersonator = User::factory()->create();
    $impersonated = User::factory()->create();

    $log = ImpersonationLog::query()->create([
        'impersonator_user_id' => $impersonator->id,
        'impersonated_user_id' => $impersonated->id,
        'session_identifier' => '33333333-3333-4333-8333-333333333333',
        'started_at' => now(),
    ]);

    $this->actingAs($impersonated)
        ->withSession([
            'impersonator_user_id' => $impersonator->id,
            'impersonation_log_id' => $log->id,
            'impersonation_session_identifier' => $log->session_identifier,
        ])
        ->get('/_tests/adfs/protected')
        ->assertStatus(401)
        ->assertSessionMissing('impersonator_user_id');

    $this->assertGuest();

    $log->refresh();
    expect($log->ended_at)->not->toBeNull();
    expect($log->termination_reason)->toBe('upstream_identity_missing');
});
