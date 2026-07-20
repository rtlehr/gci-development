<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    private const CACHE_TTL_MINUTES = 10;

    /** @return array<int, string> */
    public function getUserPermissions(int $userId): array
    {
        return Cache::remember(
            $this->cacheKey($userId),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($userId): array {
                $user = User::query()
                    ->with(['permissions:id,name', 'roles.permissions:id,name'])
                    ->find($userId);

                if (! $user) {
                    return [];
                }

                $directPermissions = $user->permissions->pluck('name');
                $rolePermissions = $user->roles
                    ->flatMap(fn ($role) => $role->permissions->pluck('name'));

                return $directPermissions
                    ->merge($rolePermissions)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            },
        );
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        return in_array(
            $permission,
            $this->getUserPermissions($userId),
            true,
        );
    }

    /** @param array<int, string> $permissions */
    public function hasAnyPermission(int $userId, array $permissions): bool
    {
        return array_intersect(
            $permissions,
            $this->getUserPermissions($userId),
        ) !== [];
    }

    /** @param array<int, string> $permissions */
    public function hasAllPermissions(int $userId, array $permissions): bool
    {
        return array_diff(
            $permissions,
            $this->getUserPermissions($userId),
        ) === [];
    }

    public function clearUserPermissionCache(int $userId): void
    {
        Cache::forget($this->cacheKey($userId));
    }

    private function cacheKey(int $userId): string
    {
        return "user_permissions_{$userId}";
    }
}
