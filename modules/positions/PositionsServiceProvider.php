<?php

namespace Insite\Modules\Positions;

use Illuminate\Support\ServiceProvider;

/**
 * Phase 5C.1 module shell. No legacy services, routes or migrations are moved here yet.
 */
final class PositionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Intentionally empty until module-owned implementations are extracted.
        // Existing legacy Positions functionality remains registered by the host.
    }
}
