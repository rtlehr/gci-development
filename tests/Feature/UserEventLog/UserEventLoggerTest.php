<?php

use App\Models\Permission;
use App\Models\User;
use App\Models\UserEventLog;
use App\Services\UserEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('user-event-log.enabled', true);
});

test('the logger stores a structured event for the current user', function () {
    $user = User::factory()->create([
        'name' => 'Audit User',
        'email' => 'audit@example.test',
    ]);

    $this->actingAs($user);

    $event = app(UserEventLogger::class)->record(
        eventType: 'update',
        module: 'positions',
        action: 'update',
        subjectLabel: 'POS-1042 - Senior Analyst',
        description: 'Updated position',
        metadata: [
            'field' => 'status',
            'password' => 'must-not-be-stored',
        ],
        routeName: 'positions.update',
        routeParameters: [
            'position' => 1042,
            'token' => 'must-not-be-stored',
        ],
    );

    expect($event)->toBeInstanceOf(UserEventLog::class)
        ->and($event->user_id)->toBe($user->id)
        ->and($event->event_type)->toBe('update')
        ->and($event->module)->toBe('positions')
        ->and($event->subject_label)->toBe('POS-1042 - Senior Analyst')
        ->and($event->route_name)->toBe('positions.update')
        ->and($event->route_parameters)->toBe(['position' => 1042])
        ->and($event->metadata)->toBe(['field' => 'status'])
        ->and($event->request_identifier)->not->toBeNull();
});

test('successful named page visits are recorded automatically', function () {
    $user = User::factory()->create();

    foreach (['access_portal', 'portal_view_dashboard'] as $name) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            ['description' => $name],
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $this->actingAs($user)
        ->get(route('portal.dashboard'))
        ->assertOk();

    $event = UserEventLog::query()
        ->where('user_id', $user->id)
        ->where('event_type', 'page_view')
        ->where('route_name', 'portal.dashboard')
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->action)->toBe('view')
        ->and($event->module)->toBe('portal')
        ->and($event->path)->toBe('/portal/dashboard');
});

test('guest requests are not recorded as user events', function () {
    $before = UserEventLog::query()->count();

    $this->get(route('dashboard'))->assertRedirect();

    expect(UserEventLog::query()->count())->toBe($before);
});

test('multiple audit entries in the same request share one request identifier', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $first = app(UserEventLogger::class)->record(
        eventType: 'action',
        module: 'testing',
        action: 'first',
        description: 'First action',
    );

    $second = app(UserEventLogger::class)->record(
        eventType: 'action',
        module: 'testing',
        action: 'second',
        description: 'Second action',
    );

    expect($first?->request_identifier)->not->toBeNull()
        ->and($second?->request_identifier)->toBe($first?->request_identifier);
});


test('successful write requests receive a fallback event when no richer event was recorded', function () {
    Route::middleware('web')->post('/_audit-fallback-test', fn () => redirect('/'))
        ->name('audit-fallback.store');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/_audit-fallback-test')
        ->assertRedirect('/');

    $events = UserEventLog::query()->where('user_id', $user->id)->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->event_type)->toBe('create')
        ->and($events->first()->route_name)->toBe('audit-fallback.store');
});
