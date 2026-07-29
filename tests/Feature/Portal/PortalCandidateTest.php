<?php

use App\Models\User;

it('requires authentication for portal candidates', function () {
    $this->get('/portal/candidates')->assertRedirect();
});

it('denies portal candidates without candidate access', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/portal/candidates')->assertForbidden();
});
