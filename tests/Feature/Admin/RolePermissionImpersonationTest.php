<?php

use App\Models\ImpersonationLog;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrentUserContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function roleMatrixPermission(string $name): Permission
{
    return Permission::query()->firstOrCreate(
        ['name' => $name],
        [
            'group_name' => 'Test',
            'label' => $name,
            'description' => $name,
            'is_system' => false,
            'is_locked' => false,
        ],
    );
}

function roleMatrixUser(string $roleName, array $permissions): User
{
    $user = User::factory()->create();
    Person::factory()->forUser($user)->create();

    $role = Role::query()->create([
        'name' => $roleName,
        'label' => str($roleName)->replace('_', ' ')->title()->toString(),
        'description' => 'Test role',
    ]);

    $role->permissions()->sync(
        collect($permissions)
            ->map(fn (string $name) => roleMatrixPermission($name)->id)
            ->all(),
    );

    $user->roles()->attach($role);

    return $user;
}

test('only owner admin and developer role bundles can enter admin', function () {
    foreach (['owner', 'admin', 'developer'] as $roleName) {
        $user = roleMatrixUser($roleName, ['view_admin']);

        app(CurrentUserContext::class)->forget();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    $candidate = roleMatrixUser('candidate', ['access_portal']);

    app(CurrentUserContext::class)->forget();

    $this->actingAs($candidate)
        ->get('/admin')
        ->assertForbidden();
});

test('admin cannot access site settings roles permissions or page help administration', function () {
    $admin = roleMatrixUser('admin', ['view_admin']);

    $this->actingAs($admin)->get('/admin/site-settings')->assertForbidden();
    $this->actingAs($admin)->get('/admin/roles')->assertForbidden();
    $this->actingAs($admin)->get('/admin/permissions')->assertForbidden();
    $this->actingAs($admin)->get('/admin/page-help')->assertForbidden();
});

test('developer can access support and impersonation but not operational administration', function () {
    $developer = roleMatrixUser('developer', [
        'view_admin',
        'access_tickets',
        'read_tickets',
        'update_tickets',
        'impersonate_users',
        'view_impersonation_log',
    ]);

    $this->actingAs($developer)->get('/admin/tickets')->assertOk();
    $this->actingAs($developer)->get('/admin/impersonation')->assertOk();
    $this->actingAs($developer)->get('/admin/site-settings')->assertForbidden();
    $this->actingAs($developer)->get('/admin/roles')->assertForbidden();
});

test('owner can begin and stop an audited impersonation session', function () {
    $owner = roleMatrixUser('owner', [
        'view_admin',
        'impersonate_users',
        'view_impersonation_log',
        'access_portal',
        'portal_view_dashboard',
    ]);

    $candidate = roleMatrixUser('candidate', [
        'access_portal',
        'portal_view_dashboard',
    ]);

    $this->actingAs($owner)
        ->post("/admin/impersonation/{$candidate->id}", [
            'reason' => 'Verify candidate dashboard.',
        ])
        ->assertRedirect('/portal/dashboard');

    $log = ImpersonationLog::query()->firstOrFail();

    expect($log->impersonator_user_id)->toBe($owner->id);
    expect($log->impersonated_user_id)->toBe($candidate->id);
    expect($log->reason)->toBe('Verify candidate dashboard.');
    expect($log->ended_at)->toBeNull();

    $this->assertAuthenticatedAs($candidate);

    $this->post('/impersonation/stop')
        ->assertRedirect('/admin/impersonation');

    $this->assertAuthenticatedAs($owner);

    expect($log->fresh()->ended_at)->not->toBeNull();
});

test('developer cannot impersonate privileged administrative accounts', function () {
    $developer = roleMatrixUser('developer', [
        'view_admin',
        'impersonate_users',
        'view_impersonation_log',
    ]);

    $owner = roleMatrixUser('owner', ['view_admin']);

    $this->actingAs($developer)
        ->post("/admin/impersonation/{$owner->id}")
        ->assertForbidden();

    expect(ImpersonationLog::query()->count())->toBe(0);
});
