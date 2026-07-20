<?php

use App\Models\User;
use App\Services\UserResolver;

it('uses the explicitly authenticated test user instead of the development person code', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(app(UserResolver::class)->resolveUser()->is($user))->toBeTrue();
});

it('does not auto authenticate a guest from development configuration during tests', function () {
    $this->assertGuest();

    $this->get(route('login'))->assertOk();

    $this->assertGuest();
});
