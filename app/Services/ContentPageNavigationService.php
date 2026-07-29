<?php

namespace App\Services;

use App\Models\ContentPage;
use Illuminate\Support\Collection;

class ContentPageNavigationService
{
    /**
     * @param array<int, string> $permissions
     * @return Collection<int, array<string, mixed>>
     */
    public function forHeader(bool $authenticated, array $permissions = []): Collection
    {
        return ContentPage::query()
            ->published()
            ->where('is_active', true)
            ->whereIn('menu_location', ['header', 'both'])
            ->whereIn(
                'visibility',
                $authenticated ? ['portal', 'both', 'public'] : ['public', 'both'],
            )
            ->orderBy('sort_order')
            ->orderBy('navigation_label')
            ->get([
                'id',
                'title',
                'slug',
                'navigation_label',
                'summary',
                'visibility',
                'help_key',
                'page_type',
            ])
            ->filter(fn (ContentPage $page): bool => $this->canSeePage(
                $page,
                $authenticated,
                $permissions,
            ))
            ->map(fn (ContentPage $page) => [
                'id' => $page->id,
                'label' => $page->navigation_label ?: $page->title,
                'title' => $page->title,
                'summary' => $page->summary,
                'href' => '/pages/'.$page->slug,
                'help_key' => $page->help_key,
            ])
            ->values();
    }

    /**
     * @param array<int, string> $permissions
     */
    public function canSeePage(
        ContentPage $page,
        bool $authenticated,
        array $permissions = [],
    ): bool {
        if (! $authenticated) {
            return in_array($page->visibility, ['public', 'both'], true);
        }

        if ($page->visibility === 'portal' && ! in_array('access_portal', $permissions, true)) {
            return false;
        }

        return match ($page->page_type) {
            ContentPage::TYPE_RESOURCE_LIBRARY =>
                $page->visibility !== 'portal'
                || in_array('portal_view_resources', $permissions, true),

            ContentPage::TYPE_CONTACT_DIRECTORY =>
                $page->visibility !== 'portal'
                || in_array('portal_view_program_contacts', $permissions, true),

            default => true,
        };
    }
}
