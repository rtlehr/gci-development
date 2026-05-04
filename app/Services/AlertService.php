<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;

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
        ?array $metadata = null
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
        int|string $ticketId,
        ?string $actionUrl = null
    ): Alert {
        return $this->createForUser(
            user: $user,
            title: 'Ticket Assigned',
            message: "You have been assigned ticket #{$ticketId}.",
            actionUrl: $actionUrl,
            type: 'ticket_assignment',
            priority: 'normal',
            sourceType: 'ticket',
            sourceId: (int) $ticketId
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
}