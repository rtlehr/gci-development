<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Services\AlertService;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $alertService = app(AlertService::class);

        $tickets = [
            [
                'ticket_number' => 'TCK-000101',
                'title' => 'Dashboard ticket widget not refreshing',
                'request_type' => 'bug',
                'importance' => 'asap',
                'category' => 'UI',
                'description' => 'The dashboard ticket widget does not always update after a ticket assignment changes.',
                'source_url' => url('/dashboard'),
            ],
            [
                'ticket_number' => 'TCK-000102',
                'title' => 'Add export option to candidate list',
                'request_type' => 'improvement',
                'importance' => 'nice_to_have',
                'category' => 'Workflow',
                'description' => 'Add a CSV export option to the candidate list using the same list export pattern used elsewhere.',
                'source_url' => url('/candidates'),
            ],
        ];

        foreach ($tickets as $ticketData) {
            $ticket = Ticket::updateOrCreate(
                [
                    'ticket_number' => $ticketData['ticket_number'],
                ],
                [
                    'title' => $ticketData['title'],
                    'submitted_by_user_id' => 1,
                    'assigned_to_user_id' => null,
                    'request_type' => $ticketData['request_type'],
                    'importance' => $ticketData['importance'],
                    'category' => $ticketData['category'],
                    'description' => $ticketData['description'],
                    'source_url' => $ticketData['source_url'],
                    'screenshot_path' => null,
                    'status' => 'new',
                    'resolution_notes' => null,
                ]
            );

            TicketActivity::updateOrCreate(
                [
                    'ticket_id' => $ticket->id,
                    'event_type' => 'created',
                    'comment' => 'Seeded ticket created.',
                ],
                [
                    'changed_by_user_id' => 1,
                ]
            );

            $alertService->assignTicketToTeam(
                ticket: $ticket,
                teamName: 'DEVELOPER',
                actionUrl: route('admin.tickets.show', $ticket)
            );
        }
    }
}