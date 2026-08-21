<?php

namespace App\Services;

use App\Models\UserEventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserEventLogExportService
{
    /** @var array<int, string> */
    private array $csvColumns = [
        'occurred_at',
        'user_id',
        'user_name',
        'user_email',
        'person_id',
        'event_type',
        'module',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'subject_label',
        'route_name',
        'path',
        'http_method',
        'ip_address',
        'request_identifier',
        'session_identifier',
        'user_agent',
        'route_parameters',
        'metadata',
    ];

    public function csv(Builder $query, string $filenamePrefix): StreamedResponse
    {
        $filename = $filenamePrefix.'-'.now()->format('Y-m-d_H-i-s').'.csv';

        return Response::streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->csvColumns);

            foreach ($query->orderBy('occurred_at')->orderBy('id')->cursor() as $event) {
                fputcsv($handle, array_map(
                    fn (string $column): string => $this->csvValue($event, $column),
                    $this->csvColumns,
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function splunk(Builder $query, string $filenamePrefix): StreamedResponse
    {
        $filename = $filenamePrefix.'-'.now()->format('Y-m-d_H-i-s').'.ndjson';
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'irad';

        return Response::streamDownload(function () use ($query, $host): void {
            foreach ($query->orderBy('occurred_at')->orderBy('id')->cursor() as $event) {
                echo json_encode([
                    'time' => $event->occurred_at?->getTimestamp(),
                    'host' => $host,
                    'source' => 'irad',
                    'sourcetype' => 'irad:user_event',
                    'event' => $this->eventPayload($event),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
            }
        }, $filename, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function csvValue(UserEventLog $event, string $column): string
    {
        $value = $event->{$column};

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        return $value === null ? '' : (string) $value;
    }

    /** @return array<string, mixed> */
    private function eventPayload(UserEventLog $event): array
    {
        return [
            'id' => $event->id,
            'occurred_at' => $event->occurred_at?->toISOString(),
            'user_id' => $event->user_id,
            'user_name' => $event->user_name,
            'user_email' => $event->user_email,
            'person_id' => $event->person_id,
            'event_type' => $event->event_type,
            'module' => $event->module,
            'action' => $event->action,
            'description' => $event->description,
            'subject_type' => $event->subject_type,
            'subject_id' => $event->subject_id,
            'subject_label' => $event->subject_label,
            'route_name' => $event->route_name,
            'route_parameters' => $event->route_parameters ?? [],
            'path' => $event->path,
            'http_method' => $event->http_method,
            'ip_address' => $event->ip_address,
            'user_agent' => $event->user_agent,
            'session_identifier' => $event->session_identifier,
            'request_identifier' => $event->request_identifier,
            'metadata' => $event->metadata ?? [],
        ];
    }
}
