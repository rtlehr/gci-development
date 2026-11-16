<?php

namespace App\Services\DataImport;

use App\Contracts\Positions\PositionImportLookup;
use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;

class ImportRecordMatcher
{
    public function __construct(private readonly PositionImportLookup $positions) {}

    public function person(?string $personCode, ?string $email): array
    {
        if (filled($personCode)) {
            $person = Person::findByPersonCode($personCode);
            if ($person) return ['record' => $person, 'matched_by' => 'person_code', 'ambiguous' => false];
        }

        if (filled($email)) {
            $matches = Person::query()->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->limit(2)->get();
            if ($matches->count() === 1) return ['record' => $matches->first(), 'matched_by' => 'email', 'ambiguous' => false];
            if ($matches->count() > 1) return ['record' => null, 'matched_by' => 'email', 'ambiguous' => true];
        }

        return ['record' => null, 'matched_by' => null, 'ambiguous' => false];
    }

    public function position(?string $positionCode): ?Position
    {
        return $this->positions->position($positionCode);
    }

    public function candidate(?Person $person, ?Position $position): ?Candidate
    {
        return $this->positions->candidate($person, $position);
    }
}
