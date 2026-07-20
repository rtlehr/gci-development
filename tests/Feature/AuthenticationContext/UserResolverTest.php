<?php

use App\Models\Person;
use App\Models\User;
use App\Services\UserResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated user to exist without a linked person', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $resolver = app(UserResolver::class);

    expect($resolver->resolveUser()->is($user))->toBeTrue()
        ->and($resolver->findPerson())->toBeNull();
});

it('strictly requires a person only when resolvePerson is requested', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(fn () => app(UserResolver::class)->resolvePerson())
        ->toThrow(
            \RuntimeException::class,
            "Authenticated User [{$user->id}] does not have a linked Person record.",
        );
});

it('resolves the person linked to the authenticated user', function () {
    $user = User::factory()->create();

    $person = Person::query()->create([
        'user_id' => $user->id,
        'person_code' => 'RES-PER-001',
        'first_name' => 'Margaret',
        'last_name' => 'Houlihan',
        'email' => 'margaret@example.test',
    ]);

    $this->actingAs($user);

    expect(app(UserResolver::class)->resolvePerson()->is($person))->toBeTrue();
});
