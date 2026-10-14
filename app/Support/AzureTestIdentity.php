<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Additional fail-closed guard for the Azure App Service test identity mode.
 * IMPORTANT: X-MS-CLIENT-PRINCIPAL-* is authoritative ONLY when App Service
 * Authentication (Easy Auth) is set to require login, allowlisted in Entra,
 * and cannot be bypassed by direct origin access or spoofed client headers.
 */
final class AzureTestIdentity
{
    public static function enabled(): bool
    {
        return app()->isProduction()
            && config('azure_test_identity.enabled') === true
            && config('identity.driver') === 'development'
            && config('devuser.enabled') === true
            && filled(config('azure_test_identity.app_name'))
            && hash_equals(
                (string) config('azure_test_identity.app_name'),
                (string) config('azure_test_identity.runtime_app_name', '')
            )
            && filled(config('azure_test_identity.entra_object_id'));
    }

    public static function authorized(Request $request): bool
    {
        if (! self::enabled()) {
            return false;
        }

        $actual = (string) $request->header('X-MS-CLIENT-PRINCIPAL-ID', '');
        $provider = strtolower((string) $request->header('X-MS-CLIENT-PRINCIPAL-IDP', ''));

        return $provider === 'aad'
            && $actual !== ''
            && hash_equals(strtolower((string) config('azure_test_identity.entra_object_id')), strtolower($actual));
    }

    public static function switcherAvailable(Request $request): bool
    {
        return config('devuser.enabled') === true
            && (app()->environment('local') || self::authorized($request));
    }
}
