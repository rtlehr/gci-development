<?php

use App\Models\Permission;
use App\Models\Person;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function portalTicketUser(array $permissions): User
{
    $user = User::factory()->create();

    Person::query()->create([
        'user_id' => $user->id,
        'person_code' => 'PORTAL-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
        'first_name' => 'Portal',
        'last_name' => 'User '.$user->id,
        'email' => $user->email,
    ]);

    $permissionIds = Permission::query()
        ->whereIn('name', $permissions)
        ->pluck('id');

    $user->permissions()->sync($permissionIds);
    app(PermissionService::class)->clearUserPermissionCache($user->id);

    return $user;
}

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('shows only tickets submitted by the current portal user', function () {
    $user = portalTicketUser(['portal_view_own_tickets']);
    $otherUser = User::factory()->create();

    Ticket::query()->create([
        'ticket_number' => 'TCK-000001',
        'title' => 'My ticket',
        'submitted_by_user_id' => $user->id,
        'request_type' => 'bug',
        'importance' => 'nice_to_have',
        'description' => 'My request',
        'status' => 'new',
    ]);

    Ticket::query()->create([
        'ticket_number' => 'TCK-000002',
        'title' => 'Another user ticket',
        'submitted_by_user_id' => $otherUser->id,
        'request_type' => 'improvement',
        'importance' => 'asap',
        'description' => 'Private request',
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->get(route('portal.tickets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Tickets/Index')
            ->has('tickets.data', 1)
            ->where('tickets.data.0.ticket_number', 'TCK-000001')
        );
});

it('allows a portal user to submit a ticket', function () {
    $user = portalTicketUser(['portal_create_tickets', 'portal_view_own_tickets']);

    $response = $this->actingAs($user)->post(route('portal.tickets.store'), [
        'title' => 'Portal submission',
        'request_type' => 'bug',
        'importance' => 'asap',
        'category' => 'UI',
        'description' => 'The page is not displaying correctly.',
        'source_url' => 'http://localhost/portal/dashboard',
    ]);

    $ticket = Ticket::query()->firstOrFail();

    $response->assertRedirect(route('portal.tickets.show', $ticket));

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'submitted_by_user_id' => $user->id,
        'title' => 'Portal submission',
    ]);

    $this->assertDatabaseHas('ticket_activities', [
        'ticket_id' => $ticket->id,
        'event_type' => 'created',
    ]);
});

it('allows a submitter to view their own portal ticket', function () {
    $user = portalTicketUser(['portal_view_own_tickets']);

    $ticket = Ticket::query()->create([
        'ticket_number' => 'TCK-000003',
        'title' => 'Visible ticket',
        'submitted_by_user_id' => $user->id,
        'request_type' => 'bug',
        'importance' => 'nice_to_have',
        'description' => 'Visible details',
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->get(route('portal.tickets.show', $ticket))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Tickets/Show')
            ->where('ticket.ticket_number', 'TCK-000003')
            ->where('ticket.description', 'Visible details')
        );
});

it('does not expose another users ticket through the portal', function () {
    $user = portalTicketUser(['portal_view_own_tickets']);
    $otherUser = User::factory()->create();

    $ticket = Ticket::query()->create([
        'ticket_number' => 'TCK-000004',
        'title' => 'Private ticket',
        'submitted_by_user_id' => $otherUser->id,
        'request_type' => 'bug',
        'importance' => 'nice_to_have',
        'description' => 'Private details',
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->get(route('portal.tickets.show', $ticket))
        ->assertNotFound();
});

it('requires the portal ticket permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('portal.tickets.index'))
        ->assertForbidden();
});
