<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    public function getUserPermissions(int $userId): array
    {
        $user = User::with('permissions')->find($userId);

        if (! $user) {
            return [];
        }

        return $user->permissions
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $permissions = $this->getUserPermissions($userId);

        return in_array($permission, $permissions);
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
}