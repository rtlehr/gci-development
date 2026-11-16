<?php

namespace Insite\Modules\Positions\Services;

use App\Models\Person;
use App\Models\Position;
use App\Models\Workflow;
use Illuminate\Support\Collection;

/** Candidate-person and active-workflow choices shared by both module edit pages. */
class PositionCandidateEditOptions
{
    public function candidateOptions(Position $position): Collection
    {
        $candidatePersonIds = $position->candidates
            ->pluck('person_id')
            ->filter()
            ->values();

        return Person::query()
            ->with('primaryPhoneNumber:id,person_id,phone_number,extension')
            ->when($candidatePersonIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $candidatePersonIds))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'preferred_name', 'last_name', 'email', 'person_code'])
            ->map(fn (Person $person) => [
                'id' => $person->id,
                'full_name' => trim(($person->preferred_name ?: $person->first_name).' '.$person->last_name),
                'email' => $person->email,
                'person_code' => $person->person_code,
                'primary_phone' => $person->primaryPhoneNumber?->phone_number,
                'primary_phone_extension' => $person->primaryPhoneNumber?->extension,
            ])
            ->values();
    }

    public function workflows(): Collection
    {
        return Workflow::query()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_primary'])
            ->map(fn (Workflow $workflow) => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'code' => $workflow->code,
                'is_primary' => (bool) $workflow->is_primary,
            ])
            ->values();
    }
}
