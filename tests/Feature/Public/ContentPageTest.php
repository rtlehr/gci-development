<?php

use App\Models\ContentPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('published public page can be viewed', function () {
    ContentPage::create([
        'title' => 'Resources',
        'slug' => 'resources',
        'page_type' => 'resource_library',
        'visibility' => 'public',
        'status' => 'published',
        'menu_location' => 'header',
        'is_active' => true,
        'sort_order' => 1,
        'content_html' => '<p>Resources</p>',
    ]);

    $this->get('/pages/resources')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/ContentPages/Show')
            ->where('contentPage.slug', 'resources')
            ->where('contentPage.page_type', 'resource_library'));
});

test('published faq page returns active structured questions', function () {
    $page = ContentPage::create([
        'title' => 'FAQs',
        'slug' => 'faqs',
        'page_type' => 'faq',
        'visibility' => 'public',
        'status' => 'published',
        'menu_location' => 'header',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $page->faqItems()->createMany([
        [
            'question' => 'Visible?',
            'answer' => 'Yes.',
            'is_active' => true,
            'sort_order' => 10,
        ],
        [
            'question' => 'Hidden?',
            'answer' => 'No.',
            'is_active' => false,
            'sort_order' => 20,
        ],
    ]);

    $this->get('/pages/faqs')
        ->assertOk()
        ->assertInertia(fn (Assert $response) => $response
            ->component('Public/ContentPages/Show')
            ->has('contentPage.faq_items', 1)
            ->where('contentPage.faq_items.0.question', 'Visible?'));
});

test('draft page is not public', function () {
    ContentPage::create([
        'title' => 'Draft',
        'slug' => 'draft',
        'page_type' => 'standard',
        'visibility' => 'public',
        'status' => 'draft',
        'menu_location' => 'none',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->get('/pages/draft')->assertNotFound();
});
