<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Services\UserResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\AlertService;
use App\Models\User;

class TicketController extends Controller
{
    public function create(Request $request, UserResolver $userResolver)
    {
        $user = $userResolver->resolveUser();
        $person = $userResolver->resolvePerson();

        $displayName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));

        if ($displayName === '') {
            $displayName = $user->name ?? '';
        }

        return Inertia::render('Tickets/Create', [
            'currentUser' => [
                'id' => $user->id,
                'username' => $displayName,
                'email' => $user->email,
                'person_code' => $person->person_code ?? null,
            ],
            'sourceUrl' => $request->query('source_url', ''),
        ]);
    }

    public function store(Request $request, UserResolver $userResolver, AlertService $alertService)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'request_type' => ['required', 'in:bug,improvement'],
            'importance' => ['required', 'in:show_stopper,asap,nice_to_have'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'source_url' => ['nullable', 'string'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ]);

        $userId = $userResolver->resolveUserId();

        $nextId = (Ticket::max('id') ?? 0) + 1;
        $ticketNumber = 'TCK-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        $screenshotPath = null;

        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('ticket-screenshots', 'public');
        }

        $ownerUser = User::where('email', 'owner@example.com')->firstOrFail();

        $defaultAssignedUser = User::where('email', 'owner@example.com')->first();

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'title' => $validated['title'],
            'submitted_by_user_id' => $userId,
            'assigned_to_user_id' => $defaultAssignedUser?->id,
            'request_type' => $validated['request_type'],
            'importance' => $validated['importance'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'],
            'source_url' => $validated['source_url'] ?? null,
            'screenshot_path' => $screenshotPath,
            'status' => 'new',
        ]);

        if ($defaultAssignedUser) {
            $alertService->ticketAssigned(
                user: $defaultAssignedUser,
                ticketId: $ticket->ticket_number,
                actionUrl: route('admin.tickets.show', $ticket)
            );
        }

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'changed_by_user_id' => $userId,
            'event_type' => 'created',
            'comment' => 'Ticket created.',
        ]);

        return redirect()
            ->route('tickets.create')
            ->with('success', "Request submitted successfully. Ticket {$ticket->ticket_number} created.");
    }

    public function assign(
    Request $request,
    Ticket $ticket,
    AlertService $alertService,
    UserResolver $userResolver
    ) {
        $validated = $request->validate([
            'assigned_to_user_id' => ['required', 'exists:users,id'],
        ]);

        $oldAssignedUserId = $ticket->assigned_to_user_id;
        $newAssignedUserId = (int) $validated['assigned_to_user_id'];

        if ($oldAssignedUserId === $newAssignedUserId) {
            return back()->with('success', 'Ticket is already assigned to that user.');
        }

        $assignedUser = User::findOrFail($newAssignedUserId);

        $ticket->update([
            'assigned_to_user_id' => $assignedUser->id,
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'changed_by_user_id' => $userResolver->resolveUserId(),
            'event_type' => 'assigned',
            'comment' => "Ticket assigned to {$assignedUser->name}.",
        ]);

        $alertService->ticketAssigned(
            user: $assignedUser,
            ticketId: $ticket->ticket_number,
            actionUrl: route('admin.tickets.show', $ticket)
        );

        return back()->with('success', 'Ticket assigned successfully.');
    }

}