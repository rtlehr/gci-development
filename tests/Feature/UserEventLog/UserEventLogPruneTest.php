<?php

use App\Models\User;
use App\Models\UserEventLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makePruneEvent(User $user, string $occurredAt): UserEventLog
{
    return UserEventLog::query()->create([
        'occurred_at' => $occurredAt,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'event_type' => 'page_view',
        'module' => 'testing',
        'action' => 'view',
        'description' => 'Test event',
    ]);
}

test('prune command deletes only events older than the retention period', function () {
    $user = User::factory()->create();

    $old = makePruneEvent($user, now()->subDays(45)->toDateTimeString());
    $recent = makePruneEvent($user, now()->subDays(10)->toDateTimeString());

    $this->artisan('user-event-log:prune', ['--days' => 30])
        ->assertSuccessful();

    expect(UserEventLog::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(UserEventLog::query()->whereKey($recent->id)->exists())->toBeTrue();
});

test('retention remains disabled when configured to zero', function () {
    config()->set('user-event-log.retention_days', 0);

    $user = User::factory()->create();
    $event = makePruneEvent($user, now()->subYears(2)->toDateTimeString());

    $this->artisan('user-event-log:prune')->assertSuccessful();

    expect(UserEventLog::query()->whereKey($event->id)->exists())->toBeTrue();
});
