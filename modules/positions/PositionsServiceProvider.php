<?php

namespace Insite\Modules\Positions;

use App\Contracts\Dashboard\WorkforceDashboardProvider;
use App\Contracts\Workforce\PersonPositionRelationships;
use App\Models\Position;
use App\Models\PositionAssignment;
use Insite\Modules\Positions\Services\ModulePersonPositionRelationships;
use App\Services\ProjectManagerDashboardService;
use Illuminate\Support\ServiceProvider;

/**
 * Positions module service ownership (Phase 5C.2).
 *
 * Relationship implementation is now module-owned; dashboard data still uses
 * the host implementation until its later extraction.
 * Legacy installations without the module retain the transitional Core fallback.
 */
final class PositionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Do not expose legacy business services in a Core-only configuration.
        if (! (bool) config('insite_modules.legacy_positions_enabled', true)) {
            return;
        }

        // The selected module owns its own implementation. The host's legacy
        // adapter remains a temporary fallback only when Positions is not selected.
        if (class_exists(Position::class) && class_exists(PositionAssignment::class)) {
            $this->app->bind(PersonPositionRelationships::class, ModulePersonPositionRelationships::class);
        }
        $this->app->bind(WorkforceDashboardProvider::class, ProjectManagerDashboardService::class);
    }
}
