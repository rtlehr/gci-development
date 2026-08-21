<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\JobTitle;
use App\Models\Person;
use App\Models\Position;
use App\Models\Ticket;
use App\Models\UserEventLog;
use App\Models\Workflow;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserEventLinkResolver
{
    /**
     * Resolve display labels and safe destinations for a page of audit events.
     * Known record ids are loaded in batches to avoid an N+1 query pattern.
     *
     * @param  Collection<int, UserEventLog>  $events
     * @return Collection<int, array{label: string, href: string|null, type: string|null}>
     */
    public function resolveMany(Collection $events): Collection
    {
        $ids = [
            'position' => [],
            'person' => [],
            'candidate' => [],
            'ticket' => [],
            'workflow' => [],
            'jobTitle' => [],
        ];

        foreach ($events as $event) {
            [$type, $id] = $this->inferTarget($event);

            if ($type && $id && array_key_exists($type, $ids)) {
                $ids[$type][] = $id;
            }
        }

        $positions = Position::query()
            ->whereKey(array_unique($ids['position']))
            ->get(['id', 'position_code', 'job_title'])
            ->keyBy('id');

        $people = Person::query()
            ->whereKey(array_unique($ids['person']))
            ->get(['id', 'person_code', 'first_name', 'preferred_name', 'last_name'])
            ->keyBy('id');

        $candidates = Candidate::query()
            ->with([
                'person:id,first_name,preferred_name,last_name',
                'position:id,position_code,job_title',
            ])
            ->whereKey(array_unique($ids['candidate']))
            ->get(['id', 'candidate_code', 'person_id', 'position_id'])
            ->keyBy('id');

        $tickets = Ticket::query()
            ->whereKey(array_unique($ids['ticket']))
            ->get(['id', 'ticket_number', 'title'])
            ->keyBy('id');

        $workflows = Workflow::query()
            ->whereKey(array_unique($ids['workflow']))
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        $jobTitles = JobTitle::query()
            ->whereKey(array_unique($ids['jobTitle']))
            ->get(['id', 'name'])
            ->keyBy('id');

        return $events->mapWithKeys(function (UserEventLog $event) use (
            $positions,
            $people,
            $candidates,
            $tickets,
            $workflows,
            $jobTitles,
        ): array {
            [$type, $id] = $this->inferTarget($event);
            $resolved = null;

            if ($type === 'position' && $id && ($position = $positions->get($id))) {
                $label = trim(($position->position_code ?: "Position {$position->id}").($position->job_title ? " — {$position->job_title}" : ''));
                $resolved = ['label' => $label, 'href' => "/portal/positions/{$position->id}", 'type' => 'Position'];
            } elseif ($type === 'person' && $id && ($person = $people->get($id))) {
                $name = trim(($person->preferred_name ?: $person->first_name ?: '').' '.($person->last_name ?: ''));
                $label = $name !== '' ? $name : ($person->person_code ?: "Person {$person->id}");
                $resolved = ['label' => $label, 'href' => "/portal/people/{$person->id}", 'type' => 'Person'];
            } elseif ($type === 'candidate' && $id && ($candidate = $candidates->get($id))) {
                $person = $candidate->person;
                $personName = $person
                    ? trim(($person->preferred_name ?: $person->first_name ?: '').' '.($person->last_name ?: ''))
                    : '';
                $positionLabel = $candidate->position?->position_code ?: null;
                $parts = array_values(array_filter([$personName, $positionLabel]));
                $label = $parts ? implode(' — ', $parts).' Workflow' : ($candidate->candidate_code ?: "Candidate {$candidate->id}");
                $resolved = ['label' => $label, 'href' => "/portal/candidates/{$candidate->id}", 'type' => 'Candidate Workflow'];
            } elseif ($type === 'ticket' && $id && ($ticket = $tickets->get($id))) {
                $number = $ticket->ticket_number ?: "Ticket #{$ticket->id}";
                $label = trim($number.($ticket->title ? " — {$ticket->title}" : ''));
                $resolved = ['label' => $label, 'href' => "/admin/tickets/{$ticket->id}", 'type' => 'Support Ticket'];
            } elseif ($type === 'workflow' && $id && ($workflow = $workflows->get($id))) {
                $label = trim(($workflow->code ? "{$workflow->code} — " : '').($workflow->name ?: "Workflow {$workflow->id}"));
                $resolved = ['label' => $label, 'href' => "/workflows/{$workflow->id}/edit", 'type' => 'Workflow'];
            } elseif ($type === 'jobTitle' && $id && ($jobTitle = $jobTitles->get($id))) {
                $resolved = ['label' => $jobTitle->name ?: "Job Title {$jobTitle->id}", 'href' => "/portal/job-titles/{$jobTitle->id}", 'type' => 'Job Title'];
            }

            if (! $resolved) {
                $resolved = $this->fallback($event);
            }

            return [$event->id => $resolved];
        });
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function inferTarget(UserEventLog $event): array
    {
        $subjectType = Str::lower(class_basename((string) $event->subject_type));
        $subjectId = $this->integerId($event->subject_id);

        $subjectMap = [
            'position' => 'position',
            'person' => 'person',
            'candidate' => 'candidate',
            'ticket' => 'ticket',
            'workflow' => 'workflow',
            'jobtitle' => 'jobTitle',
        ];

        if ($subjectId && isset($subjectMap[$subjectType])) {
            return [$subjectMap[$subjectType], $subjectId];
        }

        $parameters = $event->route_parameters ?? [];
        $route = (string) $event->route_name;

        $candidates = [
            'candidate' => ['candidate'],
            'position' => ['position', 'id'],
            'person' => ['person', 'id'],
            'ticket' => ['ticket'],
            'workflow' => ['workflow'],
            'jobTitle' => ['jobTitle', 'job_title'],
        ];

        foreach ($candidates as $type => $keys) {
            if (! $this->routeSuggestsType($route, $type)) {
                continue;
            }

            foreach ($keys as $key) {
                $id = $this->integerId($parameters[$key] ?? null);

                if ($id) {
                    return [$type, $id];
                }
            }
        }

        return [null, null];
    }

    private function routeSuggestsType(string $route, string $type): bool
    {
        return match ($type) {
            'position' => str_contains($route, 'position'),
            'person' => str_contains($route, 'people') || str_contains($route, 'person'),
            'candidate' => str_contains($route, 'candidate'),
            'ticket' => str_contains($route, 'ticket'),
            'workflow' => str_contains($route, 'workflow'),
            'jobTitle' => str_contains($route, 'job-title'),
            default => false,
        };
    }

    /**
     * @return array{label: string, href: string|null, type: string|null}
     */
    private function fallback(UserEventLog $event): array
    {
        $label = $event->subject_label
            ?: $this->friendlyRouteName($event->route_name)
            ?: $event->description
            ?: 'Application activity';

        $href = null;

        if (Str::upper((string) $event->http_method) === 'GET'
            && is_string($event->path)
            && Str::startsWith($event->path, '/')
            && ! Str::startsWith($event->path, '//')) {
            $href = $event->path;
        }

        return [
            'label' => $label,
            'href' => $href,
            'type' => $event->module ? Str::headline($event->module) : null,
        ];
    }

    private function friendlyRouteName(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        return Str::headline(str_replace('.', ' ', $routeName));
    }

    private function integerId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
