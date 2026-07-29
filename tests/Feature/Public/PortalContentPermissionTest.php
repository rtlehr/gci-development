<?php

use App\Models\ContentPage;
use App\Models\Permission;
use App\Models\Person;
use App\Models\User;
use App\Services\CurrentUserContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function portalContentUser(array $permissions): User
{
    $user = User::factory()->create();

    Person::factory()
        ->forUser($user)
        ->create();

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

test('portal-only resources require portal resource permission', function () {
    $page = ContentPage::create([
        'title' => 'Internal Resources',
        'slug' => 'internal-resources',
        'page_type' => ContentPage::TYPE_RESOURCE_LIBRARY,
        'visibility' => 'portal',
        'status' => 'published',
        'menu_location' => 'header',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $withoutPermission = portalContentUser(['access_portal']);

    $this->actingAs($withoutPermission)
        ->get('/pages/'.$page->slug)
        ->assertForbidden();

    app(CurrentUserContext::class)->forget();

    $withPermission = portalContentUser([
        'access_portal',
        'portal_view_resources',
    ]);

    $this->actingAs($withPermission)
        ->get('/pages/'.$page->slug)
        ->assertOk();
});

test('portal-only contacts require program contacts permission', function () {
    $page = ContentPage::create([
        'title' => 'PMO Contacts',
        'slug' => 'pmo-contacts',
        'page_type' => ContentPage::TYPE_CONTACT_DIRECTORY,
        'visibility' => 'portal',
        'status' => 'published',
        'menu_location' => 'header',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $withoutPermission = portalContentUser(['access_portal']);

    $this->actingAs($withoutPermission)
        ->get('/pages/'.$page->slug)
        ->assertForbidden();

    app(CurrentUserContext::class)->forget();

    $withPermission = portalContentUser([
        'access_portal',
        'portal_view_program_contacts',
    ]);

    $this->actingAs($withPermission)
        ->get('/pages/'.$page->slug)
        ->assertOk();
});
