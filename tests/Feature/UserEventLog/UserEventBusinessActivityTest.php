<?php

use App\Models\Position;
use App\Models\User;
use App\Models\UserEventLog;
use App\Services\UserEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('user-event-log.enabled', true);
});

test('business events retain the subject and safe before after changes', function () {
    $user = User::factory()->create();
    $position = Position::query()->create([
        'position_code' => 'POS-2001',
        'job_title' => 'Systems Engineer',
        'status' => 'Open',
    ]);

    $this->actingAs($user);

    $event = app(UserEventLogger::class)->recordModelEvent(
        eventType: 'update',
        module: 'positions',
        action: 'update',
        subject: $position,
        description: 'Updated position POS-2001.',
        before: ['status' => 'Open', 'password' => 'secret'],
        after: ['status' => 'In Process', 'password' => 'changed-secret'],
    );

    expect($event)->toBeInstanceOf(UserEventLog::class)
        ->and($event->subject_type)->toBe($position->getMorphClass())
        ->and($event->subject_id)->toBe((string) $position->getRouteKey())
        ->and($event->metadata['changes'])->toBe([
            'status' => ['from' => 'Open', 'to' => 'In Process'],
        ]);
});

test('unchanged values are omitted from business event change metadata', function () {
    $logger = app(UserEventLogger::class);

    expect($logger->buildChanges(
        ['status' => 'Open', 'level' => 3],
        ['status' => 'Open', 'level' => 4],
    ))->toBe([
        'level' => ['from' => 3, 'to' => 4],
    ]);
});
