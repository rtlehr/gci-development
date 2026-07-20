<?php

use App\Models\Permission;
use App\Models\Person;
use App\Models\User;
use App\Services\CurrentUserContext;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds an authenticated user payload without requiring a linked person', function () {
    $user = User::factory()->create([
        'name' => 'Test Administrator',
        'email' => 'administrator@example.test',
    ]);

    $this->actingAs($user);

    $payload = app(CurrentUserContext::class)->payload();

    expect($payload)
        ->not->toBeNull()
        ->and($payload['id'])->toBe($user->id)
        ->and($payload['username'])->toBe('Test Administrator')
        ->and($payload['person_id'])->toBeNull()
        ->and($payload['person_code'])->toBeNull();
});

it('uses linked person profile information when available', function () {
    $user = User::factory()->create([
        'name' => 'Fallback Name',
    ]);

    $person = Person::query()->create([
        'user_id' => $user->id,
        'person_code' => 'CTX-PER-001',
        'first_name' => 'Charles',
        'preferred_name' => 'Chuck',
        'last_name' => 'Winchester',
        'email' => 'chuck@example.test',
    ]);

    $this->actingAs($user);

    $payload = app(CurrentUserContext::class)->payload();

    expect($payload['username'])->toBe('Chuck Winchester')
        ->and($payload['person_id'])->toBe($person->id)
        ->and($payload['person_code'])->toBe('CTX-PER-001');
});

it('returns direct user permissions through the current user context', function () {
    $user = User::factory()->create();

    $permission = Permission::query()->create([
        'name' => 'context_test_permission',
        'label' => 'Context Test Permission',
    ]);

    $user->permissions()->attach($permission->id);
    app(PermissionService::class)->clearUserPermissionCache($user->id);

    $this->actingAs($user);

    $context = app(CurrentUserContext::class);

    expect($context->hasPermission('context_test_permission'))->toBeTrue()
        ->and($context->hasPermission('missing_permission'))->toBeFalse();
});
