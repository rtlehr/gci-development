<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Ticket;
use App\Services\ProjectManagerDashboardService;
use App\Services\UserResolver;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        UserResolver $userResolver,
        ProjectManagerDashboardService $projectManagerDashboardService,
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
            ->limit(10)
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

        $assignedPositions = $projectManagerDashboardService->positionsFor($user);

        return Inertia::render('Portal/Dashboard', [
            'alerts' => Alert::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->latest()
                ->limit(10)
                ->get(),
            'assignedTickets' => $assignedTickets,
            'assignedPositions' => $assignedPositions,
        ]);
    }
}
