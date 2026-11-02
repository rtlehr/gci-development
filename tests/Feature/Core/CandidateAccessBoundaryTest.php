<?php

use App\Models\Candidate;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use App\Models\Workflow;
use App\Services\CurrentUserContext;
use App\Services\PortalWorkforceAccessService;
use App\Services\Recruitment\CandidateAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function boundaryCandidateUser(array $grants): array
{
    $user = User::factory()->create();
    $person = Person::factory()->forUser($user)->create();
    $ids = collect($grants)->map(fn ($permission) => Permission::query()->firstOrCreate(
        ['name' => $permission],
        ['group_name' => 'Test', 'label' => $permission, 'description' => $permission, 'is_system' => false, 'is_locked' => false],
    )->id);
    $user->permissions()->sync($ids);
    app(CurrentUserContext::class)->forget();

    return [$user, $person];
}

function boundaryCandidates(): array
{
    $workflow = Workflow::query()->create([
        'name' => 'Boundary Workflow', 'code' => 'boundary-workflow',
        'is_active' => true, 'is_primary' => true,
    ]);

    return [$workflow, Position::factory()->create(), Position::factory()->create()];
}

test('candidate access service is independently resolvable', function () {
    expect(app(CandidateAccessService::class))->toBeInstanceOf(CandidateAccessService::class);
});

test('recruitment candidate scope preserves all-position access', function () {
    [$user] = boundaryCandidateUser(['portal_view_all_positions']);
    [$workflow, $first, $second] = boundaryCandidates();
    Candidate::query()->create(['person_id' => Person::factory()->create()->id, 'position_id' => $first->id, 'workflow_id' => $workflow->id, 'status' => 'submitted']);
    Candidate::query()->create(['person_id' => Person::factory()->create()->id, 'position_id' => $second->id, 'workflow_id' => $workflow->id, 'status' => 'submitted']);
    $this->actingAs($user);
    app(CurrentUserContext::class)->forget();

    expect(app(CandidateAccessService::class)->scopeCandidates(Candidate::query())->count())->toBe(2);
});

test('recruitment candidate scope preserves project-manager filtering and legacy wrapper', function () {
    [$user] = boundaryCandidateUser(['portal_view_assigned_positions']);
    [$workflow, $first, $second] = boundaryCandidates();
    $first->update(['project_manager_user_id' => $user->id]);
    $mine = Candidate::query()->create(['person_id' => Person::factory()->create()->id, 'position_id' => $first->id, 'workflow_id' => $workflow->id, 'status' => 'submitted']);
    Candidate::query()->create(['person_id' => Person::factory()->create()->id, 'position_id' => $second->id, 'workflow_id' => $workflow->id, 'status' => 'submitted']);
    $this->actingAs($user);
    app(CurrentUserContext::class)->forget();

    expect(app(CandidateAccessService::class)->scopeCandidates(Candidate::query())->pluck('id')->all())->toBe([$mine->id]);
    expect(app(PortalWorkforceAccessService::class)->scopeCandidates(Candidate::query())->pluck('id')->all())->toBe([$mine->id]);
});

test('recruitment candidate scope preserves person filtering', function () {
    [$user, $person] = boundaryCandidateUser(['portal_view_candidate_positions']);
    [$workflow, $first, $second] = boundaryCandidates();
    $mine = Candidate::query()->create(['person_id' => $person->id, 'position_id' => $first->id, 'workflow_id' => $workflow->id, 'status' => 'submitted']);
    Candidate::query()->create(['person_id' => Person::factory()->create()->id, 'position_id' => $second->id, 'workflow_id' => $workflow->id, 'status' => 'submitted']);
    $this->actingAs($user);
    app(CurrentUserContext::class)->forget();

    expect(app(CandidateAccessService::class)->scopeCandidates(Candidate::query())->pluck('id')->all())->toBe([$mine->id]);
});
