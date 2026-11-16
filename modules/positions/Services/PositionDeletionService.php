<?php

namespace Insite\Modules\Positions\Services;

use App\Models\Position;
use App\Models\PositionActivity;
use App\Services\UserEventLogger;

/**
 * Owns the module's deletion guard and activity logging.
 * Portal additionally records the existing user-event audit entry.
 */
final class PositionDeletionService
{
    public function __construct(private readonly UserEventLogger $eventLogger)
    {
    }

    /**
     * Return false without side effects when the position has any assignments,
     * including historical assignments.
     */
    public function delete(Position $position, ?int $userId, bool $auditPortal = false): bool
    {
        if ($position->assignments()->exists()) {
            return false;
        }

        PositionActivity::create([
            'position_id' => $position->id,
            'user_id' => $userId,
            'action' => 'deleted',
            'description' => 'Position deleted.',
        ]);

        if ($auditPortal) {
            $this->eventLogger->recordModelEvent(
                eventType: 'delete',
                module: 'positions',
                action: 'delete',
                subject: $position,
                description: 'Deleted position '.$position->position_code.'.',
                metadata: ['deleted' => true],
            );
        }

        $position->delete();

        return true;
    }
}
