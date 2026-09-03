<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\EnforceSecureTransport;
use App\Http\Middleware\LogUserPageView;
use App\Http\Middleware\ResolveUserFromPhpSource;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Trust only explicitly configured reverse proxies. The Insite Authentication
         * Gateway sets X-Forwarded-* after terminating TLS. Leaving this environment
         * value blank preserves direct/local behavior and trusts no proxy.
         */
        $trustedProxies = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IRAD_TRUSTED_PROXIES', ''))
        )));

        if ($trustedProxies !== []) {
            $middleware->trustProxies(
                at: $trustedProxies,
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO,
            );
        }

        $middleware->prepend(EnforceSecureTransport::class);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            /* Establish development identity before shared Inertia data. */
            ResolveUserFromPhpSource::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            LogUserPageView::class,
        ]);

        /*
         * The external identity must be established before Laravel's route-level
         * authentication middleware runs. Keep ResolveUserFromPhpSource in the web group so
         * the session is available, then explicitly place it immediately before
         * Authenticate in Laravel's middleware priority ordering.
         */
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            ResolveUserFromPhpSource::class,
        );

        $middleware->alias([
            'security' => \App\Http\Middleware\CheckSecurityLevel::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'portal-feature' => \App\Http\Middleware\EnsurePortalFeatureEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
