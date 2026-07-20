<?php

namespace App\Support;

use App\Models\Person;
use App\Models\User;
use App\Services\CurrentUserContext;
use Illuminate\Http\Request;

/**
 * Backward-compatible static facade for the scoped CurrentUserContext service.
 *
 * New injectable classes should depend on CurrentUserContext directly. This
 * wrapper remains available to existing controllers and middleware so the
 * cleanup does not require a broad application rewrite.
 */
class CurrentUser
{
    public static function user(Request $request): ?array
    {
        return self::context()->payload();
    }

    /** @return array<int, string> */
    public static function permissions(Request $request): array
    {
        return self::context()->permissions();
    }

    public static function hasPermission(Request $request, string $permission): bool
    {
        return self::context()->hasPermission($permission);
    }

    public static function securityLevel(Request $request): int
    {
        return (int) config('devuser.security_level', 0);
    }

    public static function model(Request $request): ?User
    {
        return self::context()->user();
    }

    public static function person(Request $request): ?Person
    {
        return self::context()->person();
    }

    private static function context(): CurrentUserContext
    {
        return app(CurrentUserContext::class);
    }
}
