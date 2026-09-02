<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadDataImportRequest;
use App\Models\DataImport;
use App\Models\DataImportMappingTemplate;
use App\Models\DataImportRow;
use App\Models\Workflow;
use App\Services\DataImport\ImportConflictResolutionService;
use App\Services\DataImport\ImportExecutionService;
use App\Services\DataImport\ImportMappingRegistry;
use App\Services\DataImport\ImportRollbackService;
use App\Services\DataImport\ImportMappingSuggester;
use App\Services\DataImport\ImportMappingTemplateService;
use App\Services\DataImport\ImportTemplateWorkbookService;
use App\Services\DataImport\ImportValidationService;
use App\Services\DataImport\ImportValueTranslationService;
use App\Services\DataImport\XlsxWorkbookReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataImportController extends Controller
{
    public function index(): Response
    {
        $imports = DataImport::query()->with('uploader:id,name')->latest()->paginate(20)->through(fn (DataImport $import) => [
            'id' => $import->id,
            'uuid' => $import->uuid,
            'status' => $import->status,
            'original_filename' => $import->original_filename,
            'worksheet' => $import->worksheet,
            'row_count' => $import->row_count,
            'created_count' => $import->created_count,
            'updated_count' => $import->updated_count,
            'skipped_count' => $import->skipped_count,
            'failed_count' => $import->failed_count,
            'created_at' => $import->created_at?->toISOString(),
            'uploaded_by' => $import->uploader?->name,
        ]);

        $workflows = Workflow::query()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_primary']);

        return Inertia::render('Admin/DataImport/Index', [
            'imports' => $imports,
            'workflows' => $workflows->map(fn (Workflow $workflow) => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'code' => $workflow->code,
                'is_primary' => (bool) $workflow->is_primary,
            ])->values(),
            'primaryWorkflowId' => $workflows->firstWhere('is_primary', true)?->id ?? $workflows->first()?->id,
        ]);
    }

    public function downloadTemplate(Request $request, ImportTemplateWorkbookService $templateService): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'workflow_id' => ['required', 'integer'],
        ]);

        $workflow = Workflow::query()
            ->whereKey((int) $validated['workflow_id'])
            ->where('is_active', true)
            ->first();

        if (! $workflow) {
            return back()->withErrors([
                'workflow_id' => 'Select an active Candidate Workflow before downloading the template.',
            ]);
        }

        try {
            $path = $templateService->build($workflow);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['template' => $exception->getMessage()]);
        }

        $safeWorkflow = Str::slug($workflow->name) ?: 'candidate-workflow';
        $filename = 'Insight-Data-Import-Template-'.$safeWorkflow.'.xlsx';

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/DataImport/Create');
    }

    public function store(UploadDataImportRequest $request, XlsxWorkbookReader $reader): RedirectResponse
    {
        $file = $request->file('file');
        $uuid = (string) Str::uuid();
        $storedPath = $file->storeAs("data-imports/{$uuid}", 'source.xlsx', 'local');

        try {
            $metadata = $reader->inspect(Storage::disk('local')->path($storedPath));
        } catch (RuntimeException $exception) {
            Storage::disk('local')->deleteDirectory("data-imports/{$uuid}");

            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        if (empty($metadata['sheets'])) {
            Storage::disk('local')->deleteDirectory("data-imports/{$uuid}");

            return back()->withErrors(['file' => 'The workbook does not contain a readable worksheet.']);
        }

        $import = DataImport::query()->create([
            'uuid' => $uuid,
            'status' => 'uploaded',
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'workbook_metadata' => $metadata,
            'uploaded_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.data-imports.show', $import);
    }

    public function show(
        Request $request,
        DataImport $dataImport,
        ImportMappingRegistry $registry,
        ImportMappingSuggester $suggester,
        ImportMappingTemplateService $templateService,
        ImportConflictResolutionService $conflicts,
        ImportValueTranslationService $translations,
    ): Response {
        $importLocked = $this->isImportLocked($dataImport);

        $templates = DataImportMappingTemplate::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'mapping', 'source_headers', 'updated_at']);

        $requestedTemplate = null;
        if (! $importLocked && $request->filled('template_id')) {
            $requestedTemplate = $templates->firstWhere('id', (int) $request->integer('template_id'));
        }

        $workflows = Workflow::query()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_primary']);

        $selectedWorkflow = $this->resolveWorkflow(
            $request,
            $workflows,
            $requestedTemplate,
            $dataImport,
            $templateService,
        );

        $destinationGroups = $registry->groups($selectedWorkflow);
        $destinationLookup = collect($destinationGroups)
            ->flatMap(fn (array $group) => $group['items'])
            ->keyBy('key')
            ->all();

        $headers = $dataImport->source_headers ?? [];
        $mappingIssues = [];
        $mappings = [];

        if ($headers !== []) {
            if ($requestedTemplate) {
                $applied = $templateService->apply($requestedTemplate, $headers, $destinationLookup, $selectedWorkflow);
                $mappings = $applied['mappings'];
                $mappingIssues = $applied['issues'];
            } elseif ($dataImport->mapping_snapshot) {
                $applied = $templateService->applyPayload($dataImport->mapping_snapshot, $headers, $destinationLookup, $selectedWorkflow);
                $mappings = $applied['mappings'];
                $mappingIssues = $applied['issues'];
            } else {
                $mappings = $suggester->suggest($headers, $destinationGroups);
            }
        }

        return Inertia::render('Admin/DataImport/Show', [
            'import' => [
                'id' => $dataImport->id,
                'uuid' => $dataImport->uuid,
                'status' => $dataImport->status,
                'original_filename' => $dataImport->original_filename,
                'worksheet' => $dataImport->worksheet,
                'worksheet_index' => $dataImport->worksheet_index,
                'source_headers' => $dataImport->source_headers,
                'workbook_metadata' => $dataImport->workbook_metadata,
                'mapping_template_id' => $dataImport->mapping_template_id,
                'created_at' => $dataImport->created_at?->toISOString(),
                'started_at' => $dataImport->started_at?->toISOString(),
                'completed_at' => $dataImport->completed_at?->toISOString(),
                'created_count' => $dataImport->created_count,
                'updated_count' => $dataImport->updated_count,
                'skipped_count' => $dataImport->skipped_count,
                'failed_count' => $dataImport->failed_count,
                'rolled_back_at' => $dataImport->rolled_back_at?->toISOString(),
                'rolled_back_by' => $dataImport->rolled_back_by,
                'change_count' => $dataImport->changes()->count(),
                'rollback_summary' => $dataImport->error_summary['rollback'] ?? null,
                'is_locked' => $importLocked,
            ],
            'workflows' => $workflows->map(fn (Workflow $workflow) => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'code' => $workflow->code,
                'is_primary' => (bool) $workflow->is_primary,
            ])->values(),
            'selectedWorkflowId' => $selectedWorkflow?->id,
            'destinationGroups' => $destinationGroups,
            'initialMappings' => (object) $mappings,
            'mappingIssues' => $mappingIssues,
            'templates' => $templates->map(fn (DataImportMappingTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'workflow_code' => $templateService->workflowCode($template),
                'updated_at' => $template->updated_at?->toISOString(),
            ])->values(),
            'selectedTemplateId' => $requestedTemplate?->id,
            'validationSummary' => $dataImport->validation_summary,
            'validationRows' => $dataImport->rows()
                ->with(['person', 'position', 'candidate'])
                ->orderBy('source_row_number')->get()->map(fn (DataImportRow $row) => [
                    'id' => $row->id,
                    'source_row_number' => $row->source_row_number,
                    'source_identifier' => $row->source_identifier,
                    'status' => $row->status,
                    'action' => $row->action,
                    'issues' => $row->issues ?? [],
                    'person_id' => $row->person_id,
                    'position_id' => $row->position_id,
                    'candidate_id' => $row->candidate_id,
                    'result' => $row->result,
                    'review' => $conflicts->review($row),
                ])->values(),
            'translationOptions' => $translations->options($dataImport, $registry),
            'valueTranslations' => $dataImport->mapping_snapshot['value_translations'] ?? [],
        ]);
    }

    public function selectWorksheet(Request $request, DataImport $dataImport): RedirectResponse
    {
        $this->ensureImportEditable($dataImport);

        $validated = $request->validate(['worksheet_index' => ['required', 'integer', 'min:0']]);
        $sheets = $dataImport->workbook_metadata['sheets'] ?? [];
        $sheet = collect($sheets)->firstWhere('index', (int) $validated['worksheet_index']);
        abort_unless($sheet, 422, 'Selected worksheet is not available.');

        $dataImport->update([
            'worksheet' => $sheet['name'],
            'worksheet_index' => $sheet['index'],
            'row_count' => $sheet['row_count'],
            'column_count' => $sheet['column_count'],
            'source_headers' => $sheet['headers'],
            'status' => 'worksheet_selected',
            'mapping_snapshot' => null,
            'mapping_template_id' => null,
            'validation_summary' => null,
            'error_summary' => null,
            'failed_count' => 0,
            'skipped_count' => 0,
        ]);
        $dataImport->rows()->delete();

        return back()->with('success', 'Worksheet selected. Map the detected columns to Insight fields below.');
    }

    public function saveMapping(
        Request $request,
        DataImport $dataImport,
        ImportMappingRegistry $registry,
    ): RedirectResponse {
        $this->ensureImportEditable($dataImport);

        $workflow = $this->validatedWorkflow($request);
        $columns = $this->validatedMappings($request, $dataImport, $registry, $workflow);

        $dataImport->update([
            'mapping_snapshot' => $this->mappingPayload($workflow, $columns),
            'status' => 'mapped',
            'mapping_template_id' => null,
            'validation_summary' => null,
            'error_summary' => null,
            'failed_count' => 0,
            'skipped_count' => 0,
        ]);
        $dataImport->rows()->delete();

        return back()->with('success', 'Column mapping saved. Validate the spreadsheet rows before any staffing data is changed.');
    }

    public function validateImport(DataImport $dataImport, ImportValidationService $validator): RedirectResponse
    {
        $this->ensureImportEditable($dataImport);

        if (empty($dataImport->mapping_snapshot['columns'] ?? null)) {
            throw ValidationException::withMessages([
                'mapping' => 'Save the column mapping before validating this import.',
            ]);
        }

        try {
            $summary = $validator->validate($dataImport);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['validation' => $exception->getMessage()]);
        }

        $message = $summary['error'] > 0 || $summary['review'] > 0
            ? 'Validation completed. Review the flagged rows before importing.'
            : 'Validation completed. All rows are ready for import.';

        return back()->with('success', $message);
    }

    public function executeImport(DataImport $dataImport, ImportExecutionService $executor): RedirectResponse
    {
        try {
            $summary = $executor->execute($dataImport);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['execution' => $exception->getMessage()]);
        }

        $message = $summary['failed'] > 0
            ? "Import completed with {$summary['failed']} failed row(s). Review the results before rollback or follow-up."
            : "Import completed successfully. {$summary['created']} record(s) created and {$summary['updated']} record(s) updated.";

        return back()->with('success', $message);
    }

    public function rollbackImport(DataImport $dataImport, ImportRollbackService $rollback): RedirectResponse
    {
        try {
            $summary = $rollback->rollback($dataImport, auth()->id());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['rollback' => $exception->getMessage()]);
        }

        $message = $summary['conflicts'] > 0
            ? "Rollback completed with {$summary['conflicts']} conflict(s). Newer record changes were preserved and require manual review."
            : "Rollback completed successfully. {$summary['restored']} record(s) restored and {$summary['deleted']} imported record(s) removed.";

        return back()->with('success', $message);
    }

    public function resolveRow(
        Request $request,
        DataImport $dataImport,
        DataImportRow $dataImportRow,
        ImportConflictResolutionService $conflicts,
    ): RedirectResponse {
        $this->ensureImportEditable($dataImport);

        $validated = $request->validate([
            'row_action' => ['nullable', 'in:continue,skip'],
            'person_action' => ['nullable', 'in:update,use_existing'],
            'position_action' => ['nullable', 'in:update,use_existing'],
            'candidate_action' => ['nullable', 'in:update,use_existing'],
        ]);

        $conflicts->resolve($dataImport, $dataImportRow, $validated);

        return back()->with('success', ($validated['row_action'] ?? null) === 'skip'
            ? "Spreadsheet row {$dataImportRow->source_row_number} will be skipped."
            : "Spreadsheet row {$dataImportRow->source_row_number} review decisions saved.");
    }

    public function saveValueTranslation(
        Request $request,
        DataImport $dataImport,
        ImportMappingRegistry $registry,
        ImportValueTranslationService $translations,
        ImportValidationService $validator,
    ): RedirectResponse {
        $this->ensureImportEditable($dataImport);

        $validated = $request->validate([
            'destination_key' => ['required', 'string', 'max:255'],
            'source_value' => ['required', 'string', 'max:1000'],
            'target_value' => ['required', 'string', 'max:1000'],
        ]);

        $options = $translations->options($dataImport, $registry);
        if (! isset($options[$validated['destination_key']]) || ! in_array($validated['target_value'], $options[$validated['destination_key']], true)) {
            throw ValidationException::withMessages([
                'target_value' => 'Select a valid current Insight value for this destination.',
            ]);
        }

        $translations->save(
            $dataImport,
            $validated['destination_key'],
            $validated['source_value'],
            $validated['target_value'],
        );

        try {
            $validator->validate($dataImport->fresh());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['validation' => $exception->getMessage()]);
        }

        return back()->with('success', "Value mapping saved and validation rerun.");
    }

    public function saveTemplate(
        Request $request,
        DataImport $dataImport,
        ImportMappingRegistry $registry,
    ): RedirectResponse {
        $this->ensureImportEditable($dataImport);

        $validated = $request->validate([
            'template_name' => ['required', 'string', 'max:150'],
            'template_description' => ['nullable', 'string', 'max:1000'],
        ]);

        $workflow = $this->validatedWorkflow($request);
        $columns = $this->validatedMappings($request, $dataImport, $registry, $workflow);
        $payload = $this->mappingPayload($workflow, $columns);

        $template = DataImportMappingTemplate::query()->create([
            'name' => trim($validated['template_name']),
            'description' => filled($validated['template_description'] ?? null) ? trim($validated['template_description']) : null,
            'mapping' => $payload,
            'source_headers' => $dataImport->source_headers,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $dataImport->update([
            'mapping_snapshot' => $payload,
            'mapping_template_id' => $template->id,
            'status' => 'mapped',
            'validation_summary' => null,
            'error_summary' => null,
            'failed_count' => 0,
            'skipped_count' => 0,
        ]);
        $dataImport->rows()->delete();

        return redirect()
            ->route('admin.data-imports.show', [
                'dataImport' => $dataImport,
                'workflow_id' => $workflow?->id,
                'template_id' => $template->id,
            ])
            ->with('success', "Mapping template '{$template->name}' saved.");
    }

    private function resolveWorkflow(
        Request $request,
        $workflows,
        ?DataImportMappingTemplate $template,
        DataImport $dataImport,
        ImportMappingTemplateService $templateService,
    ): ?Workflow {
        if ($this->isImportLocked($dataImport)) {
            $snapshotCode = $dataImport->mapping_snapshot['workflow_code'] ?? null;
            if ($snapshotCode) {
                $match = $workflows->firstWhere('code', $snapshotCode);
                if ($match) {
                    return $match;
                }
            }

            return $workflows->firstWhere('is_primary', true) ?? $workflows->first();
        }

        if ($request->filled('workflow_id')) {
            return $workflows->firstWhere('id', (int) $request->integer('workflow_id'));
        }

        if ($template) {
            $code = $templateService->workflowCode($template);
            if ($code) {
                $match = $workflows->firstWhere('code', $code);
                if ($match) {
                    return $match;
                }
            }
        }

        $snapshotCode = $dataImport->mapping_snapshot['workflow_code'] ?? null;
        if ($snapshotCode) {
            $match = $workflows->firstWhere('code', $snapshotCode);
            if ($match) {
                return $match;
            }
        }

        return $workflows->firstWhere('is_primary', true) ?? $workflows->first();
    }

    private function ensureImportEditable(DataImport $dataImport): void
    {
        if (! $this->isImportLocked($dataImport)) {
            return;
        }

        throw ValidationException::withMessages([
            'import' => 'This import has already been executed and its configuration is now read-only. Use the recorded results or rollback instead of changing the original import.',
        ]);
    }

    private function isImportLocked(DataImport $dataImport): bool
    {
        return $dataImport->completed_at !== null
            || in_array($dataImport->status, [
                'importing',
                'completed',
                'completed_with_errors',
                'rolled_back',
                'rolled_back_with_conflicts',
            ], true)
            || $dataImport->changes()->exists();
    }

    private function validatedWorkflow(Request $request): ?Workflow
    {
        $validated = $request->validate([
            'workflow_id' => ['nullable', 'integer'],
        ]);

        if (empty($validated['workflow_id'])) {
            return null;
        }

        $workflow = Workflow::query()
            ->whereKey($validated['workflow_id'])
            ->where('is_active', true)
            ->first();

        if (! $workflow) {
            throw ValidationException::withMessages([
                'workflow_id' => 'The selected Candidate Workflow is no longer available.',
            ]);
        }

        return $workflow;
    }

    private function validatedMappings(
        Request $request,
        DataImport $dataImport,
        ImportMappingRegistry $registry,
        ?Workflow $workflow,
    ): array {
        if (empty($dataImport->source_headers)) {
            throw ValidationException::withMessages([
                'mappings' => 'Select a worksheet before saving a mapping.',
            ]);
        }

        $validated = $request->validate([
            'mappings' => ['required', 'array'],
            'mappings.*.source_index' => ['required', 'integer', 'min:0'],
            'mappings.*.source_header' => ['nullable', 'string'],
            'mappings.*.destination_key' => ['required', 'string', 'max:500'],
        ]);

        $headers = $dataImport->source_headers;
        $seenIndexes = [];
        $seenDestinations = [];
        $columns = [];

        foreach ($validated['mappings'] as $mapping) {
            $index = (int) $mapping['source_index'];
            $destination = $mapping['destination_key'];

            if (! array_key_exists($index, $headers)) {
                throw ValidationException::withMessages([
                    'mappings' => "Column {$index} is not present in the selected worksheet.",
                ]);
            }

            if (isset($seenIndexes[$index])) {
                throw ValidationException::withMessages([
                    'mappings' => 'A worksheet column can only be mapped once.',
                ]);
            }
            $seenIndexes[$index] = true;

            if (! $registry->has($destination, $workflow)) {
                throw ValidationException::withMessages([
                    'mappings' => "The selected destination for '{$headers[$index]}' is no longer available.",
                ]);
            }

            if ($destination !== 'ignore') {
                if (isset($seenDestinations[$destination])) {
                    throw ValidationException::withMessages([
                        'mappings' => "The destination '{$destination}' is mapped from more than one spreadsheet column.",
                    ]);
                }
                $seenDestinations[$destination] = true;
            }

            $columns[] = [
                'source_index' => $index,
                'source_header' => (string) $headers[$index],
                'destination_key' => $destination,
            ];
        }

        if (count($columns) !== count($headers)) {
            throw ValidationException::withMessages([
                'mappings' => 'Every spreadsheet column must be mapped or explicitly set to Do Not Import.',
            ]);
        }

        usort($columns, fn (array $a, array $b) => $a['source_index'] <=> $b['source_index']);

        return $columns;
    }

    private function mappingPayload(?Workflow $workflow, array $columns): array
    {
        return [
            'version' => 1,
            'workflow_code' => $workflow?->code,
            'workflow_name' => $workflow?->name,
            'columns' => $columns,
            'saved_at' => now()->toIso8601String(),
        ];
    }
}
