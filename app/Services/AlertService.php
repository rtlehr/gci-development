<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use App\Models\Team;
use App\Models\Ticket;

class AlertService
{
    public function createForUser(
        User $user,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        string $type = 'general',
        string $priority = 'normal',
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null,
        bool $shouldEmail = false
    ): Alert {
        return Alert::create([
            'user_id' => $user->id,
            'person_id' => $user->person?->id,
            'type' => $type,
            'priority' => $priority,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'metadata' => $metadata,
            'should_email' => $shouldEmail,
        ]);
    }

    public function assignmentCreated(
        User $user,
        string $itemName,
        ?string $actionUrl = null,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): Alert {
        return $this->createForUser(
            user: $user,
            title: 'New Assignment',
            message: "You have been assigned: {$itemName}.",
            actionUrl: $actionUrl,
            type: 'assignment',
            priority: 'normal',
            sourceType: $sourceType,
            sourceId: $sourceId
        );
    }

    public function ticketAssigned(
        User $user,
        int $ticketDatabaseId,
        string $ticketNumber,
        ?string $actionUrl = null
    ): Alert {
        return $this->createForUser(
            user: $user,
            title: 'Ticket Assigned',
            message: "You have been assigned ticket {$ticketNumber}.",
            actionUrl: $actionUrl,
            type: 'ticket_assignment',
            priority: 'normal',
            sourceType: 'ticket',
            sourceId: $ticketDatabaseId,
            shouldEmail: true
        );
    }

    public function workflowActionRequired(
        User $user,
        string $workflowName,
        ?string $actionUrl = null,
        ?int $sourceId = null
    ): Alert {
        return $this->createForUser(
            user: $user,
            title: 'Workflow Action Required',
            message: "You have a pending action in {$workflowName}.",
            actionUrl: $actionUrl,
            type: 'workflow',
            priority: 'high',
            sourceType: 'workflow',
            sourceId: $sourceId
        );
    }

    public function ticketCreatedForTeam(
    string $teamName,
    int $ticketDatabaseId,
    string $ticketNumber,
    ?string $actionUrl = null
    ): void {
        $team = Team::query()
            ->with('people.user')
            ->where('team_name', $teamName)
            ->first();

        if (! $team) {
            return;
        }

        foreach ($team->people as $person) {
            if (! $person->user) {
                continue;
            }

            $this->ticketAssigned(
                user: $person->user,
                ticketDatabaseId: $ticketDatabaseId,
                ticketNumber: $ticketNumber,
                actionUrl: $actionUrl
            );
        }
    }

    public function assignTicketToTeam(
        Ticket $ticket,
        string $teamName,
        ?string $actionUrl = null
    ): void {
        $team = Team::query()
            ->with('people.user')
            ->where('team_name', $teamName)
            ->first();

        if (! $team) {
            return;
        }

        $userIds = $team->people
            ->filter(fn ($person) => $person->user)
            ->pluck('user.id')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        // Assign ticket to all users on the team
        $ticket->assignedUsers()->sync($userIds);

        foreach ($team->people as $person) {
            if (! $person->user) {
                continue;
            }

            $this->ticketAssigned(
                user: $person->user,
                ticketDatabaseId: $ticket->id,
                ticketNumber: $ticket->ticket_number,
                actionUrl: $actionUrl
            );
        }
    }

    public function reassignTicketToUser(
        Ticket $ticket,
        User $user,
        ?string $actionUrl = null
    ): void {
        // Remove all previous assigned users and assign only the new user
        $ticket->assignedUsers()->sync([$user->id]);

        // Mark old unread ticket alerts as read for everyone except new assignee
        Alert::where('source_type', 'ticket')
            ->where('source_id', $ticket->id)
            ->whereNull('read_at')
            ->where('user_id', '!=', $user->id)
            ->update([
                'read_at' => now(),
            ]);

        $this->ticketAssigned(
            user: $user,
            ticketDatabaseId: $ticket->id,
            ticketNumber: $ticket->ticket_number,
            actionUrl: $actionUrl
        );
    }

}