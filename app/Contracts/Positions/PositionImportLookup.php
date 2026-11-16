<?php

namespace App\Contracts\Positions;

use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;

/**
 * Cross-module lookup contract for the Data Import validation workflow.
 * The model types are transitional until Positions model ownership is resolved.
 */
interface PositionImportLookup
{
    public function position(?string $positionCode): ?Position;

    public function candidate(?Person $person, ?Position $position): ?Candidate;
}
