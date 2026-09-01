<?php

use App\Models\DataImport;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function dataImportUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $name) {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            [
                'group_name' => 'Test',
                'label' => $name,
                'description' => $name,
                'is_system' => false,
                'is_locked' => false,
            ],
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    return $user;
}

test('data import history requires data import access', function () {
    $user = dataImportUser(['view_admin']);

    $this->actingAs($user)
        ->get('/admin/data-imports')
        ->assertForbidden();
});

test('authorized user can view data import history', function () {
    $user = dataImportUser(['view_admin', 'access_data_import']);

    $this->actingAs($user)
        ->get('/admin/data-imports')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('Admin/DataImport/Index')
        );
});

test('creating an import requires manage permission', function () {
    $user = dataImportUser(['view_admin', 'access_data_import']);

    $this->actingAs($user)
        ->get('/admin/data-imports/create')
        ->assertForbidden();
});

test('authorized manager can open data import creation', function () {
    $user = dataImportUser([
        'view_admin',
        'access_data_import',
        'manage_data_import',
    ]);

    $this->actingAs($user)
        ->get('/admin/data-imports/create')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('Admin/DataImport/Create')
        );
});

test('data import history lists uploaded imports', function () {
    $user = dataImportUser(['view_admin', 'access_data_import']);

    DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'worksheet_selected',
        'original_filename' => 'Staffing Matrix.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Sheet1',
        'row_count' => 12,
        'column_count' => 24,
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/admin/data-imports')
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('Admin/DataImport/Index')
                ->where('imports.data.0.original_filename', 'Staffing Matrix.xlsx')
                ->where('imports.data.0.worksheet', 'Sheet1')
                ->where('imports.data.0.row_count', 12)
        );
});

test('mapping page exposes dynamic workflow and custom field destinations with safe suggestions', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $workflow = \App\Models\Workflow::query()->create([
        'name' => 'Import Workflow',
        'code' => 'import_workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    \App\Models\WorkflowStep::query()->create([
        'workflow_id' => $workflow->id,
        'code' => 'resume_accepted',
        'name' => 'Resume Accepted',
        'step_order' => 1,
        'is_active' => true,
        'allows_completed_at' => true,
    ]);

    \App\Models\CustomField::query()->create([
        'entity_type' => \App\Models\CustomField::ENTITY_POSITION,
        'name' => 'FTE',
        'key' => 'fte',
        'field_type' => \App\Models\CustomField::TYPE_TEXT,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'worksheet_selected',
        'original_filename' => 'Staffing Matrix.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Sheet1',
        'worksheet_index' => 0,
        'row_count' => 2,
        'column_count' => 4,
        'source_headers' => ['PID', 'Employee ID', 'Resume Accepted', 'FTE'],
        'workbook_metadata' => [
            'sheets' => [[
                'index' => 0,
                'name' => 'Sheet1',
                'row_count' => 2,
                'column_count' => 4,
                'headers' => ['PID', 'Employee ID', 'Resume Accepted', 'FTE'],
                'sample_rows' => [['ZN-001', '123456', '46143', '1']],
            ]],
        ],
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get("/admin/data-imports/{$import->id}?workflow_id={$workflow->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/DataImport/Show')
            ->where('selectedWorkflowId', $workflow->id)
            ->where('initialMappings.0', 'position.position_code')
            ->where('initialMappings.1', 'person.person_code')
            ->where('initialMappings.2', 'workflow.import_workflow.resume_accepted.completed_at')
            ->where('initialMappings.3', 'custom.position.fte')
        );
});

test('manager can save a complete column mapping without changing staffing data', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $workflow = \App\Models\Workflow::query()->create([
        'name' => 'Import Workflow',
        'code' => 'import_workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'worksheet_selected',
        'original_filename' => 'Staffing Matrix.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Sheet1',
        'worksheet_index' => 0,
        'row_count' => 2,
        'column_count' => 3,
        'source_headers' => ['PID', 'Employee ID', 'Last Updated'],
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->put("/admin/data-imports/{$import->id}/mapping", [
            'workflow_id' => $workflow->id,
            'mappings' => [
                ['source_index' => 0, 'source_header' => 'PID', 'destination_key' => 'position.position_code'],
                ['source_index' => 1, 'source_header' => 'Employee ID', 'destination_key' => 'person.person_code'],
                ['source_index' => 2, 'source_header' => 'Last Updated', 'destination_key' => 'ignore'],
            ],
        ])
        ->assertRedirect();

    $import->refresh();

    expect($import->status)->toBe('mapped')
        ->and($import->mapping_snapshot['workflow_code'])->toBe('import_workflow')
        ->and($import->mapping_snapshot['columns'])->toHaveCount(3)
        ->and(\App\Models\Position::query()->count())->toBe(0)
        ->and(\App\Models\Person::query()->count())->toBe(0)
        ->and(\App\Models\Candidate::query()->count())->toBe(0);
});

test('saved template flags a workflow destination that is no longer available', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $workflow = \App\Models\Workflow::query()->create([
        'name' => 'Import Workflow',
        'code' => 'import_workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $step = \App\Models\WorkflowStep::query()->create([
        'workflow_id' => $workflow->id,
        'code' => 'resume_accepted',
        'name' => 'Resume Accepted',
        'step_order' => 1,
        'is_active' => true,
        'allows_completed_at' => true,
    ]);

    $template = \App\Models\DataImportMappingTemplate::query()->create([
        'name' => 'Staffing Matrix',
        'mapping' => [
            'version' => 1,
            'workflow_code' => 'import_workflow',
            'columns' => [[
                'source_index' => 0,
                'source_header' => 'Resume Accepted',
                'destination_key' => 'workflow.import_workflow.resume_accepted.completed_at',
            ]],
        ],
        'source_headers' => ['Resume Accepted'],
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $step->update(['is_active' => false]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'worksheet_selected',
        'original_filename' => 'Staffing Matrix.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Sheet1',
        'worksheet_index' => 0,
        'row_count' => 2,
        'column_count' => 1,
        'source_headers' => ['Resume Accepted'],
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get("/admin/data-imports/{$import->id}?workflow_id={$workflow->id}&template_id={$template->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/DataImport/Show')
            ->where('mappingIssues.0.type', 'stale_destination')
        );
});

test('stage three validation matches existing records and does not change staffing data', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $jobTitle = \App\Models\JobTitle::query()->create([
        'name' => 'PM',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $existingPerson = \App\Models\Person::query()->create([
        'person_code' => '123456',
        'first_name' => 'Existing',
        'last_name' => 'Person',
    ]);

    $existingPosition = \App\Models\Position::query()->create([
        'position_code' => 'ZN-001',
        'status' => 'Open',
        'job_title_id' => $jobTitle->id,
        'job_title' => 'PM',
        'level' => 1,
    ]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'mapped',
        'original_filename' => 'Staffing Matrix.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Sheet1',
        'worksheet_index' => 0,
        'row_count' => 2,
        'column_count' => 5,
        'source_headers' => ['PID', 'Employee ID', 'First Name', 'Last Name', 'Job Title'],
        'mapping_snapshot' => [
            'version' => 1,
            'workflow_code' => null,
            'columns' => [
                ['source_index' => 0, 'source_header' => 'PID', 'destination_key' => 'position.position_code'],
                ['source_index' => 1, 'source_header' => 'Employee ID', 'destination_key' => 'person.person_code'],
                ['source_index' => 2, 'source_header' => 'First Name', 'destination_key' => 'person.first_name'],
                ['source_index' => 3, 'source_header' => 'Last Name', 'destination_key' => 'person.last_name'],
                ['source_index' => 4, 'source_header' => 'Job Title', 'destination_key' => 'position.job_title'],
            ],
        ],
        'uploaded_by' => $user->id,
    ]);

    $reader = \Mockery::mock(\App\Services\DataImport\XlsxWorkbookReader::class);
    $reader->shouldReceive('readWorksheet')->once()->andReturn([
        'name' => 'Sheet1',
        'headers' => $import->source_headers,
        'original_headers' => $import->source_headers,
        'rows' => [
            ['ZN-001', '123456', 'Existing', 'Person', 'PM'],
            ['ZN-002', '654321', 'New', 'Person', 'PM'],
        ],
    ]);
    app()->instance(\App\Services\DataImport\XlsxWorkbookReader::class, $reader);

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/validate")
        ->assertRedirect();

    $import->refresh();

    expect($import->validation_summary['total'])->toBe(2)
        ->and($import->validation_summary['review'])->toBe(1)
        ->and($import->validation_summary['ready'])->toBe(1)
        ->and($import->validation_summary['error'])->toBe(0)
        ->and($import->rows()->count())->toBe(2)
        ->and($import->rows()->where('status', 'review')->first()->person_id)->toBe($existingPerson->id)
        ->and($import->rows()->where('status', 'review')->first()->position_id)->toBe($existingPosition->id)
        ->and(\App\Models\Person::query()->count())->toBe(1)
        ->and(\App\Models\Position::query()->count())->toBe(1)
        ->and(\App\Models\Candidate::query()->count())->toBe(0);
});

test('stage three validation flags unknown lookup values and converts excel dates', function () {
    $normalizer = app(\App\Services\DataImport\ImportValueNormalizer::class);

    expect($normalizer->date('46143'))->toBe('2026-05-01')
        ->and($normalizer->date('5/1/2026'))->toBe('2026-05-01')
        ->and($normalizer->text(' N/A '))->toBeNull()
        ->and($normalizer->boolean('Yes'))->toBeTrue()
        ->and($normalizer->boolean('No'))->toBeFalse();
});

test('data import history exposes active workflows for template downloads', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $primary = \App\Models\Workflow::query()->create([
        'name' => 'Primary Import Workflow',
        'code' => 'primary_import_workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    \App\Models\Workflow::query()->create([
        'name' => 'Inactive Workflow',
        'code' => 'inactive_import_workflow',
        'is_active' => false,
        'is_primary' => false,
    ]);

    $this->actingAs($user)
        ->get('/admin/data-imports')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/DataImport/Index')
            ->where('primaryWorkflowId', $primary->id)
            ->has('workflows', 1)
            ->where('workflows.0.id', $primary->id)
            ->where('workflows.0.name', 'Primary Import Workflow')
        );
});

test('excel template download requires manage permission', function () {
    $user = dataImportUser(['view_admin', 'access_data_import']);

    $workflow = \App\Models\Workflow::query()->create([
        'name' => 'Template Workflow',
        'code' => 'template_workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $this->actingAs($user)
        ->get("/admin/data-imports/template/download?workflow_id={$workflow->id}")
        ->assertForbidden();
});

test('manager can download a workflow aware excel import template', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $workflow = \App\Models\Workflow::query()->create([
        'name' => 'Template Workflow',
        'code' => 'template_workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    \App\Models\WorkflowStep::query()->create([
        'workflow_id' => $workflow->id,
        'code' => 'resume_accepted',
        'name' => 'Resume Accepted',
        'step_order' => 1,
        'is_active' => true,
        'allows_completed_at' => true,
    ]);

    $response = $this->actingAs($user)
        ->get("/admin/data-imports/template/download?workflow_id={$workflow->id}");

    $response
        ->assertOk()
        ->assertDownload('Insight-Data-Import-Template-template-workflow.xlsx');

    expect($response->headers->get('content-type'))
        ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('template download rejects an inactive workflow', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $workflow = \App\Models\Workflow::query()->create([
        'name' => 'Inactive Template Workflow',
        'code' => 'inactive_template_workflow',
        'is_active' => false,
        'is_primary' => false,
    ]);

    $this->actingAs($user)
        ->from('/admin/data-imports')
        ->get("/admin/data-imports/template/download?workflow_id={$workflow->id}")
        ->assertRedirect('/admin/data-imports')
        ->assertSessionHasErrors('workflow_id');
});

test('part four review decisions can update or reuse matched records without changing staffing data', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $person = \App\Models\Person::query()->create([
        'person_code' => '123456',
        'first_name' => 'Current',
        'last_name' => 'Person',
    ]);

    $jobTitle = \App\Models\JobTitle::query()->create([
        'name' => 'PM',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $position = \App\Models\Position::query()->create([
        'position_code' => 'ZN-001',
        'status' => 'Open',
        'job_title_id' => $jobTitle->id,
        'job_title' => 'PM',
        'level' => 1,
    ]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'validated_with_issues',
        'original_filename' => 'Staffing Matrix.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Sheet1',
        'worksheet_index' => 0,
        'validation_summary' => ['total' => 1, 'ready' => 0, 'review' => 1, 'error' => 0, 'ignored' => 0],
        'uploaded_by' => $user->id,
    ]);

    $row = \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'source_identifier' => 'ZN-001',
        'status' => 'review',
        'action' => 'review_existing',
        'issues' => [
            ['code' => 'existing_person', 'severity' => 'review', 'message' => 'Existing Person matched by person code.'],
            ['code' => 'existing_position', 'severity' => 'review', 'message' => 'Existing Position matched by Position Code.'],
        ],
        'person_id' => $person->id,
        'position_id' => $position->id,
        'result' => ['mapped_values' => [
            'person.person_code' => '123456',
            'person.first_name' => 'Updated',
            'position.position_code' => 'ZN-001',
            'position.level' => 2,
        ]],
    ]);

    $this->actingAs($user)
        ->put("/admin/data-imports/{$import->id}/rows/{$row->id}/resolution", [
            'row_action' => 'continue',
            'person_action' => 'update',
            'position_action' => 'use_existing',
        ])
        ->assertRedirect();

    $row->refresh();
    $import->refresh();

    expect($row->status)->toBe('ready')
        ->and($row->action)->toBe('update_existing')
        ->and($row->result['resolutions']['person'])->toBe('update')
        ->and($row->result['resolutions']['position'])->toBe('use_existing')
        ->and($import->validation_summary['ready'])->toBe(1)
        ->and($import->validation_summary['review'])->toBe(0)
        ->and($person->fresh()->first_name)->toBe('Current')
        ->and($position->fresh()->level)->toBe(1);
});

test('part four review can mark a spreadsheet row to be skipped', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'validated_with_issues',
        'original_filename' => 'Staffing Matrix.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Sheet1',
        'worksheet_index' => 0,
        'validation_summary' => ['total' => 1, 'ready' => 0, 'review' => 1, 'error' => 0, 'ignored' => 0],
        'uploaded_by' => $user->id,
    ]);

    $row = \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'status' => 'review',
        'action' => 'review_existing',
        'issues' => [['code' => 'existing_person', 'severity' => 'review', 'message' => 'Existing Person matched.']],
        'result' => ['mapped_values' => ['person.person_code' => '123456']],
    ]);

    $this->actingAs($user)
        ->put("/admin/data-imports/{$import->id}/rows/{$row->id}/resolution", ['row_action' => 'skip'])
        ->assertRedirect();

    expect($row->fresh()->status)->toBe('ignored')
        ->and($row->fresh()->action)->toBe('skip')
        ->and($import->fresh()->validation_summary['ignored'])->toBe(1);
});

test('part four value mapping translates a spreadsheet value and reruns validation', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    \App\Models\JobTitle::query()->create([
        'name' => 'Program Manager',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'validated_with_issues',
        'original_filename' => 'Staffing Matrix.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Sheet1',
        'worksheet_index' => 0,
        'row_count' => 1,
        'column_count' => 3,
        'source_headers' => ['PID', 'Job Title', 'Level'],
        'mapping_snapshot' => [
            'version' => 1,
            'workflow_code' => null,
            'columns' => [
                ['source_index' => 0, 'source_header' => 'PID', 'destination_key' => 'position.position_code'],
                ['source_index' => 1, 'source_header' => 'Job Title', 'destination_key' => 'position.job_title'],
                ['source_index' => 2, 'source_header' => 'Level', 'destination_key' => 'position.level'],
            ],
        ],
        'uploaded_by' => $user->id,
    ]);

    $reader = \Mockery::mock(\App\Services\DataImport\XlsxWorkbookReader::class);
    $reader->shouldReceive('readWorksheet')->once()->andReturn([
        'name' => 'Sheet1',
        'headers' => $import->source_headers,
        'original_headers' => $import->source_headers,
        'rows' => [['ZN-900', 'PM', '1']],
    ]);
    app()->instance(\App\Services\DataImport\XlsxWorkbookReader::class, $reader);

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/value-translations", [
            'destination_key' => 'position.job_title',
            'source_value' => 'PM',
            'target_value' => 'Program Manager',
        ])
        ->assertRedirect();

    $import->refresh();
    $row = $import->rows()->first();

    expect($import->mapping_snapshot['value_translations']['position.job_title']['pm'])->toBe('Program Manager')
        ->and($row->status)->toBe('ready')
        ->and($row->result['mapped_values']['position.job_title'])->toBe('Program Manager');
});

test('part five executes a ready import and journals created records for rollback', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $jobTitle = \App\Models\JobTitle::query()->create([
        'name' => 'Project Manager',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'validated',
        'original_filename' => 'Insight Test.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Import Template',
        'worksheet_index' => 0,
        'mapping_snapshot' => [
            'version' => 1,
            'workflow_code' => null,
            'columns' => [
                ['source_index' => 0, 'source_header' => 'Position Code', 'destination_key' => 'position.position_code'],
                ['source_index' => 1, 'source_header' => 'Job Title', 'destination_key' => 'position.job_title'],
            ],
        ],
        'validation_summary' => ['total' => 1, 'ready' => 1, 'review' => 0, 'error' => 0, 'ignored' => 0],
        'uploaded_by' => $user->id,
    ]);

    \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'source_identifier' => 'TEST-POS-5001',
        'status' => 'ready',
        'action' => 'create',
        'issues' => [],
        'result' => ['mapped_values' => [
            'position.position_code' => 'TEST-POS-5001',
            'position.job_title' => 'Project Manager',
            'position.level' => 1,
            'position.staffing_state' => 'Vacant',
        ]],
    ]);

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/execute")
        ->assertRedirect();

    $position = \App\Models\Position::query()->where('position_code', 'TEST-POS-5001')->first();
    $import->refresh();
    $row = $import->rows()->first();
    $change = $import->changes()->first();

    expect($position)->not->toBeNull()
        ->and($position->job_title)->toBe($jobTitle->name)
        ->and($import->status)->toBe('completed')
        ->and($import->created_count)->toBeGreaterThanOrEqual(1)
        ->and($import->failed_count)->toBe(0)
        ->and($row->status)->toBe('imported')
        ->and($change)->not->toBeNull()
        ->and($change->action)->toBe('create')
        ->and($change->before_payload)->toBeNull()
        ->and(\Illuminate\Support\Facades\Crypt::decryptString($change->after_payload))->toContain('TEST-POS-5001');
});

test('part five refuses execution while review or validation errors remain', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'validated_with_issues',
        'original_filename' => 'Insight Test.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Import Template',
        'worksheet_index' => 0,
        'mapping_snapshot' => [
            'version' => 1,
            'workflow_code' => null,
            'columns' => [['source_index' => 0, 'source_header' => 'Position Code', 'destination_key' => 'position.position_code']],
        ],
        'validation_summary' => ['total' => 1, 'ready' => 0, 'review' => 1, 'error' => 0, 'ignored' => 0],
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->from("/admin/data-imports/{$import->id}")
        ->post("/admin/data-imports/{$import->id}/execute")
        ->assertRedirect("/admin/data-imports/{$import->id}")
        ->assertSessionHasErrors('execution');

    expect($import->fresh()->status)->toBe('validated_with_issues')
        ->and($import->changes()->count())->toBe(0);
});

test('part five execution reuses an explicitly reviewed existing person instead of attempting a duplicate encrypted person code insert', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $person = \App\Models\Person::query()->create([
        'person_code' => '123456',
        'first_name' => 'Current',
        'last_name' => 'Person',
    ]);

    $jobTitle = \App\Models\JobTitle::query()->create([
        'name' => 'Project Manager',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'validated',
        'original_filename' => 'Insight Test.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Import Template',
        'worksheet_index' => 0,
        'mapping_snapshot' => [
            'version' => 1,
            'workflow_code' => null,
            'columns' => [
                ['source_index' => 0, 'source_header' => 'Person Code', 'destination_key' => 'person.person_code'],
            ],
        ],
        'validation_summary' => ['total' => 1, 'ready' => 1, 'review' => 0, 'error' => 0, 'ignored' => 0],
        'uploaded_by' => $user->id,
    ]);

    \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'source_identifier' => 'TEST-POS-EXEC-PERSON',
        'status' => 'ready',
        'action' => 'update_existing',
        'issues' => [],
        // Deliberately omit person_id to reproduce the stale/missing row match that
        // previously fell through to a duplicate Person insert.
        'result' => [
            'mapped_values' => [
                'person.person_code' => '123456',
                'person.first_name' => 'Updated',
                'person.last_name' => 'Person',
                'position.position_code' => 'TEST-POS-EXEC-PERSON',
                'position.job_title' => $jobTitle->name,
                'position.level' => 1,
            ],
            'resolutions' => ['person' => 'update', 'row' => 'continue'],
        ],
    ]);

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/execute")
        ->assertRedirect();

    $import->refresh();

    expect(\App\Models\Person::query()->count())->toBe(1)
        ->and($person->fresh()->first_name)->toBe('Updated')
        ->and($import->failed_count)->toBe(0)
        ->and($import->status)->toBe('completed');
});

test('part five reports a clear import error when a new position assignment has no start date', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $person = \App\Models\Person::query()->create([
        'person_code' => 'ASSIGN-1001',
        'first_name' => 'Assignment',
        'last_name' => 'Tester',
    ]);

    $jobTitle = \App\Models\JobTitle::query()->create([
        'name' => 'Project Manager',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $position = \App\Models\Position::query()->create([
        'position_code' => 'ASSIGN-POS-1001',
        'status' => 'Open',
        'job_title_id' => $jobTitle->id,
        'job_title' => $jobTitle->name,
        'level' => 1,
    ]);

    $workflow = \App\Models\Workflow::query()->create([
        'name' => 'Assignment Test Workflow',
        'code' => 'assignment_test_workflow',
        'is_active' => true,
        'is_primary' => true,
    ]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'validated',
        'original_filename' => 'Insight Test.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'worksheet' => 'Import Template',
        'worksheet_index' => 0,
        'mapping_snapshot' => [
            'version' => 1,
            'workflow_code' => $workflow->code,
            'columns' => [
                ['source_index' => 0, 'source_header' => 'Person Code', 'destination_key' => 'person.person_code'],
            ],
        ],
        'validation_summary' => ['total' => 1, 'ready' => 1, 'review' => 0, 'error' => 0, 'ignored' => 0],
        'uploaded_by' => $user->id,
    ]);

    $row = \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'source_identifier' => $position->position_code,
        'status' => 'ready',
        'action' => 'use_existing',
        'issues' => [],
        'person_id' => $person->id,
        'position_id' => $position->id,
        'result' => [
            'mapped_values' => [
                'assignment.assignment_status' => 'active',
            ],
            'resolutions' => [
                'person' => 'use_existing',
                'position' => 'use_existing',
                'row' => 'continue',
            ],
        ],
    ]);

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/execute")
        ->assertRedirect();

    $row->refresh();
    $import->refresh();

    expect($row->status)->toBe('error')
        ->and($row->issues[0]['message'])->toContain('Position Assignment requires Start Date')
        ->and(\App\Models\PositionAssignment::query()->count())->toBe(0)
        ->and($import->failed_count)->toBe(1)
        ->and($import->status)->toBe('completed_with_errors');
});

test('part six rollback requires rollback permission', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'completed',
        'original_filename' => 'Insight Test.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'completed_at' => now(),
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/rollback")
        ->assertForbidden();
});

test('part six rollback deletes records created by the import in reverse journal order', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'rollback_data_import']);

    $jobTitle = \App\Models\JobTitle::query()->create([
        'name' => 'Project Manager',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'completed',
        'original_filename' => 'Insight Test.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'completed_at' => now(),
        'created_count' => 1,
        'uploaded_by' => $user->id,
    ]);

    $row = \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'source_identifier' => 'ROLLBACK-CREATE-1',
        'status' => 'imported',
        'action' => 'imported',
        'issues' => [],
        'result' => [],
    ]);

    $position = \App\Models\Position::query()->create([
        'position_code' => 'ROLLBACK-CREATE-1',
        'status' => 'Open',
        'job_title_id' => $jobTitle->id,
        'job_title' => $jobTitle->name,
        'level' => 1,
    ]);

    \App\Models\DataImportChange::query()->create([
        'data_import_id' => $import->id,
        'data_import_row_id' => $row->id,
        'sequence' => 1,
        'model_type' => \App\Models\Position::class,
        'model_id' => (string) $position->id,
        'action' => 'create',
        'before_payload' => null,
        'after_payload' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($position->fresh()->attributesToArray(), JSON_THROW_ON_ERROR)),
    ]);

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/rollback")
        ->assertRedirect();

    $import->refresh();

    expect(\App\Models\Position::query()->find($position->id))->toBeNull()
        ->and($import->status)->toBe('rolled_back')
        ->and($import->rolled_back_at)->not->toBeNull()
        ->and($import->rolled_back_by)->toBe($user->id)
        ->and($import->error_summary['rollback']['deleted'])->toBe(1)
        ->and($import->error_summary['rollback']['conflicts'])->toBe(0);
});

test('part six rollback restores the before snapshot for an imported update', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'rollback_data_import']);

    $jobTitle = \App\Models\JobTitle::query()->create([
        'name' => 'Project Manager',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $position = \App\Models\Position::query()->create([
        'position_code' => 'ROLLBACK-UPDATE-1',
        'status' => 'Open',
        'job_title_id' => $jobTitle->id,
        'job_title' => $jobTitle->name,
        'level' => 1,
        'team_name' => 'Before Team',
    ]);
    $before = $position->fresh()->attributesToArray();

    $position->update(['team_name' => 'Imported Team']);
    $after = $position->fresh()->attributesToArray();

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'completed',
        'original_filename' => 'Insight Test.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'completed_at' => now(),
        'updated_count' => 1,
        'uploaded_by' => $user->id,
    ]);

    $row = \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'source_identifier' => $position->position_code,
        'status' => 'imported',
        'action' => 'imported',
        'issues' => [],
        'result' => [],
        'position_id' => $position->id,
    ]);

    \App\Models\DataImportChange::query()->create([
        'data_import_id' => $import->id,
        'data_import_row_id' => $row->id,
        'sequence' => 1,
        'model_type' => \App\Models\Position::class,
        'model_id' => (string) $position->id,
        'action' => 'update',
        'before_payload' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($before, JSON_THROW_ON_ERROR)),
        'after_payload' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($after, JSON_THROW_ON_ERROR)),
    ]);

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/rollback")
        ->assertRedirect();

    $import->refresh();

    expect($position->fresh()->team_name)->toBe('Before Team')
        ->and($import->status)->toBe('rolled_back')
        ->and($import->error_summary['rollback']['restored'])->toBe(1)
        ->and($import->error_summary['rollback']['conflicts'])->toBe(0);
});

test('part six rollback preserves records edited after import and reports a conflict', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'rollback_data_import']);

    $jobTitle = \App\Models\JobTitle::query()->create([
        'name' => 'Project Manager',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $position = \App\Models\Position::query()->create([
        'position_code' => 'ROLLBACK-CONFLICT-1',
        'status' => 'Open',
        'job_title_id' => $jobTitle->id,
        'job_title' => $jobTitle->name,
        'level' => 1,
        'team_name' => 'Before Team',
    ]);
    $before = $position->fresh()->attributesToArray();

    $position->update(['team_name' => 'Imported Team']);
    $after = $position->fresh()->attributesToArray();

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'completed',
        'original_filename' => 'Insight Test.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'completed_at' => now(),
        'updated_count' => 1,
        'uploaded_by' => $user->id,
    ]);

    $row = \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'source_identifier' => $position->position_code,
        'status' => 'imported',
        'action' => 'imported',
        'issues' => [],
        'result' => [],
        'position_id' => $position->id,
    ]);

    \App\Models\DataImportChange::query()->create([
        'data_import_id' => $import->id,
        'data_import_row_id' => $row->id,
        'sequence' => 1,
        'model_type' => \App\Models\Position::class,
        'model_id' => (string) $position->id,
        'action' => 'update',
        'before_payload' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($before, JSON_THROW_ON_ERROR)),
        'after_payload' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($after, JSON_THROW_ON_ERROR)),
    ]);

    // Simulate a legitimate edit after the import completed.
    $position->update(['team_name' => 'Manual Newer Team']);

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/rollback")
        ->assertRedirect();

    $import->refresh();
    $row->refresh();

    expect($position->fresh()->team_name)->toBe('Manual Newer Team')
        ->and($import->status)->toBe('rolled_back_with_conflicts')
        ->and($import->error_summary['rollback']['conflicts'])->toBe(1)
        ->and($row->issues[0]['code'])->toBe('rollback_conflict')
        ->and($row->issues[0]['message'])->toContain('changed after the import');
});

test('part six rollback correctly reverses multiple imported updates to the same record', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'rollback_data_import']);

    $jobTitle = \App\Models\JobTitle::query()->create([
        'name' => 'Project Manager',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $position = \App\Models\Position::query()->create([
        'position_code' => 'ROLLBACK-MULTI-1',
        'status' => 'Open',
        'job_title_id' => $jobTitle->id,
        'job_title' => $jobTitle->name,
        'level' => 1,
        'team_name' => 'Original Team',
    ]);
    $snapshot0 = $position->fresh()->attributesToArray();

    $position->update(['team_name' => 'First Import Change']);
    $snapshot1 = $position->fresh()->attributesToArray();

    $position->update(['team_name' => 'Second Import Change']);
    $snapshot2 = $position->fresh()->attributesToArray();

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'completed',
        'original_filename' => 'Insight Test.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'completed_at' => now(),
        'updated_count' => 2,
        'uploaded_by' => $user->id,
    ]);

    $row = \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'source_identifier' => $position->position_code,
        'status' => 'imported',
        'action' => 'imported',
        'issues' => [],
        'result' => [],
        'position_id' => $position->id,
    ]);

    foreach ([[$snapshot0, $snapshot1, 1], [$snapshot1, $snapshot2, 2]] as [$before, $after, $sequence]) {
        \App\Models\DataImportChange::query()->create([
            'data_import_id' => $import->id,
            'data_import_row_id' => $row->id,
            'sequence' => $sequence,
            'model_type' => \App\Models\Position::class,
            'model_id' => (string) $position->id,
            'action' => 'update',
            'before_payload' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($before, JSON_THROW_ON_ERROR)),
            'after_payload' => \Illuminate\Support\Facades\Crypt::encryptString(json_encode($after, JSON_THROW_ON_ERROR)),
        ]);
    }

    $this->actingAs($user)
        ->post("/admin/data-imports/{$import->id}/rollback")
        ->assertRedirect();

    $import->refresh();

    expect($position->fresh()->team_name)->toBe('Original Team')
        ->and($import->status)->toBe('rolled_back')
        ->and($import->error_summary['rollback']['restored'])->toBe(2)
        ->and($import->error_summary['rollback']['conflicts'])->toBe(0);
});

test('part seven encrypts import workbook mapping and row review payloads at rest', function () {
    $user = dataImportUser(['view_admin', 'access_data_import', 'manage_data_import']);
    $secret = 'sensitive.person@example.test';

    $import = DataImport::query()->create([
        'uuid' => fake()->uuid(),
        'status' => 'mapped',
        'original_filename' => 'Sensitive Import.xlsx',
        'stored_path' => 'data-imports/example/source.xlsx',
        'workbook_metadata' => [
            'sheets' => [[
                'index' => 0,
                'name' => 'Sheet1',
                'sample_rows' => [[$secret]],
            ]],
        ],
        'mapping_snapshot' => [
            'columns' => [],
            'value_translations' => ['person.email' => [$secret => $secret]],
        ],
        'uploaded_by' => $user->id,
    ]);

    $row = \App\Models\DataImportRow::query()->create([
        'data_import_id' => $import->id,
        'source_row_number' => 2,
        'source_identifier' => $secret,
        'status' => 'review',
        'issues' => [[
            'code' => 'test_issue',
            'severity' => 'review',
            'message' => "Review {$secret}",
        ]],
        'result' => [
            'mapped_values' => ['person.email' => $secret],
        ],
    ]);

    $rawImport = \Illuminate\Support\Facades\DB::table('data_imports')->where('id', $import->id)->first();
    $rawRow = \Illuminate\Support\Facades\DB::table('data_import_rows')->where('id', $row->id)->first();

    expect($rawImport->workbook_metadata)->toBeNull()
        ->and($rawImport->mapping_snapshot)->toBeNull()
        ->and($rawImport->workbook_metadata_encrypted)->not->toContain($secret)
        ->and($rawImport->mapping_snapshot_encrypted)->not->toContain($secret)
        ->and($rawRow->source_identifier)->toBeNull()
        ->and($rawRow->source_identifier_encrypted)->not->toContain($secret)
        ->and($rawRow->issues)->toBeNull()
        ->and($rawRow->result)->toBeNull()
        ->and($rawRow->issues_encrypted)->not->toContain($secret)
        ->and($rawRow->result_encrypted)->not->toContain($secret)
        ->and($import->fresh()->workbook_metadata['sheets'][0]['sample_rows'][0][0])->toBe($secret)
        ->and($row->fresh()->source_identifier)->toBe($secret)
        ->and($row->fresh()->result['mapped_values']['person.email'])->toBe($secret);
});

test('part seven rejects workbook XML containing a doctype declaration', function () {
    if (! class_exists(\ZipArchive::class) || ! function_exists('simplexml_load_string')) {
        $this->markTestSkipped('ZIP and SimpleXML are required for XLSX reader tests.');
    }

    $path = tempnam(sys_get_temp_dir(), 'insight-unsafe-xlsx-');
    $zip = new \ZipArchive();
    expect($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE))->toBeTrue();

    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><!DOCTYPE workbook [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>Position Code</t></is></c></row></sheetData></worksheet>');
    $zip->close();

    try {
        $reader = app(\App\Services\DataImport\XlsxWorkbookReader::class);

        expect(fn () => $reader->inspect($path))
            ->toThrow(\RuntimeException::class, 'unsafe XML');
    } finally {
        @unlink($path);
    }
});

test('part seven rejects worksheets above the supported row limit', function () {
    if (! class_exists(\ZipArchive::class) || ! function_exists('simplexml_load_string')) {
        $this->markTestSkipped('ZIP and SimpleXML are required for XLSX reader tests.');
    }

    $path = tempnam(sys_get_temp_dir(), 'insight-large-xlsx-');
    $zip = new \ZipArchive();
    expect($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE))->toBeTrue();

    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');

    $rows = ['<row r="1"><c r="A1" t="inlineStr"><is><t>Position Code</t></is></c></row>'];
    for ($row = 2; $row <= 25002; $row++) {
        $rows[] = '<row r="'.$row.'"><c r="A'.$row.'" t="inlineStr"><is><t>TEST-'.$row.'</t></is></c></row>';
    }
    $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', $rows).'</sheetData></worksheet>');
    $zip->close();

    try {
        $reader = app(\App\Services\DataImport\XlsxWorkbookReader::class);

        expect(fn () => $reader->inspect($path))
            ->toThrow(\RuntimeException::class, 'maximum supported is 25000');
    } finally {
        @unlink($path);
    }
});
