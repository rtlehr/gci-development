<?php

namespace App\Support;

use Illuminate\Http\Request;

class CurrentUser
{
    public static function get(Request $request): ?array
    {
        if (config('devuser.enabled')) {
            $role = config('devuser.role');
            $rolePermissions = config("roles.$role") ?? [];
            $userPermissions = config('devuser.permissions', []);

            return [
                'username' => config('devuser.username'),
                'role' => $role,
                'permissions' => array_values(array_unique(array_merge(
                    $rolePermissions,
                    $userPermissions
                ))),
            ];
        }

        $user = $request->user();

        if (! $user) {
            return null;
        }

        $role = $user->role ?? 'viewer';

        // later these could come from database columns or relationships
        $rolePermissions = config("roles.$role") ?? [];
        $userPermissions = $user->permissions ?? [];

        return [
            'username' => $user->name,
            'role' => $role,
            'permissions' => array_values(array_unique(array_merge(
                $rolePermissions,
                $userPermissions
            ))),
        ];
    }

    public static function hasPermission(Request $request, string $permission): bool
    {
        $user = static::get($request);

        if (! $user) {
            return false;
        }

        return in_array($permission, $user['permissions'] ?? []);
    }

    public static function hasRole(Request $request, string $role): bool
    {
        $user = static::get($request);

        if (! $user) {
            return false;
        }

        return ($user['role'] ?? null) === $role;
    }

    public static function requirePermission(Request $request, string $permission, string $message = 'Unauthorized'): void
    {
        if (! static::hasPermission($request, $permission)) {
            abort(403, $message);
        }
    }

    public static function requireRole(Request $request, string $role, string $message = 'Unauthorized'): void
    {
        if (! static::hasRole($request, $role)) {
            abort(403, $message);
        }
    }

    public static function requireAnyPermission(Request $request, array $permissions, string $message = 'Unauthorized'): void
    {
        foreach ($permissions as $permission) {
            if (static::hasPermission($request, $permission)) {
                return;
            }
        }

        abort(403, $message);
    }

    public static function requireAllPermissions(Request $request, array $permissions, string $message = 'Unauthorized'): void
    {
        foreach ($permissions as $permission) {
            if (! static::hasPermission($request, $permission)) {
                abort(403, $message);
            }
        }
    }

}