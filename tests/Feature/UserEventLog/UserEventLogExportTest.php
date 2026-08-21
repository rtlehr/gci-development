<?php

use App\Models\Permission;
use App\Models\User;
use App\Models\UserEventLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function eventLogExporter(array $permissions = ['view_admin', 'access_user_event_log', 'export_user_event_log']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $name) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            ['description' => $name],
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    return $user;
}

function exportEvent(User $user, array $attributes = []): UserEventLog
{
    return UserEventLog::query()->create(array_merge([
        'occurred_at' => '2026-08-21 12:00:00',
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'event_type' => 'update',
        'module' => 'positions',
        'action' => 'update',
        'description' => 'Updated POS-1042',
        'subject_type' => 'position',
        'subject_id' => '1042',
        'subject_label' => 'POS-1042 — Senior Analyst',
        'route_name' => 'positions.update',
        'route_parameters' => ['position' => 1042],
        'path' => '/positions/1042',
        'http_method' => 'PUT',
        'ip_address' => '127.0.0.1',
        'metadata' => ['changes' => ['status' => ['from' => 'Selected', 'to' => 'Filled']]],
    ], $attributes));
}

test('authorized admins can export a filtered event log as csv', function () {
    $admin = eventLogExporter();
    $actor = User::factory()->create(['name' => 'Tracked User']);
    exportEvent($actor);

    $response = $this->actingAs($admin)->get(route('admin.user-event-log.export-range', [
        'format' => 'csv',
        'from' => '2026-08-21',
        'to' => '2026-08-21',
    ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->streamedContent())->toContain('Tracked User')->toContain('POS-1042');
});

test('splunk export is newline delimited structured json', function () {
    $admin = eventLogExporter();
    $actor = User::factory()->create(['name' => 'Tracked User']);
    exportEvent($actor);

    $response = $this->actingAs($admin)->get(route('admin.user-event-log.export-day', [
        'date' => '2026-08-21',
        'format' => 'splunk',
    ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/x-ndjson');

    $line = trim($response->streamedContent());
    $payload = json_decode(strtok($line, "\n"), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['source'])->toBe('irad')
        ->and($payload['sourcetype'])->toBe('irad:user_event')
        ->and($payload['event']['user_name'])->toBe('Tracked User')
        ->and($payload['event']['subject_label'])->toBe('POS-1042 — Senior Analyst');
});

test('user export honors activity filters', function () {
    $admin = eventLogExporter();
    $actor = User::factory()->create(['name' => 'Tracked User']);
    exportEvent($actor);
    exportEvent($actor, ['module' => 'tickets', 'description' => 'Updated ticket']);

    $response = $this->actingAs($admin)->get(route('admin.user-event-log.export-user', [
        'date' => '2026-08-21',
        'user' => $actor->id,
        'format' => 'csv',
        'module' => 'positions',
    ]));

    $content = $response->streamedContent();
    expect($content)->toContain('POS-1042')->not->toContain('Updated ticket');
});

test('event log exports require the export permission', function () {
    $admin = eventLogExporter(['view_admin', 'access_user_event_log']);

    $this->actingAs($admin)
        ->get(route('admin.user-event-log.export-range', ['format' => 'csv']))
        ->assertForbidden();
});
