<?php

use App\Models\ContentPage;
use App\Services\ContentPageNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('only active published content pages appear in navigation', function () {
    ContentPage::create([
        'title' => 'Visible Page',
        'slug' => 'visible-page',
        'page_type' => 'standard',
        'visibility' => 'both',
        'status' => 'published',
        'menu_location' => 'header',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    ContentPage::create([
        'title' => 'Hidden Page',
        'slug' => 'hidden-page',
        'page_type' => 'standard',
        'visibility' => 'both',
        'status' => 'published',
        'menu_location' => 'header',
        'is_active' => false,
        'sort_order' => 20,
    ]);

    $items = app(ContentPageNavigationService::class)->forHeader(false);

    expect($items)->toHaveCount(1);
    expect($items->first()['label'])->toBe('Visible Page');
});
