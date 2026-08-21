<?php

namespace App\Services;

use App\Models\UserEventLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class UserEventLogger
{
    public function __construct(
        private readonly CurrentUserContext $currentUser,
    ) {
    }

    /**
     * Record a meaningful user event. Request context is filled automatically
     * when this service is called during an HTTP request.
     *
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>|null  $routeParameters
     */
    public function record(
        string $eventType,
        ?string $module = null,
        ?string $action = null,
        ?Model $subject = null,
        ?string $subjectLabel = null,
        ?string $description = null,
        array $metadata = [],
        ?string $routeName = null,
        ?array $routeParameters = null,
    ): ?UserEventLog {
        if (! config('user-event-log.enabled', true)) {
            return null;
        }

        $user = $this->currentUser->user();

        if (! $user) {
            return null;
        }

        $person = $this->currentUser->person();
        $request = app()->bound('request') ? request() : null;

        $event = UserEventLog::query()->create([
            'occurred_at' => now(),
            'user_id' => $user->id,
            'person_id' => $person?->id,
            'user_name' => $this->displayName($user->name, $person?->first_name, $person?->preferred_name, $person?->last_name),
            'user_email' => $user->email,
            'event_type' => Str::limit($eventType, 80, ''),
            'module' => $module ? Str::limit($module, 100, '') : null,
            'action' => $action ? Str::limit($action, 100, '') : null,
            'route_name' => $routeName ?? $request?->route()?->getName(),
            'route_parameters' => $this->sanitizeRouteParameters(
                $routeParameters ?? $request?->route()?->parameters() ?? [],
            ),
            'path' => $request ? Str::limit('/'.ltrim($request->path(), '/'), 2048, '') : null,
            'http_method' => $request?->method(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject ? (string) $subject->getRouteKey() : null,
            'subject_label' => $subjectLabel ?? $this->inferSubjectLabel($subject),
            'description' => $description,
            'metadata' => $this->sanitizeMetadata($metadata),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'session_identifier' => $this->sessionIdentifier($request),
            'request_identifier' => $this->requestIdentifier($request),
        ]);

        if ($request) {
            $request->attributes->set('_irad_user_event_logged', true);
        }

        return $event;
    }


    /**
     * Record a business event against a specific application record.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<int, string>  $trackedFields
     * @param  array<string, mixed>  $metadata
     */
    public function recordModelEvent(
        string $eventType,
        string $module,
        string $action,
        Model $subject,
        string $description,
        array $before = [],
        array $after = [],
        array $trackedFields = [],
        array $metadata = [],
        ?string $subjectLabel = null,
    ): ?UserEventLog {
        $changes = $this->buildChanges($before, $after, $trackedFields);

        if ($changes !== []) {
            $metadata['changes'] = $changes;
        }

        return $this->record(
            eventType: $eventType,
            module: $module,
            action: $action,
            subject: $subject,
            subjectLabel: $subjectLabel,
            description: $description,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<int, string>  $trackedFields
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public function buildChanges(array $before, array $after, array $trackedFields = []): array
    {
        $keys = $trackedFields !== []
            ? $trackedFields
            : array_values(array_unique(array_merge(array_keys($before), array_keys($after))));

        $changes = [];

        foreach ($keys as $key) {
            if ($this->isSensitiveKey((string) $key)) {
                continue;
            }

            $from = $this->safeMetadataValue($before[$key] ?? null);
            $to = $this->safeMetadataValue($after[$key] ?? null);

            if ($from === $to) {
                continue;
            }

            $changes[(string) $key] = ['from' => $from, 'to' => $to];
        }

        return $changes;
    }

    public function recordPageView(Request $request): ?UserEventLog
    {
        $routeName = $request->route()?->getName();

        return $this->record(
            eventType: 'page_view',
            module: $this->moduleFromRoute($routeName),
            action: 'view',
            description: $routeName ? 'Viewed '.$routeName : 'Viewed page',
            routeName: $routeName,
            routeParameters: $request->route()?->parameters() ?? [],
        );
    }

    public function recordFallbackRequest(Request $request): ?UserEventLog
    {
        $routeName = $request->route()?->getName();
        $action = $this->actionFromRequest($request, $routeName);

        return $this->record(
            eventType: $this->eventTypeFromRequest($request, $routeName),
            module: $this->moduleFromRoute($routeName),
            action: $action,
            description: $routeName
                ? Str::headline(str_replace('.', ' ', $routeName))
                : 'Performed application action',
            routeName: $routeName,
            routeParameters: $request->route()?->parameters() ?? [],
        );
    }

    public function currentRequestHasLoggedEvent(Request $request): bool
    {
        return (bool) $request->attributes->get('_irad_user_event_logged', false);
    }

    private function eventTypeFromRequest(Request $request, ?string $routeName): string
    {
        $routeName = Str::lower((string) $routeName);

        if (str_contains($routeName, 'export')) {
            return 'export';
        }

        return match (Str::upper($request->method())) {
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            'POST' => str_ends_with($routeName, '.store') ? 'create' : 'action',
            default => 'action',
        };
    }

    private function actionFromRequest(Request $request, ?string $routeName): string
    {
        if ($routeName) {
            $parts = explode('.', $routeName);
            $last = end($parts);

            if (is_string($last) && $last !== '') {
                return Str::limit($last, 100, '');
            }
        }

        return Str::lower($request->method());
    }

    private function moduleFromRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $parts = explode('.', $routeName);

        if (($parts[0] ?? null) === 'admin' && isset($parts[1])) {
            return $parts[1];
        }

        return $parts[0] ?? null;
    }

    /**
     * Keep route context useful for reconstructing links without retaining
     * credentials, tokens, or arbitrary object payloads.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, scalar|null>
     */
    private function sanitizeRouteParameters(array $parameters): array
    {
        $clean = [];

        foreach ($parameters as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                continue;
            }

            if ($value instanceof Model) {
                $value = $value->getRouteKey();
            }

            if (is_scalar($value) || $value === null) {
                $clean[(string) $key] = is_string($value)
                    ? Str::limit($value, 255, '')
                    : $value;
            }
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $clean = [];

        foreach ($metadata as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                continue;
            }

            $clean[$key] = is_array($value)
                ? $this->sanitizeMetadata($value)
                : $this->safeMetadataValue($value);
        }

        return $clean;
    }


    private function safeMetadataValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $value->getRouteKey();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value)) {
            return Str::limit($value, 1000, '');
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = Str::lower($key);

        foreach (['password', 'secret', 'token', 'authorization', 'cookie', 'csrf'] as $term) {
            if (str_contains($key, $term)) {
                return true;
            }
        }

        return false;
    }

    private function requestIdentifier(?Request $request): string
    {
        if (! $request) {
            return (string) Str::uuid();
        }

        $existing = $request->attributes->get('_irad_user_event_request_id');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $identifier = (string) Str::uuid();
        $request->attributes->set('_irad_user_event_request_id', $identifier);

        return $identifier;
    }

    private function sessionIdentifier(?Request $request): ?string
    {
        if (! $request || ! $request->hasSession()) {
            return null;
        }

        try {
            $id = $request->session()->getId();

            return $id !== '' ? hash('sha256', $id) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function inferSubjectLabel(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        foreach (['title', 'name', 'label', 'position_code', 'person_code'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return Str::limit((string) $value, 255, '');
            }
        }

        return null;
    }

    private function displayName(?string $userName, ?string $firstName, ?string $preferredName, ?string $lastName): ?string
    {
        $personName = trim(($preferredName ?: $firstName ?: '').' '.($lastName ?: ''));

        return $personName !== '' ? $personName : $userName;
    }
}
