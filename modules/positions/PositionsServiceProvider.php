<?php

namespace Insite\Modules\Positions;

use App\Contracts\Dashboard\WorkforceDashboardProvider;
use App\Services\Positions\LegacyPositionsBinding;
use App\Services\ProjectManagerDashboardService;
use Illuminate\Support\ServiceProvider;

/**
 * Positions module service ownership (Phase 5C.2).
 *
 * The underlying implementations still reside in the host application during
 * extraction. This provider owns their registration when Positions is selected.
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

        LegacyPositionsBinding::register($this->app);
        $this->app->bind(WorkforceDashboardProvider::class, ProjectManagerDashboardService::class);
    }
}
