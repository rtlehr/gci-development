<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Models\UserListPreference;
use App\Services\AlertService;
use App\Services\ListEngine;
use App\Services\ListExportService;
use App\Services\UserResolver;
use App\Services\UserEventLogger;
use App\Support\ListDefinitions\TicketsDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAdminController extends Controller
{
    public function index(
        Request $request,
        UserResolver $userResolver,
        ListEngine $listEngine
    ) {
        $definition = TicketsDefinition::get();
        $userId = $userResolver->resolveUserId();

        $list = $listEngine->run(
            request: $request,
            definition: $definition,
            userId: $userId,
            query: Ticket::query()
                ->leftJoin('users as submitted_users', 'submitted_users.id', '=', 'tickets.submitted_by_user_id')
                ->leftJoin('people as submitted_people', 'submitted_people.user_id', '=', 'submitted_users.id')
                ->leftJoin('users as assigned_users', 'assigned_users.id', '=', 'tickets.assigned_to_user_id')
                ->leftJoin('people as assigned_people', 'assigned_people.user_id', '=', 'assigned_users.id')
                ->with([
                    'submittedBy.person',
                    'assignedTo.person',
                    'assignedUsers.person',
                ])
                ->select('tickets.*')
                ->selectRaw("
                    TRIM(
                        CONCAT(
                            COALESCE(submitted_people.first_name, ''),
                            ' ',
                            COALESCE(submitted_people.last_name, '')
                        )
                    ) as submitted_by_name
                ")
                ->selectRaw("
                    TRIM(
                        CONCAT(
                            COALESCE(assigned_people.first_name, ''),
                            ' ',
                            COALESCE(assigned_people.last_name, '')
                        )
                    ) as assigned_to_name
                "),
            filterCallback: function ($query, $request) {
                $search = $request->input('search', '');
                $status = $request->input('status', '');
                $importance = $request->input('importance', '');
                $requestType = $request->input('request_type', '');
                $assignedTo = $request->input('assigned_to_user_id', '');

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('tickets.ticket_number', 'like', "%{$search}%")
                            ->orWhere('tickets.title', 'like', "%{$search}%")
                            ->orWhere('tickets.description', 'like', "%{$search}%");
                    });
                }

                if ($status) {
                    $query->where('tickets.status', $status);
                }

                if ($importance) {
                    $query->where('tickets.importance', $importance);
                }

                if ($requestType) {
                    $query->where('tickets.request_type', $requestType);
                }

                if ($assignedTo) {
                    if ($assignedTo === 'unassigned') {
                        $query->whereDoesntHave('assignedUsers');
                    } else {
                        $query->whereHas('assignedUsers', function ($q) use ($assignedTo) {
                            $q->where('users.id', $assignedTo);
                        });
                    }
                }
            }
        );

        $assignableUsers = $this->getAssignableTicketUsers();

        $list['filters']['search'] = $request->input('search', '');
        $list['filters']['status'] = $request->input('status', '');
        $list['filters']['importance'] = $request->input('importance', '');
        $list['filters']['request_type'] = $request->input('request_type', '');
        $list['filters']['assigned_to_user_id'] = $request->input('assigned_to_user_id', '');

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $list['rows'],
            'columns' => $list['columns'],
            'visibleColumns' => $list['visibleColumns'],
            'columnOrder' => $list['columnOrder'],
            'filters' => $list['filters'],
            'sort' => $list['sort'],
            'direction' => $list['direction'],
            'assignableUsers' => $assignableUsers,
        ]);
    }

    public function show(Ticket $ticket, UserResolver $userResolver)
    {
        $ticket->load([
            'submittedBy.person',
            'assignedTo.person',
            'assignedUsers.person',
            'watchers.person',
            'activities.changedBy.person',
        ]);

        $currentUser = $userResolver->resolveUser();

        $currentUserDisplayName = trim(
            ($currentUser->person->first_name ?? '') . ' ' . ($currentUser->person->last_name ?? '')
        );

        if ($currentUserDisplayName === '') {
            $currentUserDisplayName = $currentUser->name;
        }

        $assignableUsers = $this->getAssignableTicketUsers();

            $currentUser = $userResolver->resolveUser();

            $currentUserDisplayName = trim(
                ($currentUser->person->first_name ?? '') . ' ' . ($currentUser->person->last_name ?? '')
            );

            if ($currentUserDisplayName === '') {
                $currentUserDisplayName = $currentUser->name;
            }

            $isWatching = $ticket->watchers
                ->contains('id', $currentUser->id);

            return Inertia::render('Admin/Tickets/Show', [
                'ticket' => $ticket,
                'assignableUsers' => $assignableUsers,
                'currentUser' => [
                    'id' => $currentUser->id,
                    'name' => $currentUserDisplayName,
                ],
                'isWatching' => $isWatching,
            ]);
    }

    public function update(
        Request $request,
        Ticket $ticket,
        UserResolver $userResolver,
        AlertService $alertService
    ) {
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

            if ($newAssignedUser) {
                $alertService->reassignTicketToUser(
                    ticket: $ticket,
                    user: $newAssignedUser,
                    actionUrl: route('admin.tickets.show', $ticket)
                );

                Artisan::call('alerts:send-emails');
            }
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

        if (
            $originalStatus !== $ticket->status ||
            $originalImportance !== $ticket->importance ||
            (string) $originalAssignedTo !== (string) $ticket->assigned_to_user_id ||
            ($originalResolutionNotes ?? '') !== ($ticket->resolution_notes ?? '')
        ) {
            $alertService->ticketChangedForWatchers(
                ticket: $ticket,
                changedByUserId: $changedByUserId,
                changeMessage: "Ticket {$ticket->ticket_number} has been updated.",
                actionUrl: route('admin.tickets.show', $ticket)
            );

            $alertService->ticketChangedForSubmitter(
                ticket: $ticket,
                changedByUserId: $changedByUserId,
                changeMessage: "Ticket {$ticket->ticket_number} has been updated.",
                actionUrl: route('portal.tickets.show', $ticket)
            );
        }

        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'update', module: 'tickets', action: 'update',
            subject: $ticket,
            subjectLabel: trim($ticket->ticket_number.' — '.$ticket->title),
            description: 'Updated support ticket '.$ticket->ticket_number.'.',
            // Keep audit metadata structured without duplicating the sensitive
            // resolution text into the User Event Log.
            before: [
                'status' => $originalStatus,
                'importance' => $originalImportance,
                'assigned_to_user_id' => $originalAssignedTo,
                'has_resolution_notes' => filled($originalResolutionNotes),
            ],
            after: [
                'status' => $ticket->status,
                'importance' => $ticket->importance,
                'assigned_to_user_id' => $ticket->assigned_to_user_id,
                'has_resolution_notes' => filled($ticket->resolution_notes),
            ],
        );

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with('success', 'Ticket updated successfully.');
    }

    public function addComment(
        Request $request,
        Ticket $ticket,
        UserResolver $userResolver,
        AlertService $alertService
    )
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

        $changedByUserId = $userResolver->resolveUserId();

        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'update', module: 'tickets', action: 'comment',
            subject: $ticket,
            subjectLabel: trim($ticket->ticket_number.' — '.$ticket->title),
            description: 'Added a comment to '.$ticket->ticket_number.'.',
            metadata: ['comment_added' => true],
        );

        $alertService->ticketChangedForWatchers(
            ticket: $ticket,
            changedByUserId: $changedByUserId,
            changeMessage: "A comment was added to ticket {$ticket->ticket_number}.",
            actionUrl: route('admin.tickets.show', $ticket)
        );

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with('success', 'Comment added successfully.');
    }

    public function savePreferences(Request $request, UserResolver $userResolver)
    {
        $definition = TicketsDefinition::get();
        $validKeys = collect($definition['columns'])->pluck('key')->toArray();

        $validated = $request->validate([
            'visible_columns' => ['required', 'array'],
            'visible_columns.*' => ['string'],
            'column_order' => ['required', 'array'],
            'column_order.*' => ['string'],
        ]);

        $visibleColumns = collect($validated['visible_columns'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $columnOrder = collect($validated['column_order'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $userId = $userResolver->resolveUserId();

        UserListPreference::updateOrCreate(
            [
                'user_id' => $userId,
                'list_key' => $definition['list_key'],
            ],
            [
                'visible_columns' => $visibleColumns,
                'column_order' => $columnOrder,
            ]
        );

        return redirect()
            ->route('admin.tickets.index')
            ->with('success', 'Column preferences saved.');
    }

    public function resetPreferences(UserResolver $userResolver)
    {
        $definition = TicketsDefinition::get();
        $userId = $userResolver->resolveUserId();

        UserListPreference::where('user_id', $userId)
            ->where('list_key', $definition['list_key'])
            ->delete();

        return redirect()
            ->route('admin.tickets.index')
            ->with('success', 'Column preferences reset to defaults.');
    }

    public function exportCsv(
        Request $request,
        ListExportService $listExportService
    ): StreamedResponse {
        app(UserEventLogger::class)->record(
            eventType: 'export', module: 'tickets', action: 'export',
            description: 'Exported support tickets to CSV.',
            metadata: ['format' => 'csv', 'filters' => $request->only(['search','status','importance','request_type','assigned_to_user_id'])],
        );

        return $listExportService->exportCsv(
            request: $request,
            definition: TicketsDefinition::get(),
            query: Ticket::query()
                ->leftJoin('users as submitted_users', 'submitted_users.id', '=', 'tickets.submitted_by_user_id')
                ->leftJoin('people as submitted_people', 'submitted_people.user_id', '=', 'submitted_users.id')
                ->leftJoin('users as assigned_users', 'assigned_users.id', '=', 'tickets.assigned_to_user_id')
                ->leftJoin('people as assigned_people', 'assigned_people.user_id', '=', 'assigned_users.id')
                ->with([
                    'submittedBy.person',
                    'assignedTo.person',
                    'assignedUsers.person',
                ])
                ->select('tickets.*')
                ->selectRaw("
                    TRIM(
                        CONCAT(
                            COALESCE(submitted_people.first_name, ''),
                            ' ',
                            COALESCE(submitted_people.last_name, '')
                        )
                    ) as submitted_by_name
                ")
                ->selectRaw("
                    TRIM(
                        CONCAT(
                            COALESCE(assigned_people.first_name, ''),
                            ' ',
                            COALESCE(assigned_people.last_name, '')
                        )
                    ) as assigned_to_name
                "),
            filenamePrefix: 'tickets-export',
            filterCallback: function ($query, $request) {
                $search = $request->input('search', '');
                $status = $request->input('status', '');
                $importance = $request->input('importance', '');
                $requestType = $request->input('request_type', '');
                $assignedTo = $request->input('assigned_to_user_id', '');

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('tickets.ticket_number', 'like', "%{$search}%")
                            ->orWhere('tickets.title', 'like', "%{$search}%")
                            ->orWhere('tickets.description', 'like', "%{$search}%");
                    });
                }

                if ($status) {
                    $query->where('tickets.status', $status);
                }

                if ($importance) {
                    $query->where('tickets.importance', $importance);
                }

                if ($requestType) {
                    $query->where('tickets.request_type', $requestType);
                }

                if ($assignedTo) {
                    if ($assignedTo === 'unassigned') {
                        $query->whereDoesntHave('assignedUsers');
                    } else {
                        $query->whereHas('assignedUsers', function ($q) use ($assignedTo) {
                            $q->where('users.id', $assignedTo);
                        });
                    }
                }
            }
        );
    }

    public function watch(Ticket $ticket, UserResolver $userResolver)
    {
        $userId = $userResolver->resolveUserId();

        $ticket->watchers()->syncWithoutDetaching([$userId]);
        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'update', module: 'tickets', action: 'watch', subject: $ticket,
            subjectLabel: trim($ticket->ticket_number.' — '.$ticket->title),
            description: 'Started watching '.$ticket->ticket_number.'.'
        );

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with('success', 'You are now watching this ticket.');
    }

    public function unwatch(Ticket $ticket, UserResolver $userResolver)
    {
        $userId = $userResolver->resolveUserId();

        $ticket->watchers()->detach($userId);
        app(UserEventLogger::class)->recordModelEvent(
            eventType: 'update', module: 'tickets', action: 'unwatch', subject: $ticket,
            subjectLabel: trim($ticket->ticket_number.' — '.$ticket->title),
            description: 'Stopped watching '.$ticket->ticket_number.'.'
        );

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with('success', 'You are no longer watching this ticket.');
    }

    private function getAssignableTicketUsers()
    {
        $requiredPermissions = [
            'access_tickets',
            'read_tickets',
            'update_tickets',
        ];

        return User::query()
            ->with(['person', 'permissions', 'roles.permissions'])
            ->where(function ($query) use ($requiredPermissions) {
                foreach ($requiredPermissions as $permissionName) {
                    $query->where(function ($q) use ($permissionName) {
                        $q->whereHas('permissions', function ($permissionQuery) use ($permissionName) {
                            $permissionQuery->where('name', $permissionName);
                        })
                        ->orWhereHas('roles.permissions', function ($permissionQuery) use ($permissionName) {
                            $permissionQuery->where('name', $permissionName);
                        });
                    });
                }
            })
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
    }

}