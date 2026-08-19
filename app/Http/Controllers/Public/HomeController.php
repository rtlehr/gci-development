<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use App\Services\ContentPageNavigationService;
use App\Services\CurrentUserContext;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function __invoke(
        CurrentUserContext $currentUser,
        SiteSettingsService $siteSettings,
        ContentPageNavigationService $contentPages,
    ): Response|RedirectResponse {
        $defaultHomePage = (string) $siteSettings->get('navigation.default_home_page', 'public_home');

        if (
            $defaultHomePage === 'my_portal'
            && $currentUser->user() !== null
            && $currentUser->hasPermission('access_portal')
            && $currentUser->hasPermission('portal_view_dashboard')
        ) {
            return redirect()->route('portal.dashboard');
        }

        if (
            str_starts_with($defaultHomePage, 'content_page:')
            && $siteSettings->get('features.content_pages', true) === true
        ) {
            $contentPageId = (int) Str::after($defaultHomePage, 'content_page:');
            $contentPage = ContentPage::query()
                ->published()
                ->where('is_active', true)
                ->find($contentPageId);

            if ($contentPage !== null) {
                $authenticated = $currentUser->user() !== null;

                if (
                    $contentPage->isVisibleTo($authenticated)
                    && $contentPages->canSeePage(
                        $contentPage,
                        $authenticated,
                        $currentUser->permissions(),
                    )
                ) {
                    return redirect()->route('content-pages.show', ['contentPage' => $contentPage->slug]);
                }
            }
        }

        return $this->renderHome($currentUser, $siteSettings);
    }

    public function show(
        CurrentUserContext $currentUser,
        SiteSettingsService $siteSettings,
    ): Response {
        return $this->renderHome($currentUser, $siteSettings);
    }

    private function renderHome(
        CurrentUserContext $currentUser,
        SiteSettingsService $siteSettings,
    ): Response {
        return Inertia::render('Public/Home', [
            'isAuthenticated' => $currentUser->user() !== null,
            'program' => $siteSettings->get('program'),
            'homeContent' => $siteSettings->get('home'),
        ]);
    }
}
