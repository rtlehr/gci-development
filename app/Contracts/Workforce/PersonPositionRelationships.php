<?php

namespace App\Contracts\Workforce;

use App\Models\Person;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Core-facing read/write Eloquent relationship contract.
 * The implementation belongs to Positions, not to the People domain.
 */
interface PersonPositionRelationships
{
    public function assignments(Person $person): HasMany;

    public function positions(Person $person): BelongsToMany;
}
