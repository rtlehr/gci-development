<?php

namespace App\Http\Middleware;

use App\Services\CurrentUserContext;
use App\Services\UserEventLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogUserPageView
{
    public function __construct(
        private readonly UserEventLogger $logger,
        private readonly CurrentUserContext $currentUser,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldConsider($request, $response)) {
            return $response;
        }

        try {
            if ($request->method() === 'GET') {
                if (! $this->logger->currentRequestHasLoggedEvent($request)
                    && $this->shouldLogPageView($request, $response)) {
                    $this->logger->recordPageView($request);
                }
            } elseif (! $this->logger->currentRequestHasLoggedEvent($request)) {
                $this->logger->recordFallbackRequest($request);
            }
        } catch (Throwable $exception) {
            // Audit logging must never break the user's primary request.
            report($exception);
        }

        return $response;
    }

    private function shouldConsider(Request $request, Response $response): bool
    {
        if (! config('user-event-log.enabled', true)) {
            return false;
        }

        if (! $response->isSuccessful() && ! $response->isRedirection()) {
            return false;
        }

        if (! $this->currentUser->user()) {
            return false;
        }

        if ($request->ajax() || $request->headers->has('X-Inertia-Partial-Data')) {
            return false;
        }

        $routeName = $request->route()?->getName();

        return (bool) $routeName && ! $this->routeIsIgnored($routeName);
    }

    private function shouldLogPageView(Request $request, Response $response): bool
    {
        return $request->method() === 'GET' && $response->isSuccessful();
    }

    private function routeIsIgnored(string $routeName): bool
    {
        foreach (config('user-event-log.ignore_routes', []) as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }
}
