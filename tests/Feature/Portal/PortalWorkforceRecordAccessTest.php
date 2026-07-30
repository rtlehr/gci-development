<?php

use App\Models\Candidate;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use App\Models\Workflow;
use App\Services\CurrentUserContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function workforceUser(array $permissions): array
{
    $user = User::factory()->create();
    $person = Person::factory()->forUser($user)->create();

    $permissionIds = collect($permissions)
        ->map(function (string $name): int {
            return Permission::query()->firstOrCreate(
                ['name' => $name],
                [
                    'group_name' => 'Test',
                    'label' => $name,
                    'description' => $name,
                    'is_system' => false,
                    'is_locked' => false,
                ],
            )->id;
        });

    $user->permissions()->sync($permissionIds);

    return [$user, $person];
}

function resetWorkforceContext(): void
{
    app(CurrentUserContext::class)->forget();
}

test('all-position permission exposes every position', function () {
    [$user] = workforceUser([
        'access_portal',
        'portal_view_positions',
        'portal_view_all_positions',
    ]);

    Position::factory()->count(3)->create();

    resetWorkforceContext();

    $this->actingAs($user)
        ->get('/portal/positions')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Positions/Index')
            ->has('positions.data', 3));
});

test('project manager sees assigned positions and cannot open another position', function () {
    [$projectManager] = workforceUser([
        'access_portal',
        'portal_view_positions',
        'portal_view_assigned_positions',
    ]);

    $assigned = Position::factory()->create([
        'project_manager_user_id' => $projectManager->id,
    ]);

    $other = Position::factory()->create();

    resetWorkforceContext();

    $this->actingAs($projectManager)
        ->get('/portal/positions')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('positions.data', 1)
            ->where('positions.data.0.id', $assigned->id));

    $this->get("/portal/positions/{$assigned->id}")->assertOk();
    $this->get("/portal/positions/{$other->id}")->assertForbidden();
    $this->get("/portal/positions/{$assigned->id}/edit")->assertForbidden();
});

test('candidate sees only linked positions and linked candidate progress', function () {
    [$user, $person] = workforceUser([
        'access_portal',
        'portal_view_dashboard',
        'portal_view_positions',
        'portal_view_candidate_positions',
        'portal_view_candidate_progress',
    ]);

    $workflow = Workflow::query()->create([
        'name' => 'Candidate Workflow',
        'code' => 'candidate-workflow',
        'description' => 'Test workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $linkedPosition = Position::factory()->create();
    $otherPosition = Position::factory()->create();

    $linkedCandidate = Candidate::query()->create([
        'person_id' => $person->id,
        'position_id' => $linkedPosition->id,
        'workflow_id' => $workflow->id,
        'status' => 'submitted',
    ]);

    $otherPerson = Person::factory()->create();

    $otherCandidate = Candidate::query()->create([
        'person_id' => $otherPerson->id,
        'position_id' => $otherPosition->id,
        'workflow_id' => $workflow->id,
        'status' => 'submitted',
    ]);

    resetWorkforceContext();

    $this->actingAs($user)
        ->get('/portal/positions')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('positions.data', 1)
            ->where('positions.data.0.id', $linkedPosition->id));

    $this->get("/portal/positions/{$linkedPosition->id}")->assertOk();
    $this->get("/portal/positions/{$otherPosition->id}")->assertForbidden();
    $this->get("/portal/candidates/{$linkedCandidate->id}")->assertOk();
    $this->get("/portal/candidates/{$otherCandidate->id}")->assertForbidden();
});

test('candidate dashboard receives only personal opportunities', function () {
    [$user, $person] = workforceUser([
        'access_portal',
        'portal_view_dashboard',
        'portal_view_positions',
        'portal_view_candidate_positions',
        'portal_view_candidate_progress',
    ]);

    $workflow = Workflow::query()->create([
        'name' => 'Candidate Workflow',
        'code' => 'candidate-dashboard-workflow',
        'description' => 'Test workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $position = Position::factory()->create();

    Candidate::query()->create([
        'person_id' => $person->id,
        'position_id' => $position->id,
        'workflow_id' => $workflow->id,
        'status' => 'submitted',
    ]);

    Candidate::query()->create([
        'person_id' => Person::factory()->create()->id,
        'position_id' => Position::factory()->create()->id,
        'workflow_id' => $workflow->id,
        'status' => 'submitted',
    ]);

    resetWorkforceContext();

    $this->actingAs($user)
        ->get('/portal/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Dashboard')
            ->where('showPmoPositions', false)
            ->where('showProjectManagerPositions', false)
            ->where('showCandidateOpportunities', true)
            ->has('candidateOpportunities', 1)
            ->where('summary.assignedPositions', 1)
            ->where('summary.positionsLabel', 'opportunities'));
});

test('all-position permission wins when a user has multiple workforce roles', function () {
    [$user] = workforceUser([
        'access_portal',
        'portal_view_dashboard',
        'portal_view_positions',
        'portal_view_all_positions',
        'portal_view_assigned_positions',
        'portal_view_candidate_positions',
    ]);

    Position::factory()->count(2)->create();

    resetWorkforceContext();

    $this->actingAs($user)
        ->get('/portal/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('showPmoPositions', true)
            ->where('showProjectManagerPositions', false)
            ->where('showCandidateOpportunities', false)
            ->has('pmoPositions', 2));
});
