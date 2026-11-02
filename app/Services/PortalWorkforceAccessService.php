<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PortalWorkforceAccessService
{
    public function __construct(
        private readonly CurrentUserContext $currentUser,
    ) {
    }

    public function user(): User
    {
        return $this->currentUser->user()
            ?? abort(401, 'An authenticated user is required.');
    }

    public function canViewAllPositions(): bool
    {
        return $this->currentUser->hasPermission('portal_view_all_positions');
    }

    public function canViewAssignedPositions(): bool
    {
        return $this->currentUser->hasPermission('portal_view_assigned_positions');
    }

    public function canViewCandidatePositions(): bool
    {
        return $this->currentUser->hasPermission('portal_view_candidate_positions');
    }

    public function canViewCandidateProgress(): bool
    {
        return $this->currentUser->hasPermission('portal_view_candidate_progress');
    }

    public function scopePositions(Builder $query): Builder
    {
        if ($this->canViewAllPositions()) {
            return $query;
        }

        if ($this->canViewAssignedPositions()) {
            return $query->where(
                'project_manager_user_id',
                $this->user()->id,
            );
        }

        if ($this->canViewCandidatePositions()) {
            $personId = $this->currentUser->person()?->id;

            if (! $personId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas(
                'candidates',
                fn (Builder $candidateQuery) => $candidateQuery
                    ->where('person_id', $personId),
            );
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Compatibility entry point while existing callers migrate to Recruitment.
     */
    public function scopeCandidates(Builder $query): Builder
    {
        return app(\App\Services\Recruitment\CandidateAccessService::class)->scopeCandidates($query);
    }

    public function authorizePositionView(Position $position): void
    {
        abort_unless(
            $this->scopePositions(Position::query())
                ->whereKey($position->id)
                ->exists(),
            403,
        );
    }

    /** Compatibility entry point; authorization rules are owned by Recruitment. */
    public function authorizeCandidateView(Candidate $candidate): void
    {
        app(\App\Services\Recruitment\CandidateAccessService::class)->authorizeCandidateView($candidate);
    }
}
