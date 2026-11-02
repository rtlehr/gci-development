<?php

namespace App\Services\Positions;

use App\Contracts\Workforce\PersonPositionRelationships;
use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Contracts\Container\Container;

/**
 * Transitional switch; the extracted Positions module provider will replace it.
 * Fail closed when the legacy business models are not installed.
 */
final class LegacyPositionsBinding
{
    public static function register(Container $app): void
    {
        if (! (bool) $app['config']->get('insite_modules.legacy_positions_enabled', true)) {
            return;
        }

        if (! class_exists(Position::class)
            || ! class_exists(PositionAssignment::class)
            || ! class_exists(LegacyPersonPositionRelationships::class)) {
            return;
        }

        $app->bind(PersonPositionRelationships::class, LegacyPersonPositionRelationships::class);
    }
}
