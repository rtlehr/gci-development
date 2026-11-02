<?php

use App\Contracts\Workforce\PersonPositionRelationships;
use App\Core\Modules\ModuleActivator;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Services\Positions\LegacyPersonPositionRelationships;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Insite\Modules\Positions\Services\ModulePersonPositionRelationships;

it('keeps the unselected legacy installation on its host adapter', function () {
    expect(config('insite_modules.enabled', []))->not->toContain('positions')
        ->and(app(PersonPositionRelationships::class))->toBeInstanceOf(LegacyPersonPositionRelationships::class);
});

it('registers the extracted relationship adapter when positions is selected', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app()->offsetUnset(PersonPositionRelationships::class);

    expect(app(ModuleActivator::class)->activate())->toBe(['positions'])
        ->and(app(PersonPositionRelationships::class))->toBeInstanceOf(ModulePersonPositionRelationships::class);
});

it('preserves the person relationship shapes and position pivot in the extracted adapter', function () {
    $adapter = new ModulePersonPositionRelationships();
    $person = new Person();
    $assignments = $adapter->assignments($person);
    $positions = $adapter->positions($person);

    expect($assignments)->toBeInstanceOf(HasMany::class)
        ->and($assignments->getRelated())->toBeInstanceOf(PositionAssignment::class)
        ->and($assignments->getForeignKeyName())->toBe('person_id')
        ->and($positions)->toBeInstanceOf(BelongsToMany::class)
        ->and($positions->getRelated())->toBeInstanceOf(Position::class)
        ->and($positions->getTable())->toBe('position_assignments')
        ->and($positions->getPivotColumns())->toBe([
            'start_date', 'end_date', 'assignment_status', 'assignment_type', 'notes', 'created_at', 'updated_at',
        ]);
});
