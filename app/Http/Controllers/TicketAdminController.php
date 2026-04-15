<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Services\UserResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketAdminController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $importance = $request->input('importance', '');
        $requestType = $request->input('request_type', '');
        $assignedTo = $request->input('assigned_to_user_id', '');

        $tickets = Ticket::query()
            ->with([
                'submittedBy.person',
                'assignedTo.person',
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($importance, function ($query, $importance) {
                $query->where('importance', $importance);
            })
            ->when($requestType, function ($query, $requestType) {
                $query->where('request_type', $requestType);
            })
            ->when($assignedTo, function ($query, $assignedTo) {
                if ($assignedTo === 'unassigned') {
                    $query->whereNull('assigned_to_user_id');
                } else {
                    $query->where('assigned_to_user_id', $assignedTo);
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $assignableUsers = User::query()
            ->with('person')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $displayName = trim(
                    ($user->person->first_name ?? '') . ' ' . ($user->person->last_name ?? '')
                );

                if ($displayName === '') {
                    $displayName = $user->name;
                }

                return [
                    'id' => $user->id,
                    'name' => $displayName,
                ];
            })
            ->values();

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
            'assignableUsers' => $assignableUsers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'importance' => $importance,
                'request_type' => $requestType,
                'assigned_to_user_id' => $assignedTo,
            ],
        ]);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'submittedBy.person',
            'assignedTo.person',
            'activities.changedBy.person',
        ]);

        $assignableUsers = User::query()
            ->with('person')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $displayName = trim(
                    ($user->person->first_name ?? '') . ' ' . ($user->person->last_name ?? '')
                );

                if ($displayName === '') {
                    $displayName = $user->name;
                }

                return [
                    'id' => $user->id,
                    'name' => $displayName,
                ];
            })
            ->values();

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $ticket,
            'assignableUsers' => $assignableUsers,
        ]);
    }

    public function update(Request $request, Ticket $ticket, UserResolver $userResolver)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,in_progress,on_hold,complete,canceled'],
            'importance' => ['required', 'in:show_stopper,asap,nice_to_have'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'resolution_notes' => ['nullable', 'string'],
        ]);

        $changedByUserId = $userResolver->resolveUserId();

        $originalStatus = $ticket->status;
        $originalImportance = $ticket->importance;
        $originalAssignedTo = $ticket->assigned_to_user_id;
        $originalResolutionNotes = $ticket->resolution_notes;

        $ticket->update([
            'status' => $validated['status'],
            'importance' => $validated['importance'],
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'resolution_notes' => $validated['resolution_notes'] ?? null,
        ]);

        if ($originalStatus !== $ticket->status) {
            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'changed_by_user_id' => $changedByUserId,
                'event_type' => 'status_changed',
                'field_name' => 'status',
                'old_value' => $originalStatus,
                'new_value' => $ticket->status,
            ]);
        }

        if ($originalImportance !== $ticket->importance) {
            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'changed_by_user_id' => $changedByUserId,
                'event_type' => 'importance_changed',
                'field_name' => 'importance',
                'old_value' => $originalImportance,
                'new_value' => $ticket->importance,
            ]);
        }

        if ((string) $originalAssignedTo !== (string) $ticket->assigned_to_user_id) {
            $oldAssignedUser = $originalAssignedTo
                ? User::with('person')->find($originalAssignedTo)
                : null;

            $newAssignedUser = $ticket->assigned_to_user_id
                ? User::with('person')->find($ticket->assigned_to_user_id)
                : null;

            $oldAssignedName = $oldAssignedUser
                ? trim(($oldAssignedUser->person->first_name ?? '') . ' ' . ($oldAssignedUser->person->last_name ?? ''))
                : 'Unassigned';

            if ($oldAssignedName === '' && $oldAssignedUser) {
                $oldAssignedName = $oldAssignedUser->name;
            }

            $newAssignedName = $newAssignedUser
                ? trim(($newAssignedUser->person->first_name ?? '') . ' ' . ($newAssignedUser->person->last_name ?? ''))
                : 'Unassigned';

            if ($newAssignedName === '' && $newAssignedUser) {
                $newAssignedName = $newAssignedUser->name;
            }

            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'changed_by_user_id' => $changedByUserId,
                'event_type' => 'assignment_changed',
                'field_name' => 'assigned_to_user_id',
                'old_value' => $oldAssignedName,
                'new_value' => $newAssignedName,
            ]);
        }

        if (($originalResolutionNotes ?? '') !== ($ticket->resolution_notes ?? '')) {
            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'changed_by_user_id' => $changedByUserId,
                'event_type' => 'resolution_updated',
                'field_name' => 'resolution_notes',
                'old_value' => $originalResolutionNotes,
                'new_value' => $ticket->resolution_notes,
            ]);
        }

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with('success', 'Ticket updated successfully.');
    }

    public function addComment(Request $request, Ticket $ticket, UserResolver $userResolver)
    {
        $validated = $request->validate([
            'comment' => ['required', 'string'],
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'changed_by_user_id' => $userResolver->resolveUserId(),
            'event_type' => 'comment_added',
            'comment' => $validated['comment'],
        ]);

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with('success', 'Comment added successfully.');
    }
}