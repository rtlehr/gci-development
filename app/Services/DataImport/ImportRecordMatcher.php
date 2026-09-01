<?php

namespace App\Services\DataImport;

use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;

class ImportRecordMatcher
{
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
        if (blank($positionCode)) return null;
        return Position::query()->whereRaw('LOWER(position_code) = ?', [mb_strtolower(trim($positionCode))])->first();
    }

    public function candidate(?Person $person, ?Position $position): ?Candidate
    {
        if (! $person || ! $position) return null;
        return Candidate::query()->where('person_id', $person->id)->where('position_id', $position->id)->first();
    }
}
