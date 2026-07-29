<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use App\Services\ContentPageNavigationService;
use App\Services\CurrentUserContext;
use Inertia\Inertia;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function show(
        ContentPage $contentPage,
        CurrentUserContext $currentUser,
        ContentPageNavigationService $contentPages,
    ): Response {
        abort_unless($contentPage->status === 'published', 404);
        abort_if($contentPage->effective_at && $contentPage->effective_at->isFuture(), 404);
        abort_if($contentPage->expires_at && $contentPage->expires_at->isPast(), 404);

        $authenticated = $currentUser->user() !== null;

        abort_unless(
            $contentPage->isVisibleTo($authenticated),
            403,
        );

        abort_unless(
            $contentPages->canSeePage(
                $contentPage,
                $authenticated,
                $currentUser->permissions(),
            ),
            403,
        );

        if ($contentPage->page_type === ContentPage::TYPE_FAQ) {
            $contentPage->load('activeFaqItems');
        }

        return Inertia::render('Public/ContentPages/Show', [
            'contentPage' => [
                'title' => $contentPage->title,
                'slug' => $contentPage->slug,
                'summary' => $contentPage->summary,
                'content_html' => $contentPage->content_html,
                'page_type' => $contentPage->page_type,
                'help_key' => $contentPage->help_key,
                'updated_at' => $contentPage->updated_at?->toIso8601String(),
                'faq_items' => $contentPage->page_type === ContentPage::TYPE_FAQ
                    ? $contentPage->activeFaqItems
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'question' => $item->question,
                            'answer' => $item->answer,
                        ])
                        ->values()
                    : [],
            ],
        ]);
    }
}
