<?php

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function adminPortalUserWithPermission(string $permission): User
{
    $user = User::factory()->create();

    $permissionModel = Permission::query()->firstOrCreate(
        ['name' => $permission],
        ['description' => $permission],
    );

    $user->permissions()->syncWithoutDetaching([$permissionModel->id]);

    return $user;
}

test('authorized users can open the admin portal', function () {
    $user = adminPortalUserWithPermission('view_admin');

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Index'));
});

test('users without view admin cannot open the admin portal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertForbidden();
});
