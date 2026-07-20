<?php

use App\Models\Candidate;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    /*
     * Keep the normal web/session middleware enabled so redirects,
     * flash messages, and validation error bags can be tested.
     *
     * The route requires the create_candidates permission, so each
     * test authenticates a user with that permission.
     */
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();

    $permission = Permission::query()
        ->where('name', 'create_candidates')
        ->firstOrFail();

    $user->permissions()->syncWithoutDetaching([
        $permission->id,
    ]);

    $this->actingAs($user);
});

it('adds a person to a position using the primary active workflow', function () {
    $position = Position::query()->create([
        'position_code' => 'TEST-POS-001',
        'status' => 'Open',
        'job_title' => 'Software Engineer',
    ]);

    $person = Person::query()->create([
        'person_code' => 'TEST-PER-001',
        'first_name' => 'Charles',
        'last_name' => 'Winchester',
        'email' => 'charles@example.test',
    ]);

    $workflow = Workflow::query()->create([
        'name' => 'Primary Candidate Workflow',
        'code' => 'primary-candidate-workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $response = $this->post(
        route('positions.candidates.store', $position->id),
        [
            'person_id' => $person->id,
        ],
    );

    $response
        ->assertRedirect(route('positions.edit', [
            'id' => $position->id,
            'section' => 'candidates',
        ]))
        ->assertSessionHas(
            'success',
            'Candidate added to the position successfully.',
        );

    $this->assertDatabaseHas('candidates', [
        'person_id' => $person->id,
        'position_id' => $position->id,
        'workflow_id' => $workflow->id,
        'status' => 'submitted',
    ]);

    expect(
        Candidate::query()->firstOrFail()->submitted_at,
    )->not->toBeNull();
});

it('does not add the same person to the same position twice', function () {
    $position = Position::query()->create([
        'position_code' => 'TEST-POS-002',
        'status' => 'Open',
        'job_title' => 'Project Manager',
    ]);

    $person = Person::query()->create([
        'person_code' => 'TEST-PER-002',
        'first_name' => 'Margaret',
        'last_name' => 'Houlihan',
        'email' => 'margaret@example.test',
    ]);

    $workflow = Workflow::query()->create([
        'name' => 'Candidate Workflow',
        'code' => 'candidate-workflow-duplicate-test',
        'is_active' => true,
        'is_primary' => true,
    ]);

    Candidate::query()->create([
        'person_id' => $person->id,
        'position_id' => $position->id,
        'workflow_id' => $workflow->id,
        'status' => 'submitted',
    ]);

    $response = $this
        ->from(route('positions.edit', $position->id))
        ->post(
            route('positions.candidates.store', $position->id),
            [
                'person_id' => $person->id,
            ],
        );

    $response
        ->assertRedirect(route('positions.edit', $position->id))
        ->assertSessionHasErrors('person_id');

    expect(Candidate::query()->count())->toBe(1);
});

it('uses a selected active workflow when one is supplied', function () {
    $position = Position::query()->create([
        'position_code' => 'TEST-POS-003',
        'status' => 'Open',
        'job_title' => 'Systems Analyst',
    ]);

    $person = Person::query()->create([
        'person_code' => 'TEST-PER-003',
        'first_name' => 'Benjamin',
        'last_name' => 'Pierce',
        'email' => 'benjamin@example.test',
    ]);

    Workflow::query()->create([
        'name' => 'Primary Workflow',
        'code' => 'primary-workflow-selection-test',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $selectedWorkflow = Workflow::query()->create([
        'name' => 'Specialist Workflow',
        'code' => 'specialist-workflow-selection-test',
        'is_active' => true,
        'is_primary' => false,
    ]);

    $this->post(
        route('positions.candidates.store', $position->id),
        [
            'person_id' => $person->id,
            'workflow_id' => $selectedWorkflow->id,
        ],
    )->assertRedirect(route('positions.edit', [
        'id' => $position->id,
        'section' => 'candidates',
    ]));

    $this->assertDatabaseHas('candidates', [
        'person_id' => $person->id,
        'position_id' => $position->id,
        'workflow_id' => $selectedWorkflow->id,
    ]);
});
