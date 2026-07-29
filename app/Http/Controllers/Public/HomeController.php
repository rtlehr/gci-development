<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CurrentUserContext;
use App\Services\SiteSettingsService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(
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
