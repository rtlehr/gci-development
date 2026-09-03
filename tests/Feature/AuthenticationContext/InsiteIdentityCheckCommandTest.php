<?php

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reports an adfs configuration as ready', function () {
    config()->set('identity.driver', 'adfs');
    config()->set('identity.drivers.adfs.person_code_source', 'HTTP_PERSON_CODE');

    $this->artisan('insite:identity-check')
        ->expectsOutputToContain('Identity driver:        adfs')
        ->expectsOutputToContain('Configured source:      HTTP_PERSON_CODE')
        ->expectsOutputToContain('Status: CONFIGURATION READY')
        ->assertSuccessful();
});

it('fails when the adfs trusted source is blank', function () {
    config()->set('identity.driver', 'adfs');
    config()->set('identity.drivers.adfs.person_code_source', '');

    $this->artisan('insite:identity-check')
        ->expectsOutputToContain('Status: INVALID')
        ->assertFailed();
});

it('validates a person code through its linked user', function () {
    config()->set('identity.driver', 'adfs');
    config()->set('identity.drivers.adfs.person_code_source', 'HTTP_PERSON_CODE');

    $user = User::factory()->create();

    Person::query()->create([
        'user_id' => $user->id,
        'person_code' => '1111111',
        'first_name' => 'Insite',
        'last_name' => 'Owner',
        'email' => 'owner@example.test',
    ]);

    $this->artisan('insite:identity-check', ['--person-code' => '1111111'])
        ->expectsOutputToContain('Person found:           Yes')
        ->expectsOutputToContain('User found:             Yes')
        ->expectsOutputToContain('Status: READY')
        ->assertSuccessful();
});

it('fails when the supplied person code does not exist', function () {
    config()->set('identity.driver', 'adfs');
    config()->set('identity.drivers.adfs.person_code_source', 'HTTP_PERSON_CODE');

    $this->artisan('insite:identity-check', ['--person-code' => '9999999'])
        ->expectsOutputToContain('Person found:           No')
        ->expectsOutputToContain('Status: NOT READY')
        ->assertFailed();
});
