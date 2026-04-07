<?php

namespace App\Support;

use Illuminate\Http\Request;

class CurrentUser
{
    public static function get(Request $request): ?array
    {
        if (config('devuser.enabled')) {
            return [
                'username' => config('devuser.username'),
                'permissions' => config('devuser.permissions'),
            ];
        }

        $user = $request->user();

        if (! $user) {
            return null;
        }

        return [
            'username' => $user->name,
            'permissions' => $user->permissions ?? [],
        ];
    }

    public static function hasPermission(Request $request, string $permission): bool
    {
        $user = static::get($request);

        if (! $user) return false;

        return in_array($permission, $user['permissions'] ?? []);
    }
}