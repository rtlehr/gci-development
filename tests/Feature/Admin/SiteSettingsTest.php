<?php

use App\Models\Permission;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function siteSettingsAdminUser(): User
{
    $user = User::factory()->create();

    $permission = Permission::query()->create([
        'name' => 'view_admin',
        'description' => 'View admin',
    ]);

    $user->permissions()->attach($permission);

    return $user;
}

test('site setting seeder creates the current portal defaults', function () {
    $this->seed(SiteSettingSeeder::class);

    expect(SiteSetting::query()->where('key', 'branding.primary_color')->value('value'))
        ->toBe('#005c43')
        ->and(SiteSetting::query()->where('key', 'program.name')->value('value'))
        ->toBe('ZION INSIGHT Portal')
        ->and(SiteSetting::query()->where('key', 'program.contract_year')->value('value'))
        ->toBe('Base Year');
});

test('authorized administrators can view site settings', function () {
    $this->seed(SiteSettingSeeder::class);
    $user = siteSettingsAdminUser();

    $this->actingAs($user)
        ->get(route('admin.site-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/SiteSettings/Index')
            ->has('groups', 4));
});

test('authorized administrators can update site settings', function () {
    $this->seed(SiteSettingSeeder::class);
    $user = siteSettingsAdminUser();
    $setting = SiteSetting::query()->where('key', 'program.contract_year')->firstOrFail();

    $this->actingAs($user)
        ->put(route('admin.site-settings.update'), [
            'settings' => [
                $setting->id => 'Option Year 1',
            ],
        ])
        ->assertRedirect();

    expect($setting->fresh()->value)->toBe('Option Year 1');
});

test('public homepage receives seeded settings', function () {
    $this->seed(SiteSettingSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->where('program.name', 'ZION INSIGHT Portal')
            ->where('program.contract_year', 'Base Year')
            ->where('homeContent.support_title', 'Support'));
});
