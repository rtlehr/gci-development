<?php

namespace App\Services\Dashboard;

use App\Contracts\Dashboard\WorkforceDashboardProvider;
use App\Models\User;
use Illuminate\Support\Collection;

/** Core-only dashboard data provider; never accesses workforce tables. */
class EmptyWorkforceDashboardProvider implements WorkforceDashboardProvider
{
    public function positionsFor(User $user): Collection
    {
        return collect();
    }

    public function allPositionsForPmo(): Collection
    {
        return collect();
    }

    public function candidateOpportunitiesFor(User $user): Collection
    {
        return collect();
    }

    public function staffingColumns(): array
    {
        return [];
    }

    public function staffingSummary(Collection $positions): array
    {
        return ['vacant' => 0, 'selected' => 0, 'filled' => 0, 'departing' => 0, 'onHold' => 0];
    }
}
