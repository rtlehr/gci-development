<?php

namespace App\Services;

use App\Models\Position;
use App\Models\PositionActivity;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PositionService
{
    /**
     * Return only users who may be assigned as Position Project Managers.
     */
    public function projectManagers(): Collection
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', 'project_manager');
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
            ]);
    }

    /**
     * Enforce the core business rule:
     * only users with the project_manager role may be assigned.
     */
    public function assertEligibleProjectManager(?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $isEligible = User::query()
            ->whereKey($userId)
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', 'project_manager');
            })
            ->exists();

        if (! $isEligible) {
            throw ValidationException::withMessages([
                'project_manager_user_id' => 'The selected user must have the Project Manager role.',
            ]);
        }
    }

    public function create(array $attributes, ?int $userId): Position
    {
        $this->assertEligibleProjectManager(
            $attributes['project_manager_user_id'] ?? null
        );

        return DB::transaction(function () use ($attributes, $userId) {
            $position = Position::query()->create($attributes);

            PositionActivity::query()->create([
                'position_id' => $position->id,
                'user_id' => $userId,
                'action' => 'created',
                'description' => 'Position created.',
            ]);

            return $position;
        });
    }

    public function update(
        Position $position,
        array $attributes,
        ?int $userId
    ): Position {
        $this->assertEligibleProjectManager(
            $attributes['project_manager_user_id'] ?? null
        );

        return DB::transaction(function () use (
            $position,
            $attributes,
            $userId
        ) {
            $original = $position->getOriginal();

            $position->update($attributes);

            $ignoredActivityFields = [
                'customer_created_at',
            ];

            foreach ($attributes as $field => $newValue) {
                if (in_array($field, $ignoredActivityFields, true)) {
                    continue;
                }

                $oldValue = $original[$field] ?? null;

                if ((string) $oldValue === (string) $newValue) {
                    continue;
                }

                PositionActivity::query()->create([
                    'position_id' => $position->id,
                    'user_id' => $userId,
                    'action' => 'updated',
                    'field_name' => $field,
                    'old_value' => is_array($oldValue)
                        ? json_encode($oldValue)
                        : $oldValue,
                    'new_value' => is_array($newValue)
                        ? json_encode($newValue)
                        : $newValue,
                    'description' => "Updated {$field}.",
                ]);
            }

            return $position->refresh();
        });
    }
}
