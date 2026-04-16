<?php

namespace App\Services;

use App\Models\Person;
use App\Models\Address;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AddressService
{
    public function sync(Person $person, array $addressesInput = []): void
    {
        $addresses = collect($addressesInput)
            ->map(function ($address) {
                return [
                    'id' => $address['id'] ?? null,
                    'address_type' => $address['address_type'] ?? null,
                    'line_1' => trim((string) ($address['line_1'] ?? '')),
                    'line_2' => trim((string) ($address['line_2'] ?? '')),
                    'city' => trim((string) ($address['city'] ?? '')),
                    'state' => trim((string) ($address['state'] ?? '')),
                    'postal_code' => trim((string) ($address['postal_code'] ?? '')),
                    'country' => trim((string) ($address['country'] ?? '')),
                    'is_primary' => (bool) ($address['is_primary'] ?? false),
                    'notes' => $address['notes'] ?? null,
                ];
            })
            ->filter(fn ($address) => $address['line_1'] !== '')
            ->values();

        if ($addresses->isNotEmpty()) {
            $primaryCount = $addresses->where('is_primary', true)->count();

            if ($primaryCount !== 1) {
                throw ValidationException::withMessages([
                    'addresses' => 'Exactly one address must be marked as primary.',
                ]);
            }
        }

        $existingIds = $person->addresses()->pluck('id')->toArray();
        $incomingIds = $addresses->pluck('id')->filter()->map(fn ($id) => (int) $id)->toArray();

        $toDelete = array_diff($existingIds, $incomingIds);

        if (!empty($toDelete)) {
            Address::where('person_id', $person->id)
                ->whereIn('id', $toDelete)
                ->delete();
        }

        foreach ($addresses as $address) {
            if (!empty($address['id'])) {
                Address::where('person_id', $person->id)
                    ->where('id', $address['id'])
                    ->update([
                        'address_type' => $address['address_type'],
                        'line_1' => $address['line_1'],
                        'line_2' => $address['line_2'] ?: null,
                        'city' => $address['city'] ?: null,
                        'state' => $address['state'] ?: null,
                        'postal_code' => $address['postal_code'] ?: null,
                        'country' => $address['country'] ?: null,
                        'is_primary' => $address['is_primary'],
                        'notes' => $address['notes'],
                    ]);
            } else {
                Address::create([
                    'person_id' => $person->id,
                    'address_type' => $address['address_type'],
                    'line_1' => $address['line_1'],
                    'line_2' => $address['line_2'] ?: null,
                    'city' => $address['city'] ?: null,
                    'state' => $address['state'] ?: null,
                    'postal_code' => $address['postal_code'] ?: null,
                    'country' => $address['country'] ?: null,
                    'is_primary' => $address['is_primary'],
                    'notes' => $address['notes'],
                ]);
            }
        }
    }

    public function createForPerson(Person $person, array $addressesInput = []): void
    {
        $this->sync($person, $addressesInput);
    }

    public function normalizeForForm(Collection|array $addresses): array
    {
        $items = collect($addresses)
            ->map(function ($address) {
                return [
                    'id' => $address['id'] ?? null,
                    'address_type' => $address['address_type'] ?? '',
                    'line_1' => $address['line_1'] ?? '',
                    'line_2' => $address['line_2'] ?? '',
                    'city' => $address['city'] ?? '',
                    'state' => $address['state'] ?? '',
                    'postal_code' => $address['postal_code'] ?? '',
                    'country' => $address['country'] ?? 'USA',
                    'is_primary' => (bool) ($address['is_primary'] ?? false),
                    'notes' => $address['notes'] ?? '',
                ];
            })
            ->values();

        if ($items->isEmpty()) {
            return [[
                'id' => null,
                'address_type' => '',
                'line_1' => '',
                'line_2' => '',
                'city' => '',
                'state' => '',
                'postal_code' => '',
                'country' => 'USA',
                'is_primary' => true,
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