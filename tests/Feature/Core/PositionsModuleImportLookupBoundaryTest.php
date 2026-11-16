<?php

use App\Contracts\Positions\PositionImportLookup;
use App\Core\Modules\ModuleActivator;
use App\Models\Person;
use App\Models\Position;
use App\Services\DataImport\ImportRecordMatcher;
use App\Services\Positions\LegacyPositionImportLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insite\Modules\Positions\Services\ModulePositionImportLookup;

uses(RefreshDatabase::class);

it('retains import matching through the legacy contract for unselected installations', function () {
    expect(app(PositionImportLookup::class))->toBeInstanceOf(LegacyPositionImportLookup::class);

    $position = Position::factory()->create(['position_code' => 'IM-500']);
    $matcher = app(ImportRecordMatcher::class);

    expect($matcher->position(' im-500 ')?->is($position))->toBeTrue()
        ->and($matcher->position(null))->toBeNull()
        ->and($matcher->candidate(null, $position))->toBeNull();
});

it('selects the module-owned import lookup without inheriting the legacy adapter', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', true);
    app(ModuleActivator::class)->activate();

    expect(app(PositionImportLookup::class))->toBeInstanceOf(ModulePositionImportLookup::class)
        ->and(app(PositionImportLookup::class))->not->toBeInstanceOf(LegacyPositionImportLookup::class);

    $position = Position::factory()->create(['position_code' => 'IM-501']);
    $person = Person::factory()->create();
    $matcher = app(ImportRecordMatcher::class);

    expect($matcher->position('im-501')?->is($position))->toBeTrue()
        ->and($matcher->candidate($person, $position))->toBeNull();
});

it('does not register import matching when the legacy positions boundary is disabled', function () {
    config()->set('insite_modules.enabled', ['positions']);
    config()->set('insite_modules.legacy_positions_enabled', false);
    app()->offsetUnset(PositionImportLookup::class);
    app(ModuleActivator::class)->activate();

    expect(app()->bound(PositionImportLookup::class))->toBeFalse();
});
