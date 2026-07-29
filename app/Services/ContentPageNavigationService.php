<?php

namespace App\Services;

use App\Models\ContentPage;
use Illuminate\Support\Collection;

class ContentPageNavigationService
{
    /** @return Collection<int, array<string, mixed>> */
    public function forHeader(bool $authenticated): Collection
    {
        return ContentPage::query()
            ->published()
            ->where('is_active', true)
            ->whereIn('menu_location', ['header', 'both'])
            ->whereIn('visibility', $authenticated ? ['portal', 'both', 'public'] : ['public', 'both'])
            ->orderBy('sort_order')
            ->orderBy('navigation_label')
            ->get(['id','title','slug','navigation_label','summary','visibility','help_key'])
            ->map(fn (ContentPage $page) => [
                'id' => $page->id,
                'label' => $page->navigation_label ?: $page->title,
                'title' => $page->title,
                'summary' => $page->summary,
                'href' => '/pages/'.$page->slug,
                'help_key' => $page->help_key,
            ]);
    }
}
