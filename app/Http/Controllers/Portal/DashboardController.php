<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Ticket;
use App\Services\CurrentUserContext;
use App\Services\ProjectManagerDashboardService;
use App\Services\UserResolver;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        UserResolver $userResolver,
        CurrentUserContext $currentUser,
        ProjectManagerDashboardService $dashboardService,
    ): Response {
        $user = $userResolver->resolveUser();

        $assignedTickets = Ticket::query()
            ->where(function ($query) use ($user) {
                $query->where('assigned_to_user_id', $user->id)
                    ->orWhereHas('assignedUsers', function ($assignedUserQuery) use ($user) {
                        $assignedUserQuery->where('users.id', $user->id);
                    });
            })
            ->whereNotIn('status', ['complete', 'canceled'])
            ->latest()
            ->limit(8)
            ->get([
                'id',
                'ticket_number',
                'title',
                'request_type',
                'importance',
                'category',
                'status',
                'created_at',
            ]);

        $submittedTickets = Ticket::query()
            ->where('submitted_by_user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get([
                'id',
                'ticket_number',
                'title',
                'request_type',
                'importance',
                'category',
                'status',
                'created_at',
            ]);

        $alerts = Alert::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Multiple-role precedence
        |--------------------------------------------------------------------------
        |
        | Permissions are additive. The broadest applicable workforce view wins:
        | all positions, assigned positions, then candidate opportunities.
        |
        */

        $showAllPositions = $currentUser->hasPermission(
            'portal_view_all_positions',
        );

        $showAssignedPositions = ! $showAllPositions
            && $currentUser->hasPermission(
                'portal_view_assigned_positions',
            );

        $showCandidateProgress = ! $showAllPositions
            && ! $showAssignedPositions
            && $currentUser->hasPermission(
                'portal_view_candidate_positions',
            );

        $pmoPositions = $showAllPositions
            ? $dashboardService->allPositionsForPmo()
            : collect();

        $assignedPositions = $showAssignedPositions
            ? $dashboardService->positionsFor($user)
            : collect();

        $candidateOpportunities = $showCandidateProgress
            ? $dashboardService->candidateOpportunitiesFor($user)
            : collect();

        $positionCount = match (true) {
            $showAllPositions => $pmoPositions->count(),
            $showAssignedPositions => $assignedPositions->count(),
            $showCandidateProgress => $candidateOpportunities->count(),
            default => 0,
        };

        $positionLabel = match (true) {
            $showAllPositions => 'all positions',
            $showAssignedPositions => 'assigned positions',
            $showCandidateProgress => 'opportunities',
            default => 'positions',
        };

        return Inertia::render('Portal/Dashboard', [
            'alerts' => $alerts,
            'assignedTickets' => $assignedTickets,
            'submittedTickets' => $submittedTickets,
            'assignedPositions' => $assignedPositions,
            'pmoPositions' => $pmoPositions,
            'candidateOpportunities' => $candidateOpportunities,
            'showPmoPositions' => $showAllPositions,
            'showProjectManagerPositions' => $showAssignedPositions,
            'showCandidateOpportunities' => $showCandidateProgress,
            'summary' => [
                'unreadAlerts' => $alerts->count(),
                'assignedTickets' => $assignedTickets->count(),
                'submittedTickets' => $submittedTickets->count(),
                'assignedPositions' => $positionCount,
                'positionsLabel' => $positionLabel,
            ],
        ]);
    }
}
