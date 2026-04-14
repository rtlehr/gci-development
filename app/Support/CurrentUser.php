<?php

namespace App\Support;

use App\Models\User;
use App\Services\PermissionService;
use App\Services\UserResolver;
use Illuminate\Http\Request;

class CurrentUser
{
    public static function user(Request $request): ?array
    {
        try {
            $userResolver = app(UserResolver::class);
            $permissionService = app(PermissionService::class);

            $person = $userResolver->resolvePerson();
            $user = $userResolver->resolveUser();

            $permissions = $permissionService->getUserPermissions($user->id);

            $displayName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));

            if ($displayName === '') {
                $displayName = $user->name ?? '';
            }

            return [
                'id' => $user->id,
                'username' => $displayName,
                'role' => config('devuser.role', ''), // temporary until role system is DB-backed
                'permissions' => $permissions,
                'email' => $user->email,
                'person_code' => $person->person_code,
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function permissions(Request $request): array
    {
        return self::user($request)['permissions'] ?? [];
    }

    public static function hasPermission(Request $request, string $permission): bool
    {
        return in_array($permission, self::permissions($request));
    }

    public static function securityLevel(Request $request): int
    {
        return (int) config('devuser.security_level', 0);
    }

    public static function model(Request $request): ?User
    {
        try {
            return app(UserResolver::class)->resolveUser();
        } catch (\Throwable $e) {
            return null;
        }
    }
}