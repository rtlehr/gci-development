<?php

namespace App\Services\DataImport;

use App\Models\Candidate;
use App\Models\CandidateStepEvent;
use App\Models\CustomFieldValue;
use App\Models\DataImport;
use App\Models\DataImportChange;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportRollbackService
{
    /**
     * Limit rollback to model types the data importer itself journals.
     */
    private const ALLOWED_MODELS = [
        Person::class,
        Position::class,
        Candidate::class,
        PositionAssignment::class,
        CandidateStepEvent::class,
        CustomFieldValue::class,
    ];

    public function rollback(DataImport $import, ?int $userId = null): array
    {
        $import->refresh();

        if (! in_array($import->status, ['completed', 'completed_with_errors'], true) || ! $import->completed_at) {
            throw new RuntimeException('Only a completed data import can be rolled back.');
        }

        if ($import->rolled_back_at || in_array($import->status, ['rolled_back', 'rolled_back_with_conflicts'], true)) {
            throw new RuntimeException('This data import has already been rolled back.');
        }

        $changes = $import->changes()->orderByDesc('sequence')->get();
        if ($changes->isEmpty()) {
            throw new RuntimeException('This import has no execution journal to roll back.');
        }

        $restored = 0;
        $deleted = 0;
        $conflicts = [];
        $blockedModels = [];

        foreach ($changes as $change) {
            $modelKey = $change->model_type.'#'.$change->model_id;
            if (isset($blockedModels[$modelKey])) continue;

            try {
                $outcome = DB::transaction(fn () => $this->rollbackChange($change));

                if ($outcome === 'restored') $restored++;
                if ($outcome === 'deleted') $deleted++;
            } catch (Throwable $exception) {
                $blockedModels[$modelKey] = true;
                $conflict = [
                    'change_id' => $change->id,
                    'row_id' => $change->data_import_row_id,
                    'sequence' => $change->sequence,
                    'model_type' => class_basename($change->model_type),
                    'model_id' => $change->model_id,
                    'action' => $change->action,
                    'message' => $exception->getMessage(),
                ];
                $conflicts[] = $conflict;
                $this->recordRowConflict($change, $conflict);
            }
        }

        $summary = [
            'restored' => $restored,
            'deleted' => $deleted,
            'conflicts' => count($conflicts),
            'conflict_items' => $conflicts,
        ];

        $errorSummary = $import->error_summary ?? [];
        $errorSummary['rollback'] = $summary;

        $import->update([
            'status' => $conflicts === [] ? 'rolled_back' : 'rolled_back_with_conflicts',
            'rolled_back_at' => now(),
            'rolled_back_by' => $userId,
            'error_summary' => $errorSummary,
        ]);

        return $summary;
    }

    private function rollbackChange(DataImportChange $change): string
    {
        $modelClass = $change->model_type;
        if (! in_array($modelClass, self::ALLOWED_MODELS, true)) {
            throw new RuntimeException("Rollback does not support model type '{$modelClass}'.");
        }

        /** @var Model|null $model */
        $model = $modelClass::query()->find($change->model_id);
        $after = $this->decryptPayload($change->after_payload);

        if ($change->action === 'create') {
            if (! $model) return 'deleted';
            if (! $this->sameSnapshot($this->snapshot($model), $after)) {
                throw new RuntimeException('Record changed after the import and was not deleted.');
            }

            $model->deleteQuietly();
            return 'deleted';
        }

        if ($change->action === 'update') {
            if (! $model) {
                throw new RuntimeException('Updated record no longer exists, so its previous values could not be restored.');
            }
            if (! $this->sameSnapshot($this->snapshot($model), $after)) {
                throw new RuntimeException('Record changed after the import and its newer values were preserved.');
            }

            $before = $this->decryptPayload($change->before_payload);
            if ($before === null) {
                throw new RuntimeException('The rollback journal is missing the previous record values.');
            }

            $this->restoreSnapshot($model, $before);
            return 'restored';
        }

        throw new RuntimeException("Unsupported rollback journal action '{$change->action}'.");
    }


    private function restoreSnapshot(Model $model, array $before): void
    {
        // Restrict restoration to attributes that are actually present on the
        // persisted model. This avoids relying on schema introspection while
        // still preventing arbitrary/non-column snapshot keys from being used.
        $persistedKeys = array_flip(array_keys($model->getAttributes()));
        $attributes = array_intersect_key($before, $persistedKeys);

        // Never change the primary key while restoring a journal snapshot.
        unset($attributes[$model->getKeyName()]);

        if ($attributes === []) {
            throw new RuntimeException('The rollback journal did not contain any restorable record values.');
        }

        // Feed the logical snapshot values through the model so casts and
        // custom setters (including encrypted casts) produce the correct raw
        // database representation. Then update the row directly. This avoids
        // model events/observers and makes rollback deterministic.
        foreach ($attributes as $key => $value) {
            $model->setAttribute($key, $value);
        }

        $rawAttributes = array_intersect_key(
            $model->getAttributes(),
            array_flip(array_keys($attributes)),
        );

        $updated = DB::table($model->getTable())
            ->where($model->getKeyName(), $model->getKey())
            ->update($rawAttributes);

        // Some databases legitimately report 0 affected rows when all restored
        // values already match. Verification below is the source of truth.
        if ($updated < 0) {
            throw new RuntimeException('The previous record values could not be saved during rollback.');
        }

        $model->refresh();

        if (! $this->sameSnapshot($this->snapshot($model), $before)) {
            throw new RuntimeException('The previous record values could not be fully restored during rollback.');
        }
    }

    private function decryptPayload(?string $payload): ?array
    {
        if ($payload === null) return null;

        $decoded = json_decode(Crypt::decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) throw new RuntimeException('The rollback journal contains an invalid encrypted snapshot.');

        return $decoded;
    }

    private function snapshot(Model $model): array
    {
        return $model->fresh()?->attributesToArray() ?? $model->attributesToArray();
    }

    private function sameSnapshot(?array $current, ?array $expected): bool
    {
        if ($current === null || $expected === null) return $current === $expected;

        return $this->canonicalize($current) === $this->canonicalize($expected);
    }

    private function canonicalize(array $values): array
    {
        ksort($values);

        foreach ($values as $key => $value) {
            if (is_array($value)) $values[$key] = $this->canonicalize($value);
        }

        return $values;
    }

    private function recordRowConflict(DataImportChange $change, array $conflict): void
    {
        $row = $change->row;
        if (! $row) return;

        $issues = $row->issues ?? [];
        $issues[] = [
            'code' => 'rollback_conflict',
            'severity' => 'warning',
            'message' => $conflict['message'],
            'model_type' => $conflict['model_type'],
            'model_id' => $conflict['model_id'],
        ];

        $result = $row->result ?? [];
        $rollback = $result['rollback'] ?? ['conflicts' => []];
        $rollback['conflicts'][] = $conflict;
        $result['rollback'] = $rollback;

        $row->update([
            'issues' => $issues,
            'result' => $result,
        ]);
    }
}
