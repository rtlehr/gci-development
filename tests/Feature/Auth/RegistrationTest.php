<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/dashboard');

    $user = User::query()
        ->where('email', 'test@example.com')
        ->firstOrFail();

    $this->assertAuthenticatedAs($user);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->assertDatabaseHas('people', [
        'user_id' => $user->id,
        'first_name' => 'Test',
        'preferred_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'employment_status' => 'active',
    ]);
});