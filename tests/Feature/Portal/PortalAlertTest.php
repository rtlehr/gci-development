<?php

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows only the current users alerts', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Alert::query()->create(['user_id' => $user->id, 'title' => 'Mine', 'type' => 'info', 'priority' => 'normal']);
    Alert::query()->create(['user_id' => $other->id, 'title' => 'Not mine', 'type' => 'info', 'priority' => 'normal']);

    $this->actingAs($user)
        ->get(route('portal.alerts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Alerts/Index')
            ->has('alerts.data', 1)
            ->where('alerts.data.0.title', 'Mine')
        );
});

it('filters unread alerts', function () {
    $user = User::factory()->create();

    Alert::query()->create(['user_id' => $user->id, 'title' => 'Unread', 'type' => 'info', 'priority' => 'normal']);
    Alert::query()->create(['user_id' => $user->id, 'title' => 'Read', 'type' => 'info', 'priority' => 'normal', 'read_at' => now()]);

    $this->actingAs($user)
        ->get(route('portal.alerts.index', ['status' => 'unread']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('alerts.data', 1)
            ->where('alerts.data.0.title', 'Unread')
            ->where('counts.unread', 1)
        );
});
