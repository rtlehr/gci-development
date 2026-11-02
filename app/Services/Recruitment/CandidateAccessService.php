<?php

namespace App\Services\Recruitment;

use App\Models\Candidate;
use App\Services\CurrentUserContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Transitional Recruitment-owned authorization rules.
 * Permission precedence is intentionally unchanged from PortalWorkforceAccessService.
 */
final class CandidateAccessService
{
    public function __construct(private readonly CurrentUserContext $currentUser) {}

    public function scopeCandidates(Builder $query): Builder
    {
        if ($this->currentUser->hasPermission('portal_view_all_positions')) {
            return $query;
        }

        if ($this->currentUser->hasPermission('portal_view_assigned_positions')) {
            $userId = $this->currentUser->user()?->id;

            if ($userId === null) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas(
                'position',
                fn (Builder $positionQuery) => $positionQuery
                    ->where('project_manager_user_id', $userId),
            );
        }

        if ($this->currentUser->hasPermission('portal_view_candidate_positions')) {
            $personId = $this->currentUser->person()?->id;

            if (! $personId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where('person_id', $personId);
        }

        return $query->whereRaw('1 = 0');
    }

    public function authorizeCandidateView(Candidate $candidate): void
    {
        abort_unless(
            $this->scopeCandidates(Candidate::query())
                ->whereKey($candidate->id)
                ->exists(),
            403,
        );
    }
}
