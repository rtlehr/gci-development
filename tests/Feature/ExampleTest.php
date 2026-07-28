<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can visit the public home route', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
        );
});
