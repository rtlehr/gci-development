<?php

namespace Insite\Modules\Positions\Services;

use App\Contracts\Workforce\PersonPositionRelationships;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Module-owned implementation of the Core Person/Positions relationship contract.
 * Only registered by PositionsServiceProvider when the module is activated.
 */
final class ModulePersonPositionRelationships implements PersonPositionRelationships
{
    public function assignments(Person $person): HasMany
    {
        return $person->hasMany(PositionAssignment::class);
    }

    public function positions(Person $person): BelongsToMany
    {
        return $person->belongsToMany(Position::class, 'position_assignments')
            ->withPivot(['start_date', 'end_date', 'assignment_status', 'assignment_type', 'notes'])
            ->withTimestamps();
    }
}
