<?php

namespace App\Http\Middleware;

use App\Services\CurrentUserContext;
use App\Services\SiteSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalFeatureEnabled
{
    public function __construct(
        private readonly SiteSettingsService $siteSettings,
        private readonly CurrentUserContext $currentUser,
    ) {
    }

    public function handle(Request $request, Closure $next, string $featureKey, ?string $bypassPermission = null): Response
    {
        if ($this->siteSettings->get($featureKey, true) === true) {
            return $next($request);
        }

        if ($bypassPermission && $this->currentUser->hasPermission($bypassPermission)) {
            return $next($request);
        }

        abort(404);
    }
}
