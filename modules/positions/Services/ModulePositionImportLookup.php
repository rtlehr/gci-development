<?php

namespace Insite\Modules\Positions\Services;

use App\Contracts\Positions\PositionImportLookup;
use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;

/** Module-owned import matching implementation, independent of the legacy host service. */
final class ModulePositionImportLookup implements PositionImportLookup
{
    public function position(?string $positionCode): ?Position
    {
        if (blank($positionCode)) return null;

        return Position::query()->whereRaw('LOWER(position_code) = ?', [mb_strtolower(trim($positionCode))])->first();
    }

    public function candidate(?Person $person, ?Position $position): ?Candidate
    {
        if (! $person || ! $position) return null;

        return Candidate::query()->where('person_id', $person->id)->where('position_id', $position->id)->first();
    }
}
