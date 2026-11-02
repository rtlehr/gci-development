<?php

use App\Contracts\Workforce\PersonPositionRelationships;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Services\Positions\LegacyPersonPositionRelationships;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('resolves the temporary Positions relationship adapter through the Core contract', function () {
    expect(app(PersonPositionRelationships::class))
        ->toBeInstanceOf(LegacyPersonPositionRelationships::class);
});

it('preserves the Person assignments relationship contract', function () {
    $person = new Person();
    $relation = $person->assignments();

    expect($relation)->toBeInstanceOf(HasMany::class)
        ->and($relation->getRelated())->toBeInstanceOf(PositionAssignment::class)
        ->and($relation->getForeignKeyName())->toBe('person_id');
});

it('preserves the Person positions pivot and timestamps contract', function () {
    $person = new Person();
    $relation = $person->positions();

    expect($relation)->toBeInstanceOf(BelongsToMany::class)
        ->and($relation->getRelated())->toBeInstanceOf(Position::class)
        ->and($relation->getTable())->toBe('position_assignments')
        ->and($relation->getPivotColumns())->toBe(['start_date', 'end_date', 'assignment_status', 'assignment_type', 'notes', 'created_at', 'updated_at']);
});
