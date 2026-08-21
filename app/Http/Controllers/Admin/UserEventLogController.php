<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserEventLog;
use App\Services\UserEventLinkResolver;
use App\Services\UserEventLogExportService;
use App\Services\UserEventLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserEventLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $query = UserEventLog::query();

        if (! empty($filters['from'])) {
            $query->whereDate('occurred_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('occurred_at', '<=', $filters['to']);
        }

        $days = $query
            ->selectRaw('DATE(occurred_at) as event_date')
            ->selectRaw('COUNT(*) as event_count')
            ->selectRaw('COUNT(DISTINCT user_id) as user_count')
            ->selectRaw('MIN(occurred_at) as first_activity_at')
            ->selectRaw('MAX(occurred_at) as last_activity_at')
            ->groupByRaw('DATE(occurred_at)')
            ->orderByDesc('event_date')
            ->paginate(30)
            ->withQueryString()
            ->through(fn ($day): array => [
                'date' => (string) $day->event_date,
                'user_count' => (int) $day->user_count,
                'event_count' => (int) $day->event_count,
                'first_activity_at' => $day->first_activity_at,
                'last_activity_at' => $day->last_activity_at,
            ]);

        $today = now()->toDateString();
        $todayQuery = UserEventLog::query()->whereDate('occurred_at', $today);

        return Inertia::render('Admin/UserEventLog/Index', [
            'days' => $days,
            'filters' => [
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
            'today' => [
                'date' => $today,
                'users' => (clone $todayQuery)->distinct('user_id')->count('user_id'),
                'events' => (clone $todayQuery)->count(),
                'changes' => (clone $todayQuery)->whereIn('event_type', ['create', 'update', 'delete'])->count(),
                'exports' => (clone $todayQuery)->where(function (Builder $query): void {
                    $query->where('event_type', 'export')
                        ->orWhere('action', 'export')
                        ->orWhere('route_name', 'like', '%.export.%');
                })->count(),
            ],
        ]);
    }

    public function day(Request $request, string $date): Response
    {
        $date = $this->validatedDate($date);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = UserEventLog::query()
            ->whereDate('occurred_at', $date);

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->select(['user_id', 'user_name', 'user_email'])
            ->selectRaw('COUNT(*) as event_count')
            ->selectRaw('MIN(occurred_at) as first_activity_at')
            ->selectRaw('MAX(occurred_at) as last_activity_at')
            ->groupBy('user_id', 'user_name', 'user_email')
            ->orderByDesc('event_count')
            ->orderBy('user_name')
            ->paginate(40)
            ->withQueryString()
            ->through(fn ($user): array => [
                'user_id' => $user->user_id ? (int) $user->user_id : null,
                'name' => $user->user_name ?: ($user->user_email ?: 'Unknown user'),
                'email' => $user->user_email,
                'event_count' => (int) $user->event_count,
                'first_activity_at' => $user->first_activity_at,
                'last_activity_at' => $user->last_activity_at,
            ]);

        return Inertia::render('Admin/UserEventLog/Day', [
            'date' => $date,
            'users' => $users,
            'filters' => [
                'search' => $filters['search'] ?? '',
            ],
        ]);
    }

    public function user(
        Request $request,
        string $date,
        int $user,
        UserEventLinkResolver $linkResolver,
    ): Response {
        $date = $this->validatedDate($date);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:100'],
            'event_type' => ['nullable', 'string', 'max:80'],
        ]);

        $baseQuery = UserEventLog::query()
            ->whereDate('occurred_at', $date)
            ->where('user_id', $user);

        $identity = (clone $baseQuery)
            ->latest('occurred_at')
            ->first(['user_id', 'user_name', 'user_email']);

        abort_unless($identity, 404);

        $eventsQuery = clone $baseQuery;

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $eventsQuery->where(function (Builder $query) use ($search): void {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_label', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['module'])) {
            $eventsQuery->where('module', $filters['module']);
        }

        if (! empty($filters['event_type'])) {
            $eventsQuery->where('event_type', $filters['event_type']);
        }

        /** @var LengthAwarePaginator $events */
        $events = $eventsQuery
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->paginate(75)
            ->withQueryString();

        $resolvedLinks = $linkResolver->resolveMany($events->getCollection());

        $events->setCollection(
            $events->getCollection()->map(function (UserEventLog $event) use ($resolvedLinks): array {
                $detail = $resolvedLinks->get($event->id);

                return [
                    'id' => $event->id,
                    'occurred_at' => $event->occurred_at?->toISOString(),
                    'event_type' => $event->event_type,
                    'module' => $event->module,
                    'action' => $event->action,
                    'description' => $event->description,
                    'route_name' => $event->route_name,
                    'path' => $event->path,
                    'http_method' => $event->http_method,
                    'subject_label' => $event->subject_label,
                    'ip_address' => $event->ip_address,
                    'metadata' => $event->metadata ?? [],
                    'detail' => $detail,
                ];
            }),
        );

        $modules = (clone $baseQuery)
            ->whereNotNull('module')
            ->where('module', '!=', '')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->values();

        $eventTypes = (clone $baseQuery)
            ->whereNotNull('event_type')
            ->where('event_type', '!=', '')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type')
            ->values();

        return Inertia::render('Admin/UserEventLog/UserActivity', [
            'date' => $date,
            'user' => [
                'id' => (int) $identity->user_id,
                'name' => $identity->user_name ?: ($identity->user_email ?: "User {$identity->user_id}"),
                'email' => $identity->user_email,
            ],
            'events' => $events,
            'modules' => $modules,
            'eventTypes' => $eventTypes,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'module' => $filters['module'] ?? '',
                'event_type' => $filters['event_type'] ?? '',
            ],
        ]);
    }


    public function exportRange(
        Request $request,
        string $format,
        UserEventLogExportService $exports,
        UserEventLogger $logger,
    ): StreamedResponse {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $query = UserEventLog::query();

        if (! empty($filters['from'])) {
            $query->whereDate('occurred_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('occurred_at', '<=', $filters['to']);
        }

        $this->freezeQuery($query);
        $this->logExport($logger, $format, 'date_range', $filters);

        return $this->exportResponse($exports, $query, $format, 'irad-user-event-log');
    }

    public function exportDay(
        Request $request,
        string $date,
        string $format,
        UserEventLogExportService $exports,
        UserEventLogger $logger,
    ): StreamedResponse {
        $date = $this->validatedDate($date);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = UserEventLog::query()->whereDate('occurred_at', $date);
        $this->applyUserSearch($query, (string) ($filters['search'] ?? ''));
        $this->freezeQuery($query);
        $this->logExport($logger, $format, 'day', ['date' => $date, ...$filters]);

        return $this->exportResponse($exports, $query, $format, "irad-user-event-log-{$date}");
    }

    public function exportUser(
        Request $request,
        string $date,
        int $user,
        string $format,
        UserEventLogExportService $exports,
        UserEventLogger $logger,
    ): StreamedResponse {
        $date = $this->validatedDate($date);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:100'],
            'event_type' => ['nullable', 'string', 'max:80'],
        ]);

        $query = UserEventLog::query()
            ->whereDate('occurred_at', $date)
            ->where('user_id', $user);

        abort_unless((clone $query)->exists(), 404);
        $this->applyActivityFilters($query, $filters);
        $this->freezeQuery($query);
        $this->logExport($logger, $format, 'user_day', ['date' => $date, 'user_id' => $user, ...$filters]);

        return $this->exportResponse($exports, $query, $format, "irad-user-event-log-{$date}-user-{$user}");
    }

    /** @param array<string, mixed> $filters */
    private function applyActivityFilters(Builder $query, array $filters): void
    {
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_label', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }
    }

    private function applyUserSearch(Builder $query, string $search): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query->where('user_name', 'like', "%{$search}%")
                ->orWhere('user_email', 'like', "%{$search}%");
        });
    }

    private function freezeQuery(Builder $query): void
    {
        $maxId = (clone $query)->max('id');
        $query->where('id', '<=', $maxId ?? 0);
    }

    /** @param array<string, mixed> $metadata */
    private function logExport(UserEventLogger $logger, string $format, string $scope, array $metadata): void
    {
        $logger->record(
            eventType: 'export',
            module: 'user_event_log',
            action: "export_{$format}",
            description: 'Exported User Event Log '.strtoupper($format),
            metadata: ['scope' => $scope, 'format' => $format, 'filters' => $metadata],
        );
    }

    private function exportResponse(
        UserEventLogExportService $exports,
        Builder $query,
        string $format,
        string $filenamePrefix,
    ): StreamedResponse {
        abort_unless(in_array($format, ['csv', 'splunk'], true), 404);

        return $format === 'csv'
            ? $exports->csv($query, $filenamePrefix)
            : $exports->splunk($query, $filenamePrefix);
    }

    private function validatedDate(string $date): string
    {
        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date);
        } catch (\Throwable) {
            abort(404);
        }

        abort_unless($parsed && $parsed->format('Y-m-d') === $date, 404);

        return $date;
    }
}
