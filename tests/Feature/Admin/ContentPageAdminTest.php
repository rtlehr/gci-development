<?php

use App\Models\ContentPage;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('admin can open content pages with template information', function () {
    $user = User::factory()->create();
    $permissions = collect([
        'view_admin',
        'access_content_pages',
    ])->map(fn (string $name) => Permission::firstOrCreate(
        ['name' => $name],
        [
            'group_name' => 'Test',
            'label' => $name,
            'description' => $name,
            'is_system' => false,
            'is_locked' => false,
        ],
    ));

    $user->permissions()->sync($permissions->pluck('id'));

    ContentPage::create([
        'title' => 'FAQ',
        'slug' => 'faq',
        'page_type' => 'faq',
        'visibility' => 'both',
        'status' => 'published',
        'menu_location' => 'header',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->get('/admin/content-pages')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ContentPages/Index')
            ->has('pages.data', 1));
});

test('admin can save structured faq questions', function () {
    $user = User::factory()->create();
    $permissions = collect([
        'view_admin',
        'access_content_pages',
        'manage_content_pages',
    ])->map(fn (string $name) => Permission::firstOrCreate(
        ['name' => $name],
        [
            'group_name' => 'Test',
            'label' => $name,
            'description' => $name,
            'is_system' => false,
            'is_locked' => false,
        ],
    ));

    $user->permissions()->sync($permissions->pluck('id'));

    $this->actingAs($user)
        ->post('/admin/content-pages', [
            'title' => 'Questions',
            'slug' => 'questions',
            'navigation_label' => 'Questions',
            'summary' => 'Common questions.',
            'content_html' => '<p>Choose a question.</p>',
            'page_type' => 'faq',
            'visibility' => 'both',
            'status' => 'published',
            'menu_location' => 'header',
            'is_active' => true,
            'sort_order' => 10,
            'effective_at' => null,
            'expires_at' => null,
            'help_key' => 'content.questions',
            'faq_items' => [
                [
                    'question' => 'How do I get help?',
                    'answer' => 'Use Support.',
                    'is_active' => true,
                    'sort_order' => 10,
                ],
            ],
        ])
        ->assertRedirect();

    $page = ContentPage::query()->where('slug', 'questions')->firstOrFail();

    expect($page->faqItems()->count())->toBe(1);
    expect($page->faqItems()->first()->question)->toBe('How do I get help?');
});
