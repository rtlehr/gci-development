<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserListPreference;
use App\Services\CurrentUserContext;
use App\Services\ProjectManagerDashboardService;
use App\Services\SiteSettingsService;
use App\Services\UserResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    private const STAFFING_LIST_KEY = 'portal_staffing_matrix_v2';

    public function __invoke(
        UserResolver $userResolver,
        CurrentUserContext $currentUser,
        ProjectManagerDashboardService $dashboardService,
        SiteSettingsService $siteSettings,
    ): Response {
        $user = $userResolver->resolveUser();
        $supportTicketsEnabled = $siteSettings->get('features.support_tickets', true) === true;
        $alertsEnabled = $siteSettings->get('features.alerts', true) === true;

        $assignedTickets = $supportTicketsEnabled
            ? Ticket::query()
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
            ])
            : collect();

        $submittedTickets = $supportTicketsEnabled
            ? Ticket::query()
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
            ])
            : collect();

        $alerts = $alertsEnabled
            ? Alert::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->limit(8)
            ->get()
            : collect();

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

        $staffingPositions = $showAllPositions ? $pmoPositions : $assignedPositions;
        $staffingSummary = $dashboardService->staffingSummary($staffingPositions);
        $staffingColumns = $dashboardService->staffingColumns();
        $staffingPreferences = $this->staffingPreferences($user, $staffingColumns);

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
            'staffingSummary' => $staffingSummary,
            'staffingColumns' => $staffingColumns,
            'staffingVisibleColumns' => $staffingPreferences['visible'],
            'staffingColumnOrder' => $staffingPreferences['order'],
            'summary' => [
                'unreadAlerts' => $alerts->count(),
                'assignedTickets' => $assignedTickets->count(),
                'submittedTickets' => $submittedTickets->count(),
                'assignedPositions' => $positionCount,
                'positionsLabel' => $positionLabel,
            ],
        ]);
    }

    public function saveStaffingPreferences(
        Request $request,
        UserResolver $userResolver,
        ProjectManagerDashboardService $dashboardService,
    ) {
        $validKeys = collect($dashboardService->staffingColumns())
            ->pluck('key')
            ->all();

        $validated = $request->validate([
            'visible_columns' => ['required', 'array', 'min:1'],
            'visible_columns.*' => ['string'],
            'column_order' => ['required', 'array'],
            'column_order.*' => ['string'],
        ]);

        $visibleColumns = collect($validated['visible_columns'])
            ->filter(fn ($key) => in_array($key, $validKeys, true))
            ->unique()
            ->values()
            ->all();

        $columnOrder = collect($validated['column_order'])
            ->filter(fn ($key) => in_array($key, $validKeys, true))
            ->unique()
            ->values();

        foreach ($validKeys as $key) {
            if (! $columnOrder->contains($key)) {
                $columnOrder->push($key);
            }
        }

        UserListPreference::updateOrCreate(
            [
                'user_id' => $userResolver->resolveUserId(),
                'list_key' => self::STAFFING_LIST_KEY,
            ],
            [
                'visible_columns' => $visibleColumns,
                'column_order' => $columnOrder->all(),
            ]
        );

        return back()->with('success', 'Staffing Matrix column preferences saved.');
    }

    public function resetStaffingPreferences(UserResolver $userResolver)
    {
        UserListPreference::query()
            ->where('user_id', $userResolver->resolveUserId())
            ->where('list_key', self::STAFFING_LIST_KEY)
            ->delete();

        return back()->with('success', 'Staffing Matrix columns reset to defaults.');
    }

    public function exportStaffingCsv(
        Request $request,
        UserResolver $userResolver,
        CurrentUserContext $currentUser,
        ProjectManagerDashboardService $dashboardService,
    ): StreamedResponse {
        $user = $userResolver->resolveUser();
        $columns = collect($dashboardService->staffingColumns())->keyBy('key');
        $validKeys = $columns->keys()->all();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'staffing_state' => ['nullable', 'in:vacant,selected,filled,departing,on_hold'],
            'visible_columns' => ['nullable', 'array'],
            'visible_columns.*' => ['string'],
            'column_order' => ['nullable', 'array'],
            'column_order.*' => ['string'],
        ]);

        $positions = $this->staffingPositionsFor(
            $user,
            $currentUser,
            $dashboardService,
        );

        $search = mb_strtolower(trim((string) ($validated['search'] ?? '')));
        $staffingState = $validated['staffing_state'] ?? null;

        $positions = $positions
            ->when($staffingState, fn (Collection $items) => $items
                ->where('staffing_state', $staffingState)
                ->values())
            ->when($search !== '', fn (Collection $items) => $items
                ->filter(fn (array $position) => str_contains(
                    mb_strtolower((string) ($position['search_text'] ?? '')),
                    $search,
                ))
                ->values());

        $requestedOrder = collect($validated['column_order'] ?? [])
            ->filter(fn ($key) => in_array($key, $validKeys, true))
            ->unique();

        if ($requestedOrder->isEmpty()) {
            $requestedOrder = $columns
                ->sortBy('default_order')
                ->pluck('key');
        }

        $requestedVisible = collect($validated['visible_columns'] ?? [])
            ->filter(fn ($key) => in_array($key, $validKeys, true))
            ->unique();

        if ($requestedVisible->isEmpty()) {
            $requestedVisible = $columns
                ->where('default_visible', true)
                ->pluck('key');
        }

        $exportKeys = $requestedOrder
            ->filter(fn ($key) => $requestedVisible->contains($key))
            ->values();

        $filename = 'staffing-matrix-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($positions, $exportKeys, $columns): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, $exportKeys
                ->map(fn ($key) => $columns->get($key)['label'] ?? $key)
                ->all());

            foreach ($positions as $position) {
                fputcsv($output, $exportKeys
                    ->map(fn ($key) => $this->staffingExportValue($position, $key))
                    ->all());
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param array<int, array<string,mixed>> $columns
     * @return array{visible:array<int,string>,order:array<int,string>}
     */
    private function staffingPreferences(User $user, array $columns): array
    {
        $columns = collect($columns);
        $validKeys = $columns->pluck('key');
        $defaultOrder = $columns
            ->sortBy('default_order')
            ->pluck('key')
            ->values();
        $defaultVisible = $columns
            ->where('default_visible', true)
            ->sortBy('default_order')
            ->pluck('key')
            ->values();

        $preference = UserListPreference::query()
            ->where('user_id', $user->id)
            ->where('list_key', self::STAFFING_LIST_KEY)
            ->first();

        if (! $preference) {
            return [
                'visible' => $defaultVisible->all(),
                'order' => $defaultOrder->all(),
            ];
        }

        $visible = collect($preference->visible_columns ?? [])
            ->filter(fn ($key) => $validKeys->contains($key))
            ->values();

        $order = collect($preference->column_order ?? [])
            ->filter(fn ($key) => $validKeys->contains($key))
            ->values();

        foreach ($defaultOrder as $key) {
            if (! $order->contains($key)) {
                $order->push($key);
            }
        }

        return [
            'visible' => ($visible->isEmpty() ? $defaultVisible : $visible)->all(),
            'order' => $order->all(),
        ];
    }

    /** @return Collection<int,array<string,mixed>> */
    private function staffingPositionsFor(
        User $user,
        CurrentUserContext $currentUser,
        ProjectManagerDashboardService $dashboardService,
    ): Collection {
        if ($currentUser->hasPermission('portal_view_all_positions')) {
            return $dashboardService->allPositionsForPmo();
        }

        if ($currentUser->hasPermission('portal_view_assigned_positions')) {
            return $dashboardService->positionsFor($user);
        }

        abort(403);
    }

    private function staffingExportValue(array $position, string $key): mixed
    {
        return match ($key) {
            'workflow_link' => $position['current_workflow_step'] ?? 'No candidate workflow',
            'staffing_state' => $position['staffing_label'] ?? '',
            default => $position[$key] ?? '',
        };
    }
}
