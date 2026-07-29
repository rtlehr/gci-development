<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Services\AlertService;
use App\Services\UserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request, UserResolver $userResolver): Response
    {
        $userId = $userResolver->resolveUserId();

        $tickets = Ticket::query()
            ->where('submitted_by_user_id', $userId)
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim($request->string('search')->toString());

                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Ticket $ticket): array => $this->ticketSummary($ticket));

        return Inertia::render('Portal/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request, UserResolver $userResolver): Response
    {
        $user = $userResolver->resolveUser();
        $person = $userResolver->resolvePerson();

        $displayName = trim(($person?->preferred_name ?: $person?->first_name ?: '').' '.($person?->last_name ?: ''));

        return Inertia::render('Portal/Tickets/Create', [
            'currentUser' => [
                'id' => $user->id,
                'username' => $displayName !== '' ? $displayName : $user->name,
                'email' => $user->email,
                'person_code' => $person?->person_code,
            ],
            'sourceUrl' => $request->query('source_url', ''),
            'categories' => ['UI', 'Data', 'Permissions', 'Workflow', 'Performance', 'Other'],
        ]);
    }

    public function store(
        Request $request,
        UserResolver $userResolver,
        AlertService $alertService,
    ): RedirectResponse {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'request_type' => ['required', 'in:bug,improvement'],
            'importance' => ['required', 'in:show_stopper,asap,nice_to_have'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'source_url' => ['nullable', 'string', 'max:2048'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ]);

        $userId = $userResolver->resolveUserId();
        $nextId = (Ticket::max('id') ?? 0) + 1;

        $ticket = Ticket::create([
            'ticket_number' => 'TCK-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT),
            'title' => $validated['title'],
            'submitted_by_user_id' => $userId,
            'assigned_to_user_id' => null,
            'request_type' => $validated['request_type'],
            'importance' => $validated['importance'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'],
            'source_url' => $validated['source_url'] ?? null,
            'screenshot_path' => $request->hasFile('screenshot')
                ? $request->file('screenshot')->store('ticket-screenshots', 'public')
                : null,
            'status' => 'new',
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'changed_by_user_id' => $userId,
            'event_type' => 'created',
            'comment' => 'Ticket created.',
        ]);

        $alertService->assignTicketToTeam(
            ticket: $ticket,
            teamName: 'DEVELOPER',
            actionUrl: route('admin.tickets.show', $ticket),
        );

        Artisan::call('alerts:send-emails');

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('success', "Request submitted successfully. Ticket {$ticket->ticket_number} created.");
    }

    public function show(Ticket $ticket, UserResolver $userResolver): Response
    {
        abort_unless(
            (int) $ticket->submitted_by_user_id === (int) $userResolver->resolveUserId(),
            404,
        );

        $ticket->load([
            'assignedTo.person',
            'activities' => fn ($query) => $query
                ->whereIn('event_type', [
                    'created',
                    'status_changed',
                    'importance_changed',
                    'assignment_changed',
                    'resolution_updated',
                ])
                ->with('changedBy.person')
                ->oldest(),
        ]);

        return Inertia::render('Portal/Tickets/Show', [
            'ticket' => [
                ...$this->ticketSummary($ticket),
                'description' => $ticket->description,
                'source_url' => $ticket->source_url,
                'screenshot_url' => $ticket->screenshot_path
                    ? Storage::disk('public')->url($ticket->screenshot_path)
                    : null,
                'resolution_notes' => $ticket->resolution_notes,
                'assigned_to' => $this->displayName($ticket->assignedTo),
                'activities' => $ticket->activities->map(fn (TicketActivity $activity): array => [
                    'id' => $activity->id,
                    'event_type' => $activity->event_type,
                    'field_name' => $activity->field_name,
                    'old_value' => $activity->old_value,
                    'new_value' => $activity->new_value,
                    'created_at' => $activity->created_at,
                    'changed_by' => $this->displayName($activity->changedBy),
                ])->values(),
            ],
        ]);
    }

    private function ticketSummary(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'title' => $ticket->title,
            'request_type' => $ticket->request_type,
            'importance' => $ticket->importance,
            'category' => $ticket->category,
            'status' => $ticket->status,
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->updated_at,
        ];
    }

    private function displayName($user): ?string
    {
        if (! $user) {
            return null;
        }

        $person = $user->person;
        $name = trim(($person?->preferred_name ?: $person?->first_name ?: '').' '.($person?->last_name ?: ''));

        return $name !== '' ? $name : $user->name;
    }
}
