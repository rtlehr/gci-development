<?php

namespace App\Services\Positions;

use App\Contracts\Workforce\PersonPositionRelationships;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Temporary legacy Positions adapter. Move this binding to the Positions
 * module's provider when extraction is complete.
 */
final class LegacyPersonPositionRelationships implements PersonPositionRelationships
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
