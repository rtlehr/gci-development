<?php

use App\Support\ListDefinitions\PositionsDefinition as HostPositionsDefinition;
use Insite\Modules\Positions\Http\Controllers\Portal\PositionController as ModulePortalPositionController;
use Insite\Modules\Positions\Http\Controllers\PositionsController as ModuleLegacyPositionsController;
use Insite\Modules\Positions\Support\ListDefinitions\PositionsDefinition as ModulePositionsDefinition;

it('preserves the original Positions list definition for host fallback', function () {
    $host = HostPositionsDefinition::get();
    $module = ModulePositionsDefinition::get();

    expect($module)->toEqual($host)
        ->and($module['list_key'])->toBe('positions')
        ->and($module['entity_model'])->toBe(\App\Models\Position::class)
        ->and($module['entity_table'])->toBe('positions')
        ->and($module['default_sort'])->toBe('created_at')
        ->and($module['default_direction'])->toBe('desc');
});

it('preserves column keys and export search and visibility metadata', function () {
    $columns = ModulePositionsDefinition::get()['columns'];

    expect(array_column($columns, 'key'))->toBe([
        'position_code', 'job_title', 'level', 'team_name', 'location', 'building',
        'created_at', 'close_date', 'id', 'labor_category', 'status', 'component',
        'is_essential', 'travel_required', 'high_risk_role', 'scheduled_to_close',
    ]);

    foreach ($columns as $column) {
        expect($column)->toHaveKeys([
            'key', 'label', 'db_field', 'sortable', 'searchable', 'hideable',
            'exportable', 'default_visible', 'default_order',
        ]);
        expect($column['exportable'])->toBeTrue();
    }
});

it('uses the module-owned list definition in both extracted controllers', function () {
    foreach ([ModuleLegacyPositionsController::class, ModulePortalPositionController::class] as $controller) {
        $file = (new ReflectionClass($controller))->getFileName();
        $source = file_get_contents($file);

        expect($source)->toContain(
            'use Insite\\Modules\\Positions\\Support\\ListDefinitions\\PositionsDefinition;'
        )->not->toContain('use App\\Support\\ListDefinitions\\PositionsDefinition;');
    }

    expect(ModulePositionsDefinition::class)->not->toBe(HostPositionsDefinition::class);
});
