<?php

namespace App\Services\DataImport;

use App\Models\Candidate;
use App\Models\CustomField;
use App\Models\DataImport;
use App\Models\DataImportChange;
use App\Models\DataImportRow;
use App\Models\JobTitle;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ImportExecutionService
{
    private int $sequence = 0;
    private int $created = 0;
    private int $updated = 0;

    public function execute(DataImport $import): array
    {
        $import->refresh();

        if ($import->completed_at || in_array($import->status, ['completed', 'completed_with_errors', 'rolled_back'], true)) {
            throw new RuntimeException('This data import has already been executed.');
        }

        if (empty($import->mapping_snapshot['columns'] ?? null) || ! $import->validation_summary) {
            throw new RuntimeException('Save the mapping and validate the spreadsheet before running the import.');
        }

        if (($import->validation_summary['error'] ?? 0) > 0 || ($import->validation_summary['review'] ?? 0) > 0) {
            throw new RuntimeException('Resolve all validation errors and review items before running the import.');
        }

        if ($import->rows()->whereNotIn('status', ['ready', 'ignored'])->exists()) {
            throw new RuntimeException('Every spreadsheet row must be ready or skipped before running the import.');
        }

        if ($import->changes()->exists()) {
            throw new RuntimeException('This import already has an execution journal and cannot be run again.');
        }

        $this->sequence = 0;
        $this->created = 0;
        $this->updated = 0;
        $failed = 0;
        $skipped = 0;

        $import->update([
            'status' => 'importing',
            'started_at' => now(),
            'completed_at' => null,
            'created_count' => 0,
            'updated_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
        ]);

        foreach ($import->rows()->orderBy('source_row_number')->get() as $row) {
            if ($row->status === 'ignored' || $row->action === 'skip') {
                $skipped++;
                continue;
            }

            $createdBeforeRow = $this->created;
            $updatedBeforeRow = $this->updated;

            try {
                DB::transaction(function () use ($import, $row): void {
                    $this->executeRow($import, $row);
                });
            } catch (Throwable $exception) {
                // The row transaction rolled back, so its in-memory counters must roll back too.
                $this->created = $createdBeforeRow;
                $this->updated = $updatedBeforeRow;
                $failed++;
                $safeMessage = $this->safeExecutionMessage($exception);
                $issues = $row->issues ?? [];
                $issues[] = [
                    'code' => 'import_execution_failed',
                    'severity' => 'error',
                    'message' => $safeMessage,
                ];

                $result = $row->result ?? [];
                $result['execution'] = [
                    'status' => 'failed',
                    'message' => $safeMessage,
                ];

                $row->update([
                    'status' => 'error',
                    'action' => 'failed',
                    'issues' => $issues,
                    'result' => $result,
                ]);
            }
        }

        $status = $failed > 0 ? 'completed_with_errors' : 'completed';
        $summary = [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $skipped,
            'failed' => $failed,
        ];

        $import->update([
            'status' => $status,
            'completed_at' => now(),
            'created_count' => $this->created,
            'updated_count' => $this->updated,
            'skipped_count' => $skipped,
            'failed_count' => $failed,
            'error_summary' => $failed > 0 ? ['import_execution_failed' => $failed] : [],
        ]);

        return $summary;
    }


    private function safeExecutionMessage(Throwable $exception): string
    {
        if ($exception instanceof QueryException) {
            report($exception);

            return 'Insight could not save this row because the database rejected one or more values. Revalidate the row and review required fields or duplicate values before trying again.';
        }

        $message = trim($exception->getMessage());
        if ($message === '') {
            report($exception);

            return 'Insight could not import this row. Review the row and try again.';
        }

        // Do not expose SQL statements, connection details, filesystem paths, or stack-oriented
        // exception output on the import results screen.
        if (preg_match('/SQLSTATE|select |insert |update |delete |\\vendor\\|\/vendor\/|Stack trace:/i', $message)) {
            report($exception);

            return 'Insight could not save this row because an internal data error occurred. Review the row and try again; administrators can use the application log for technical details.';
        }

        return mb_strlen($message) > 500 ? mb_substr($message, 0, 497).'...' : $message;
    }

    private function executeRow(DataImport $import, DataImportRow $row): void
    {
        $mapped = $row->result['mapped_values'] ?? [];
        $resolutions = $row->result['resolutions'] ?? [];

        $position = $this->position($import, $row, $mapped, $resolutions);
        $person = $this->person($import, $row, $mapped, $resolutions);
        $candidate = $this->candidate($import, $row, $mapped, $resolutions, $person, $position);

        if ($position) {
            $this->customFields($import, $row, $position, CustomField::ENTITY_POSITION, $mapped);
        }
        if ($person) {
            $this->customFields($import, $row, $person, CustomField::ENTITY_PERSON, $mapped);
        }
        if ($candidate) {
            $this->workflowEvents($import, $row, $candidate, $mapped);
        }
        if ($person && $position) {
            $this->assignment($import, $row, $person, $position, $mapped);
        }

        $result = $row->result ?? [];
        $result['execution'] = [
            'status' => 'completed',
            'person_id' => $person?->id,
            'position_id' => $position?->id,
            'candidate_id' => $candidate?->id,
        ];

        $row->update([
            'status' => 'imported',
            'action' => 'imported',
            'person_id' => $person?->id,
            'position_id' => $position?->id,
            'candidate_id' => $candidate?->id,
            'issues' => [],
            'result' => $result,
        ]);
    }

    private function person(DataImport $import, DataImportRow $row, array $mapped, array $resolutions): ?Person
    {
        $values = $this->prefixed($mapped, 'person.');
        $custom = $this->prefixed($mapped, 'custom.person.');
        if ($values === [] && $custom === [] && ! $row->person_id) return null;

        $person = $row->person_id ? Person::query()->find($row->person_id) : null;

        // Re-resolve at execution time as a final uniqueness guard. This protects
        // against a stale/missing row match as well as another row in the same
        // import having created the Person after validation completed.
        if (! $person) {
            $personCode = $values['person_code'] ?? null;
            $email = $values['email'] ?? null;

            if (filled($personCode)) {
                $person = Person::findByPersonCode($personCode);
            }

            if (! $person && filled($email)) {
                $matches = Person::query()
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim((string) $email))])
                    ->limit(2)
                    ->get();

                if ($matches->count() > 1) {
                    throw new RuntimeException("More than one Person matches email '{$email}'. Revalidate this import before executing it.");
                }

                $person = $matches->first();
            }
        }

        if ($person) {
            $resolution = $resolutions['person'] ?? null;

            if ($resolution === 'use_existing') return $person;

            // An execution-time match that was not explicitly reviewed must not
            // silently update an existing Person. Force a fresh validation/review.
            if ($resolution !== 'update') {
                throw new RuntimeException('An existing Person now matches this row. Revalidate the import and review the Person before executing it.');
            }

            $attributes = $this->filterColumns(new Person(), $values);
            $this->updateModel($import, $row, $person, $attributes);
            return $person->fresh();
        }

        $attributes = $this->filterColumns(new Person(), $values);
        if ($attributes === []) return null;
        $person = new Person();
        $person->forceFill($attributes)->save();
        $this->journal($import, $row, $person, 'create', null, $this->snapshot($person));
        $this->created++;

        return $person;
    }

    private function position(DataImport $import, DataImportRow $row, array $mapped, array $resolutions): ?Position
    {
        $values = $this->prefixed($mapped, 'position.');
        $custom = $this->prefixed($mapped, 'custom.position.');
        if ($values === [] && $custom === [] && ! $row->position_id) return null;

        $position = $row->position_id ? Position::query()->find($row->position_id) : null;
        if ($position && ($resolutions['position'] ?? null) === 'use_existing') return $position;

        $attributes = $this->positionAttributes($values);
        if ($position) {
            $this->updateModel($import, $row, $position, $attributes);
            return $position->fresh();
        }

        if ($attributes === []) return null;
        $position = new Position();
        if (! isset($attributes['status']) && Schema::hasColumn($position->getTable(), 'status')) {
            $attributes['status'] = 'Open';
        }
        $position->forceFill($attributes)->save();
        $this->journal($import, $row, $position, 'create', null, $this->snapshot($position));
        $this->created++;

        return $position;
    }

    private function candidate(DataImport $import, DataImportRow $row, array $mapped, array $resolutions, ?Person $person, ?Position $position): ?Candidate
    {
        $candidateValues = $this->prefixed($mapped, 'candidate.');
        $hasWorkflow = collect(array_keys($mapped))->contains(fn (string $key) => str_starts_with($key, 'workflow.'));
        $hasAssignment = collect(array_keys($mapped))->contains(fn (string $key) => str_starts_with($key, 'assignment.'));
        $needsCandidate = $candidateValues !== [] || $hasWorkflow || $hasAssignment || $row->candidate_id;
        if (! $needsCandidate) return null;
        if (! $person || ! $position) throw new RuntimeException('Candidate data requires both a Person and a Position.');

        $candidate = $row->candidate_id ? Candidate::query()->find($row->candidate_id) : null;
        if ($candidate && ($resolutions['candidate'] ?? null) === 'use_existing') return $candidate;

        $attributes = $this->filterColumns(new Candidate(), $candidateValues);
        if ($candidate) {
            $this->updateModel($import, $row, $candidate, $attributes);
            return $candidate->fresh();
        }

        $workflow = $this->workflow($import);
        if (! $workflow) throw new RuntimeException('A Candidate Workflow is required to create a Candidate.');

        $attributes['person_id'] = $person->id;
        $attributes['position_id'] = $position->id;
        $attributes['workflow_id'] = $workflow->id;
        if (! isset($attributes['status'])) $attributes['status'] = 'submitted';

        $candidate = new Candidate();
        $candidate->forceFill($this->filterColumns($candidate, $attributes))->save();
        $this->journal($import, $row, $candidate, 'create', null, $this->snapshot($candidate));
        $this->created++;

        return $candidate;
    }

    private function assignment(DataImport $import, DataImportRow $row, Person $person, Position $position, array $mapped): void
    {
        $values = $this->prefixed($mapped, 'assignment.');
        if ($values === []) return;

        $assignment = PositionAssignment::query()
            ->where('person_id', $person->id)
            ->where('position_id', $position->id)
            ->orderByDesc('id')
            ->first();

        $attributes = $this->filterColumns(new PositionAssignment(), $values);
        $attributes['person_id'] = $person->id;
        $attributes['position_id'] = $position->id;

        if (! $assignment && blank($attributes['start_date'] ?? null)) {
            throw new RuntimeException('A new Position Assignment requires Start Date. Revalidate the import after mapping Position Assignment — Start Date.');
        }

        if ($assignment) {
            $this->updateModel($import, $row, $assignment, $attributes);
            return;
        }

        $assignment = new PositionAssignment();
        $assignment->forceFill($this->filterColumns($assignment, $attributes))->save();
        $this->journal($import, $row, $assignment, 'create', null, $this->snapshot($assignment));
        $this->created++;
    }

    private function workflowEvents(DataImport $import, DataImportRow $row, Candidate $candidate, array $mapped): void
    {
        $workflow = $this->workflow($import);
        if (! $workflow) return;

        $byStep = [];
        foreach ($mapped as $key => $value) {
            if (! str_starts_with($key, 'workflow.')) continue;
            $parts = explode('.', $key);
            if (count($parts) !== 4) continue;
            [, $workflowCode, $stepCode, $property] = $parts;
            if ($workflowCode !== $workflow->code) continue;
            $byStep[$stepCode][$property === 'status' ? 'status_code' : $property] = $value;
        }

        foreach ($byStep as $stepCode => $attributes) {
            $step = $workflow->steps()->where('code', $stepCode)->first();
            if (! $step) throw new RuntimeException("Workflow step '{$stepCode}' is no longer available.");

            if (! method_exists($candidate, 'stepEvents')) {
                throw new RuntimeException('Candidate workflow events are not available on this installation.');
            }

            $event = $candidate->stepEvents()->where('workflow_step_id', $step->id)->first();
            $attributes['workflow_step_id'] = $step->id;

            if ($event) {
                $this->updateModel($import, $row, $event, $this->filterColumns($event, $attributes));
                continue;
            }

            $event = $candidate->stepEvents()->create($attributes);
            $this->journal($import, $row, $event, 'create', null, $this->snapshot($event));
            $this->created++;
        }
    }

    private function customFields(DataImport $import, DataImportRow $row, Model $owner, string $entityType, array $mapped): void
    {
        $prefix = "custom.{$entityType}.";
        foreach ($mapped as $key => $value) {
            if (! str_starts_with($key, $prefix)) continue;
            $fieldKey = substr($key, strlen($prefix));
            $field = CustomField::query()->where('entity_type', $entityType)->where('key', $fieldKey)->first();
            if (! $field) throw new RuntimeException("Custom field '{$fieldKey}' is no longer available.");

            if (method_exists($owner, 'customFieldValues')) {
                $existing = $owner->customFieldValues()->where('custom_field_id', $field->id)->first();
                if ($existing) {
                    $column = Schema::hasColumn($existing->getTable(), 'value') ? 'value' : (Schema::hasColumn($existing->getTable(), 'field_value') ? 'field_value' : null);
                    if (! $column) throw new RuntimeException('Custom field value storage is not supported by the importer.');
                    $this->updateModel($import, $row, $existing, [$column => $value]);
                } else {
                    $related = $owner->customFieldValues()->getRelated();
                    $column = Schema::hasColumn($related->getTable(), 'value') ? 'value' : (Schema::hasColumn($related->getTable(), 'field_value') ? 'field_value' : null);
                    if (! $column) throw new RuntimeException('Custom field value storage is not supported by the importer.');
                    $created = $owner->customFieldValues()->create(['custom_field_id' => $field->id, $column => $value]);
                    $this->journal($import, $row, $created, 'create', null, $this->snapshot($created));
                    $this->created++;
                }
                continue;
            }

            throw new RuntimeException('Custom field values are not available on this installation.');
        }
    }

    private function positionAttributes(array $values): array
    {
        $position = new Position();
        $attributes = [];

        foreach ($values as $key => $value) {
            if ($key === 'staffing_state') {
                if (Schema::hasColumn($position->getTable(), 'status')) $attributes['status'] = $this->positionStatus((string) $value);
                continue;
            }
            if ($key === 'job_title') {
                $jobTitle = JobTitle::query()->where('is_active', true)->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])->first();
                if (! $jobTitle) throw new RuntimeException("Job Title '{$value}' is no longer available.");
                if (Schema::hasColumn($position->getTable(), 'job_title_id')) $attributes['job_title_id'] = $jobTitle->id;
                if (Schema::hasColumn($position->getTable(), 'job_title')) $attributes['job_title'] = $jobTitle->name;
                continue;
            }
            if ($key === 'project_manager') {
                $user = $this->projectManager((string) $value);
                if (! $user) throw new RuntimeException("Project Manager '{$value}' is no longer uniquely available.");
                if (Schema::hasColumn($position->getTable(), 'project_manager_user_id')) $attributes['project_manager_user_id'] = $user->id;
                continue;
            }
            if (in_array($key, ['position_organization', 'sponsoring_organization', 'funding_organization'], true)) {
                $organization = Organization::query()->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])->first();
                if (! $organization) throw new RuntimeException("Organization '{$value}' is no longer available.");
                $column = $key.'_id';
                if (Schema::hasColumn($position->getTable(), $column)) $attributes[$column] = $organization->id;
                continue;
            }

            $column = match ($key) {
                'close_date' => Schema::hasColumn($position->getTable(), 'close_date') ? 'close_date' : 'closed_at',
                'close_reason' => Schema::hasColumn($position->getTable(), 'close_reason') ? 'close_reason' : 'closed_reason',
                default => $key,
            };
            if (Schema::hasColumn($position->getTable(), $column)) $attributes[$column] = $value;
        }

        return $attributes;
    }

    private function projectManager(string $value): ?User
    {
        $needle = mb_strtolower(trim($value));
        $matches = User::query()->with('person')->get()->filter(function (User $user) use ($needle): bool {
            $values = [mb_strtolower(trim((string) $user->name)), mb_strtolower(trim((string) $user->email))];
            if ($user->person) {
                $values[] = mb_strtolower(trim((string) $user->person->last_name));
                $values[] = mb_strtolower(trim(($user->person->first_name ?? '').' '.($user->person->last_name ?? '')));
            }
            return in_array($needle, array_filter($values), true);
        })->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function workflow(DataImport $import): ?Workflow
    {
        $code = $import->mapping_snapshot['workflow_code'] ?? null;
        if ($code) return Workflow::query()->where('code', $code)->where('is_active', true)->first();
        return Workflow::query()->where('is_active', true)->where('is_primary', true)->first();
    }

    private function updateModel(DataImport $import, DataImportRow $row, Model $model, array $attributes): void
    {
        if ($attributes === []) return;
        $before = $this->snapshot($model);
        $model->forceFill($attributes);
        if (! $model->isDirty()) return;
        $model->save();
        $this->journal($import, $row, $model, 'update', $before, $this->snapshot($model));
        $this->updated++;
    }

    private function journal(DataImport $import, DataImportRow $row, Model $model, string $action, ?array $before, ?array $after): void
    {
        DataImportChange::query()->create([
            'data_import_id' => $import->id,
            'data_import_row_id' => $row->id,
            'sequence' => ++$this->sequence,
            'model_type' => $model::class,
            'model_id' => (string) $model->getKey(),
            'action' => $action,
            'before_payload' => $before === null ? null : Crypt::encryptString(json_encode($before, JSON_THROW_ON_ERROR)),
            'after_payload' => $after === null ? null : Crypt::encryptString(json_encode($after, JSON_THROW_ON_ERROR)),
        ]);
    }

    private function snapshot(Model $model): array
    {
        return $model->fresh()?->attributesToArray() ?? $model->attributesToArray();
    }

    private function filterColumns(Model $model, array $attributes): array
    {
        return collect($attributes)
            ->filter(fn ($value, string $key) => $value !== null && Schema::hasColumn($model->getTable(), $key))
            ->all();
    }

    private function prefixed(array $mapped, string $prefix): array
    {
        $values = [];
        foreach ($mapped as $key => $value) {
            if (str_starts_with($key, $prefix) && $value !== null) $values[substr($key, strlen($prefix))] = $value;
        }
        return $values;
    }

    private function positionStatus(string $state): string
    {
        return match (mb_strtolower(trim($state))) {
            'selected', 'on-hold', 'on hold' => 'In Process',
            'filled', 'departing' => 'Closed',
            default => 'Open',
        };
    }
}
