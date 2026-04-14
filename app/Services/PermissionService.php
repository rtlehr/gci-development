<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    public function getUserPermissions(int $userId): array
    {
        return Cache::remember(
            "user_permissions_{$userId}",
            now()->addMinutes(10),
            function () use ($userId) {
                $user = User::with([
                    'permissions',
                    'roles.permissions',
                ])->find($userId);

                if (! $user) {
                    return [];
                }

                $directPermissions = $user->permissions->pluck('name')->toArray();

                $rolePermissions = $user->roles
                    ->flatMap(function ($role) {
                        return $role->permissions->pluck('name');
                    })
                    ->toArray();

                return collect([...$directPermissions, ...$rolePermissions])
                    ->unique()
                    ->values()
                    ->toArray();
            }
        );
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        return in_array($permission, $this->getUserPermissions($userId));
    }

    public function hasAnyPermission(int $userId, array $permissions): bool
    {
        $userPermissions = $this->getUserPermissions($userId);

        return count(array_intersect($permissions, $userPermissions)) > 0;
    }

    public function hasAllPermissions(int $userId, array $permissions): bool
    {
        $userPermissions = $this->getUserPermissions($userId);

        return count(array_diff($permissions, $userPermissions)) === 0;
    }

    public function clearUserPermissionCache(int $userId): void
    {
        Cache::forget("user_permissions_{$userId}");
    }
}