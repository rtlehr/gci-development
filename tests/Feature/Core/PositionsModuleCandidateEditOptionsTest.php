<?php

use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insite\Modules\Positions\Http\Controllers\PositionsController as LegacyController;
use Insite\Modules\Positions\Http\Controllers\Portal\PositionController as PortalController;
use Insite\Modules\Positions\Services\PositionCandidateEditOptions;

uses(RefreshDatabase::class);

it('returns available candidate people with the existing edit-page output shape', function () {
    $position = Position::factory()->create();
    $person = Person::factory()->create(['first_name' => 'Samantha', 'preferred_name' => 'Sam', 'last_name' => 'Example']);

    $options = app(PositionCandidateEditOptions::class)->candidateOptions($position);
    $selected = $options->firstWhere('id', $person->id);

    expect($selected)->not->toBeNull()
        ->and($selected['full_name'])->toBe('Sam Example')
        ->and($selected['email'])->toBe($person->email)
        ->and($selected)->toHaveKeys(['person_code', 'primary_phone', 'primary_phone_extension']);
});

it('excludes people already linked as candidates to this position', function () {
    $position = Position::factory()->create();
    $included = Person::factory()->create();
    $excluded = Person::factory()->create();
    $position->setRelation('candidates', collect([new Candidate(['person_id' => $excluded->id])]));

    $ids = app(PositionCandidateEditOptions::class)->candidateOptions($position)->pluck('id')->all();

    expect($ids)->toContain($included->id)
        ->not->toContain($excluded->id);
});

it('lists active workflows primary first and wires both module edit pages to the same service', function () {
    Workflow::query()->create(['name' => 'Inactive', 'code' => 'inactive-candidate-5c16', 'is_active' => false, 'is_primary' => true]);
    Workflow::query()->create(['name' => 'Secondary', 'code' => 'secondary-candidate-5c16', 'is_active' => true, 'is_primary' => false]);
    Workflow::query()->create(['name' => 'Primary', 'code' => 'primary-candidate-5c16', 'is_active' => true, 'is_primary' => true]);

    $workflows = app(PositionCandidateEditOptions::class)->workflows();
    expect($workflows->pluck('name')->all())->toBe(['Primary', 'Secondary'])
        ->and($workflows->first()['is_primary'])->toBeTrue();

    foreach ([LegacyController::class, PortalController::class] as $controller) {
        $source = file_get_contents((new ReflectionClass($controller))->getFileName());
        expect($source)->toContain('$candidateEditOptions->candidateOptions($position)')
            ->toContain('$candidateEditOptions->workflows()')
            ->not->toContain('whereNotIn(\'id\', $candidatePersonIds)');
    }
});
