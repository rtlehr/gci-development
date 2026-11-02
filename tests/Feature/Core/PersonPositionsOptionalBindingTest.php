<?php

use App\Contracts\Workforce\PersonPositionRelationships;
use App\Models\Person;
use App\Services\Positions\LegacyPersonPositionRelationships;
use App\Services\Positions\LegacyPositionsBinding;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;

it('registers the legacy adapter by default while existing Positions classes are present', function () {
    $container = new Container();
    $container->instance('config', new Repository(['insite_modules' => ['legacy_positions_enabled' => true]]));

    LegacyPositionsBinding::register($container);

    expect($container->bound(PersonPositionRelationships::class))->toBeTrue()
        ->and($container->make(PersonPositionRelationships::class))->toBeInstanceOf(LegacyPersonPositionRelationships::class);
});

it('does not register Positions services when the transitional integration is disabled', function () {
    $container = new Container();
    $container->instance('config', new Repository(['insite_modules' => ['legacy_positions_enabled' => false]]));

    LegacyPositionsBinding::register($container);

    expect($container->bound(PersonPositionRelationships::class))->toBeFalse();
});

it('continues to support normal Core Person operations without the Positions contract', function () {
    $container = app();
    $original = $container->getBindings()[PersonPositionRelationships::class] ?? null;

    $container->offsetUnset(PersonPositionRelationships::class);

    try {
        $person = new Person(['first_name' => 'Core', 'last_name' => 'Only']);
        expect($person->first_name)->toBe('Core')
            ->and($person->last_name)->toBe('Only')
            ->and($person->phoneNumbers())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    } finally {
        if ($original !== null) {
            $container->bind(PersonPositionRelationships::class, $original['concrete'], $original['shared']);
        }
    }
});

it('fails clearly rather than manufacturing fake Position relationships when disabled', function () {
    $container = app();
    $original = $container->getBindings()[PersonPositionRelationships::class] ?? null;
    $container->offsetUnset(PersonPositionRelationships::class);

    try {
        $person = new Person();
        expect(fn () => $person->assignments())->toThrow(RuntimeException::class, 'Positions integration is unavailable');
        expect(fn () => $person->positions())->toThrow(RuntimeException::class, 'Positions integration is unavailable');
    } finally {
        if ($original !== null) {
            $container->bind(PersonPositionRelationships::class, $original['concrete'], $original['shared']);
        }
    }
});
