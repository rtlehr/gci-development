<?php

use App\Models\JobTitle;
use App\Services\CustomFieldService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Insite\Modules\Positions\Http\Controllers\PositionsController as ModuleLegacyController;
use Insite\Modules\Positions\Http\Controllers\Portal\PositionController as ModulePortalController;
use Insite\Modules\Positions\Services\PositionInputValidator;

uses(RefreshDatabase::class);

it('shares module validation between both controllers with Portal custom fields enabled', function () {
    foreach ([ModuleLegacyController::class => 'false', ModulePortalController::class => 'true'] as $class => $customFields) {
        $file = (new ReflectionClass($class))->getFileName();
        $source = file_get_contents($file);
        expect($source)->toContain('use Insite\\Modules\\Positions\\Services\\PositionInputValidator;')
            ->and($source)->toContain('app(PositionInputValidator::class)->validate($request, $position, '.$customFields.')')
            ->and($source)->not->toContain("Rule::unique('positions'");
    }
});

it('enforces the existing required fields without applying Portal-only custom field rules', function () {
    $fields = \Mockery::mock(CustomFieldService::class);
    $fields->shouldNotReceive('rules');
    app()->instance(CustomFieldService::class, $fields);

    try {
        app(PositionInputValidator::class)->validate(Request::create('/positions', 'POST', [
            'position_code' => 'VALIDATION-LEGACY-001',
            'status' => 'Not a real status',
        ]));
        test()->fail('Expected validation exception for invalid status and missing job title.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKeys(['status', 'job_title_id']);
    }
});

it('adds custom field rules to Portal validation but not legacy validation', function () {
    $jobTitle = JobTitle::query()->create([
        'name' => 'Module Validation Role',
        'slug' => 'module-validation-role',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $fields = \Mockery::mock(CustomFieldService::class);
    $fields->shouldReceive('rules')->once()->with('position')->andReturn([
        'custom_fields.example' => ['required'],
    ]);
    app()->instance(CustomFieldService::class, $fields);

    $data = [
        'position_code' => 'VALIDATION-MODULE-001',
        'status' => 'Open',
        'job_title_id' => $jobTitle->id,
    ];
    $result = app(PositionInputValidator::class)->validate(Request::create('/positions', 'POST', $data));
    expect($result)->toBe($data);

    try {
        app(PositionInputValidator::class)->validate(Request::create('/portal/positions', 'POST', $data), null, true);
        test()->fail('Expected Portal custom-field validation exception.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('custom_fields.example');
    }
});
