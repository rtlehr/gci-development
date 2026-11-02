<?php

namespace App\Contracts\Dashboard;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Core-facing data contract for optional workforce dashboard panels.
 * Implementations must return dashboard-ready arrays, not Eloquent models.
 */
interface WorkforceDashboardProvider
{
    public function positionsFor(User $user): Collection;

    public function allPositionsForPmo(): Collection;

    public function candidateOpportunitiesFor(User $user): Collection;

    public function staffingColumns(): array;

    public function staffingSummary(Collection $positions): array;
}
