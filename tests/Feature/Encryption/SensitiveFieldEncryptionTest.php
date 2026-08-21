<?php

use App\Models\Address;
use App\Models\Candidate;
use App\Models\CandidateStepEvent;
use App\Models\Person;
use App\Models\PersonNote;
use App\Models\PersonPhoneNumber;
use App\Models\Position;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('data-encryption.driver', 'laravel');
    config()->set('data-encryption.allow_plaintext_fallback', false);
});

it('stores selected people and contact details encrypted at rest while returning plaintext through eloquent', function () {
    $person = Person::factory()->create(['notes' => 'Sensitive personnel note']);

    $address = Address::query()->create([
        'person_id' => $person->id,
        'address_type' => 'home',
        'line_1' => '123 Example Street',
        'city' => 'Arlington',
        'state' => 'VA',
        'postal_code' => '22201',
        'country' => 'USA',
        'is_primary' => true,
        'notes' => 'Use side entrance',
    ]);

    $phone = PersonPhoneNumber::query()->create([
        'person_id' => $person->id,
        'phone_number' => '555-0100',
        'phone_type' => 'work',
        'is_primary' => true,
        'extension' => '1234',
        'notes' => 'Direct line',
    ]);

    $note = PersonNote::query()->create([
        'person_id' => $person->id,
        'entered_by_user_id' => null,
        'entered_by_name' => 'Tester',
        'category' => PersonNote::CATEGORY_GENERAL,
        'note' => 'Private note body',
    ]);

    expect(DB::table('people')->where('id', $person->id)->value('notes'))
        ->toStartWith('irad:v1:laravel:k1:')
        ->not->toContain('Sensitive personnel note')
        ->and(DB::table('addresses')->where('id', $address->id)->value('line_1'))
        ->toStartWith('irad:v1:laravel:k1:')
        ->not->toContain('123 Example Street')
        ->and(DB::table('person_phone_numbers')->where('id', $phone->id)->value('extension'))
        ->toStartWith('irad:v1:laravel:k1:')
        ->and(DB::table('person_notes')->where('id', $note->id)->value('note'))
        ->toStartWith('irad:v1:laravel:k1:');

    expect($person->fresh()->notes)->toBe('Sensitive personnel note')
        ->and($address->fresh()->line_1)->toBe('123 Example Street')
        ->and($address->fresh()->city)->toBe('Arlington')
        ->and($phone->fresh()->extension)->toBe('1234')
        ->and($note->fresh()->note)->toBe('Private note body');

    // phone_number remains searchable/sortable until the Step 3 lookup strategy.
    expect(DB::table('person_phone_numbers')->where('id', $phone->id)->value('phone_number'))
        ->toBe('555-0100');
});

it('stores candidate notes and comments encrypted at rest', function () {
    $person = Person::factory()->create();
    $position = Position::factory()->create();
    $workflow = Workflow::query()->create(['code' => 'enc-test', 'name' => 'Encryption Test', 'is_active' => true]);
    $step = WorkflowStep::query()->create([
        'workflow_id' => $workflow->id,
        'code' => 'enc-step',
        'name' => 'Encryption Step',
        'step_order' => 1,
        'is_active' => true,
    ]);
    $candidate = Candidate::query()->create([
        'candidate_code' => 'ENC-CAND-1',
        'person_id' => $person->id,
        'position_id' => $position->id,
        'workflow_id' => $workflow->id,
        'status' => 'submitted',
    ]);

    $event = CandidateStepEvent::query()->create([
        'candidate_id' => $candidate->id,
        'workflow_step_id' => $step->id,
        'notes' => 'Sensitive candidate note',
        'comments' => 'Sensitive candidate comment',
        'metadata' => ['structural' => true],
    ]);

    expect(DB::table('candidate_step_events')->where('id', $event->id)->value('notes'))
        ->toStartWith('irad:v1:laravel:k1:')
        ->not->toContain('Sensitive candidate note')
        ->and(DB::table('candidate_step_events')->where('id', $event->id)->value('comments'))
        ->toStartWith('irad:v1:laravel:k1:')
        ->and($event->fresh()->notes)->toBe('Sensitive candidate note')
        ->and($event->fresh()->comments)->toBe('Sensitive candidate comment')
        ->and($event->fresh()->metadata)->toBe(['structural' => true]);
});

it('stores selected ticket details and activity values encrypted at rest', function () {
    $user = User::factory()->create();

    $ticket = Ticket::query()->create([
        'ticket_number' => 'TCK-ENC-001',
        'title' => 'Searchable ticket title',
        'submitted_by_user_id' => $user->id,
        'request_type' => 'bug',
        'importance' => 'asap',
        'category' => 'Data',
        'description' => 'Searchable description remains plaintext in Step 2',
        'source_url' => 'https://irad.example.test/private/path',
        'status' => 'new',
        'resolution_notes' => 'Sensitive resolution details',
    ]);

    $activity = TicketActivity::query()->create([
        'ticket_id' => $ticket->id,
        'changed_by_user_id' => $user->id,
        'event_type' => 'updated',
        'field_name' => 'resolution_notes',
        'old_value' => 'Old sensitive value',
        'new_value' => 'New sensitive value',
        'comment' => 'Private ticket comment',
    ]);

    expect(DB::table('tickets')->where('id', $ticket->id)->value('source_url'))
        ->toStartWith('irad:v1:laravel:k1:')
        ->and(DB::table('tickets')->where('id', $ticket->id)->value('resolution_notes'))
        ->toStartWith('irad:v1:laravel:k1:')
        ->and(DB::table('ticket_activities')->where('id', $activity->id)->value('comment'))
        ->toStartWith('irad:v1:laravel:k1:')
        ->and($ticket->fresh()->source_url)->toBe('https://irad.example.test/private/path')
        ->and($ticket->fresh()->resolution_notes)->toBe('Sensitive resolution details')
        ->and($activity->fresh()->comment)->toBe('Private ticket comment');

    expect(DB::table('tickets')->where('id', $ticket->id)->value('description'))
        ->toBe('Searchable description remains plaintext in Step 2');
});

it('migrates legacy plaintext values idempotently and supports pretend mode', function () {
    $personId = DB::table('people')->insertGetId([
        'first_name' => 'Legacy',
        'last_name' => 'Person',
        'notes' => 'Legacy plaintext note',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Artisan::call('irad:encrypt-existing-data', ['--pretend' => true]);

    expect(DB::table('people')->where('id', $personId)->value('notes'))
        ->toBe('Legacy plaintext note');

    Artisan::call('irad:encrypt-existing-data');
    $encrypted = DB::table('people')->where('id', $personId)->value('notes');

    expect($encrypted)
        ->toStartWith('irad:v1:laravel:k1:')
        ->and(Person::query()->findOrFail($personId)->notes)->toBe('Legacy plaintext note');

    Artisan::call('irad:encrypt-existing-data');

    expect(DB::table('people')->where('id', $personId)->value('notes'))
        ->toBe($encrypted);
});
