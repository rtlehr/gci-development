<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Services\UserResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
                'person_code' => $person->person_code,
            ],
            'sourceUrl' => $request->input('source_url', url()->previous()),
        ]);
    }

    public function store(Request $request, UserResolver $userResolver)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'request_type' => ['required', 'in:bug,improvement'],
            'importance' => ['required', 'in:show_stopper,asap,nice_to_have'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'source_url' => ['nullable', 'string'],
            'screenshot' => ['nullable', 'image', 'max:5120'], // 5MB
        ]);

        $userId = $userResolver->resolveUserId();

        $nextId = (Ticket::max('id') ?? 0) + 1;
        $ticketNumber = 'TCK-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        $screenshotPath = null;

        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('ticket-screenshots', 'public');
        }

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'title' => $validated['title'],
            'submitted_by_user_id' => $userId,
            'request_type' => $validated['request_type'],
            'importance' => $validated['importance'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'],
            'source_url' => $validated['source_url'] ?? null,
            'screenshot_path' => $screenshotPath,
            'status' => 'new',
        ]);

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
}