<?php

namespace App\Support;

use Illuminate\Http\Request;

class CurrentUser
{
    public static function get(Request $request): ?array
    {
        // Development mode: use the hardcoded config user
        if (config('devuser.enabled')) {
            return [
                'username' => config('devuser.username'),
                'security_level' => config('devuser.security_level'),
            ];
        }

        // Real login mode: use Laravel's authenticated user
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return [
            'username' => $user->name,
            'security_level' => $user->security_level ?? 0,
        ];
    }

    public static function securityLevel(Request $request): int
    {
        return static::get($request)['security_level'] ?? 0;
    }
}