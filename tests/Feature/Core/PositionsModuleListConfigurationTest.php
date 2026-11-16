<?php

use App\Models\Position;
use App\Services\CustomFieldListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Insite\Modules\Positions\Http\Controllers\PositionsController as LegacyController;
use Insite\Modules\Positions\Http\Controllers\Portal\PositionController as PortalController;
use Insite\Modules\Positions\Services\PositionListConfiguration;
use Insite\Modules\Positions\Services\PositionListPreferences;
use Insite\Modules\Positions\Support\ListDefinitions\PositionsDefinition;

uses(RefreshDatabase::class);

it('uses the same augmented Positions definition for lists, exports and preferences', function () {
    $configuration = app(PositionListConfiguration::class);
    $expected = app(CustomFieldListService::class)->augmentDefinition(PositionsDefinition::get(), 'position');

    expect($configuration->definition())->toEqual($expected)
        ->and($configuration->definition()['list_key'])->toBe('positions');

    $preferences = file_get_contents((new ReflectionClass(PositionListPreferences::class))->getFileName());
    expect($preferences)->toContain('$this->configuration->definition()')
        ->not->toContain('augmentDefinition(');
});

it('applies exactly the requested status filter without changing unfiltered queries', function () {
    Position::factory()->create(['status' => 'Open']);
    Position::factory()->create(['status' => 'Closed']);
    $configuration = app(PositionListConfiguration::class);

    $filtered = Position::query();
    $configuration->applyStatusFilter($filtered, Request::create('/positions', 'GET', ['status' => 'Open']));
    expect($filtered->count())->toBe(1)
        ->and($filtered->first()->status)->toBe('Open');

    $unfiltered = Position::query();
    $configuration->applyStatusFilter($unfiltered, Request::create('/positions', 'GET', ['status' => '']));
    expect($unfiltered->count())->toBe(2);
});

it('routes both module controller lists and exports through the shared definition and filter', function () {
    foreach ([LegacyController::class, PortalController::class] as $controller) {
        $source = file_get_contents((new ReflectionClass($controller))->getFileName());
        expect(substr_count($source, 'app(PositionListConfiguration::class)->definition()'))->toBe(2)
            ->and(substr_count($source, "filterCallback: [app(PositionListConfiguration::class), 'applyStatusFilter']"))->toBe(2)
            ->and($source)->not->toContain('$query->where(\'status\', $request->input(\'status\'))');
    }
});
