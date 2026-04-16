<?php

namespace App\Services;

use App\Models\Person;
use App\Models\PersonPhoneNumber;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PersonPhoneService
{
    public function sync(Person $person, array $phoneNumbersInput = []): void
    {
        $phoneNumbers = collect($phoneNumbersInput)
            ->map(function ($phone) {
                return [
                    'id' => $phone['id'] ?? null,
                    'phone_number' => trim((string) ($phone['phone_number'] ?? '')),
                    'phone_type' => $phone['phone_type'] ?? null,
                    'is_primary' => (bool) ($phone['is_primary'] ?? false),
                    'extension' => $phone['extension'] ?? null,
                    'notes' => $phone['notes'] ?? null,
                ];
            })
            ->filter(fn ($phone) => $phone['phone_number'] !== '')
            ->values();

        if ($phoneNumbers->isNotEmpty()) {
            $primaryCount = $phoneNumbers->where('is_primary', true)->count();

            if ($primaryCount !== 1) {
                throw ValidationException::withMessages([
                    'phone_numbers' => 'Exactly one phone number must be marked as primary.',
                ]);
            }
        }

        $existingIds = $person->phoneNumbers()->pluck('id')->toArray();
        $incomingIds = $phoneNumbers->pluck('id')->filter()->map(fn ($id) => (int) $id)->toArray();

        $toDelete = array_diff($existingIds, $incomingIds);

        if (!empty($toDelete)) {
            PersonPhoneNumber::where('person_id', $person->id)
                ->whereIn('id', $toDelete)
                ->delete();
        }

        foreach ($phoneNumbers as $phone) {
            if (!empty($phone['id'])) {
                PersonPhoneNumber::where('person_id', $person->id)
                    ->where('id', $phone['id'])
                    ->update([
                        'phone_number' => $phone['phone_number'],
                        'phone_type' => $phone['phone_type'],
                        'is_primary' => $phone['is_primary'],
                        'extension' => $phone['extension'],
                        'notes' => $phone['notes'],
                    ]);
            } else {
                PersonPhoneNumber::create([
                    'person_id' => $person->id,
                    'phone_number' => $phone['phone_number'],
                    'phone_type' => $phone['phone_type'],
                    'is_primary' => $phone['is_primary'],
                    'extension' => $phone['extension'],
                    'notes' => $phone['notes'],
                ]);
            }
        }
    }

    public function createForPerson(Person $person, array $phoneNumbersInput = []): void
    {
        $this->sync($person, $phoneNumbersInput);
    }

    public function getPrimaryPhoneNumber(Person $person): ?PersonPhoneNumber
    {
        return $person->phoneNumbers()
            ->where('is_primary', true)
            ->first();
    }

    public function normalizeForForm(Collection|array $phoneNumbers): array
    {
        $items = collect($phoneNumbers)
            ->map(function ($phone) {
                return [
                    'id' => $phone['id'] ?? null,
                    'phone_number' => $phone['phone_number'] ?? '',
                    'phone_type' => $phone['phone_type'] ?? '',
                    'is_primary' => (bool) ($phone['is_primary'] ?? false),
                    'extension' => $phone['extension'] ?? '',
                    'notes' => $phone['notes'] ?? '',
                ];
            })
            ->values();

        if ($items->isEmpty()) {
            return [[
                'id' => null,
                'phone_number' => '',
                'phone_type' => '',
                'is_primary' => true,
                'extension' => '',
                'notes' => '',
            ]];
        }

        if ($items->where('is_primary', true)->count() === 0) {
            $first = $items->first();
            $first['is_primary'] = true;
            $items[0] = $first;
        }

        return $items->toArray();
    }
}