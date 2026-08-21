<?php

use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use App\Models\UserEventLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    UserEventLog::query()->delete();
});

function userEventLogAdmin(array $extraPermissions = []): User
{
    $user = User::factory()->create();

    foreach (array_merge(['view_admin', 'access_user_event_log'], $extraPermissions) as $name) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            ['description' => $name],
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    return $user;
}

function makeUserEvent(User $user, array $attributes = []): UserEventLog
{
    return UserEventLog::query()->create(array_merge([
        'occurred_at' => '2026-08-21 12:00:00',
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'event_type' => 'page_view',
        'module' => 'portal',
        'action' => 'view',
        'route_name' => 'portal.dashboard',
        'route_parameters' => [],
        'path' => '/portal/dashboard',
        'http_method' => 'GET',
    ], $attributes));
}

test('authorized admins can view event log dates and daily totals', function () {
    $admin = userEventLogAdmin();
    $actor = User::factory()->create(['name' => 'Tracked User']);

    makeUserEvent($actor, ['occurred_at' => '2026-08-21 12:00:00']);
    makeUserEvent($actor, ['occurred_at' => '2026-08-21 13:00:00']);

    $this->actingAs($admin)
        ->get(route('admin.user-event-log.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserEventLog/Index')
            ->where('days.data.0.date', '2026-08-21')
            ->where('days.data.0.user_count', 1)
            ->where('days.data.0.event_count', 2));
});

test('day view groups activity by user', function () {
    $admin = userEventLogAdmin();
    $actor = User::factory()->create(['name' => 'Tracked User', 'email' => 'tracked@example.test']);

    makeUserEvent($actor);
    makeUserEvent($actor, ['occurred_at' => '2026-08-21 13:15:00']);

    $this->actingAs($admin)
        ->get(route('admin.user-event-log.day', '2026-08-21'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserEventLog/Day')
            ->where('date', '2026-08-21')
            ->where('users.data.0.user_id', $actor->id)
            ->where('users.data.0.event_count', 2));
});

test('user activity resolves a position event to its portal record', function () {
    $admin = userEventLogAdmin();
    $actor = User::factory()->create(['name' => 'Tracked User']);
    $position = Position::query()->create([
        'position_code' => 'POS-1042',
        'job_title' => 'Senior Analyst',
        'status' => 'Open',
    ]);

    makeUserEvent($actor, [
        'module' => 'positions',
        'route_name' => 'portal.positions.show',
        'route_parameters' => ['id' => $position->id],
        'path' => "/portal/positions/{$position->id}",
    ]);

    $this->actingAs($admin)
        ->get(route('admin.user-event-log.user', ['date' => '2026-08-21', 'user' => $actor->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserEventLog/UserActivity')
            ->where('user.id', $actor->id)
            ->where('events.data.0.detail.label', 'POS-1042 — Senior Analyst')
            ->where('events.data.0.detail.href', "/portal/positions/{$position->id}"));
});

test('event log admin pages require the event log permission', function () {
    $admin = User::factory()->create();
    $permission = Permission::query()->firstOrCreate(
        ['name' => 'view_admin'],
        ['description' => 'view_admin'],
    );
    $admin->permissions()->syncWithoutDetaching([$permission->id]);

    $this->actingAs($admin)
        ->get(route('admin.user-event-log.index'))
        ->assertForbidden();
});
