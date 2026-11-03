<?php

namespace App\Contracts\Positions;

use App\Models\Position;
use Illuminate\Support\Collection;

/**
 * Compatibility boundary between host controllers and an optional Positions module.
 * Methods and model types mirror the existing PositionService API until models
 * and controller ownership are extracted in subsequent milestones.
 */
interface PositionOperations
{
    public function projectManagers(): Collection;

    public function assertEligibleProjectManager(?int $userId): void;

    public function create(array $attributes, ?int $userId): Position;

    public function update(Position $position, array $attributes, ?int $userId): Position;
}
