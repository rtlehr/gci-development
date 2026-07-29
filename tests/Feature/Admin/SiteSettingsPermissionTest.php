<?php

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function siteSettingsPermissionUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $name) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            [
                'group_name' => 'Test',
                'label' => $name,
                'description' => $name,
                'is_system' => false,
                'is_locked' => false,
            ],
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    return $user;
}

test('view admin alone does not expose site settings', function () {
    $user = siteSettingsPermissionUser(['view_admin']);

    $this->actingAs($user)
        ->get('/admin/site-settings')
        ->assertForbidden();
});

test('site settings requires admin and site settings access', function () {
    $user = siteSettingsPermissionUser([
        'view_admin',
        'access_site_settings',
    ]);

    $this->actingAs($user)
        ->get('/admin/site-settings')
        ->assertOk();
});

test('updating site settings requires update permission', function () {
    $user = siteSettingsPermissionUser([
        'view_admin',
        'access_site_settings',
    ]);

    $this->actingAs($user)
        ->put('/admin/site-settings', ['settings' => []])
        ->assertForbidden();
});
