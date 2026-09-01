<?php

namespace App\Services\DataImport;

use App\Models\DataImport;
use App\Models\DataImportRow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ImportConflictResolutionService
{
    private const ENTITY_ISSUES = [
        'person' => 'existing_person',
        'position' => 'existing_position',
        'candidate' => 'existing_candidate',
    ];

    public function review(DataImportRow $row): array
    {
        $mapped = $row->result['mapped_values'] ?? [];

        return [
            'resolutions' => $row->result['resolutions'] ?? [],
            'differences' => array_values(array_filter([
                $this->entityDifferences('person', $row->person, $mapped),
                $this->entityDifferences('position', $row->position, $mapped),
                $this->entityDifferences('candidate', $row->candidate, $mapped),
            ])),
        ];
    }

    public function resolve(DataImport $import, DataImportRow $row, array $decisions): void
    {
        if ((int) $row->data_import_id !== (int) $import->id) abort(404);

        $result = $row->result ?? [];
        $resolutions = $result['resolutions'] ?? [];

        if (($decisions['row_action'] ?? null) === 'skip') {
            $resolutions['row'] = 'skip';
            $result['resolutions'] = $resolutions;
            $row->update(['status' => 'ignored', 'action' => 'skip', 'result' => $result]);
            $this->refreshSummary($import);
            return;
        }

        $resolutions['row'] = 'continue';

        foreach (array_keys(self::ENTITY_ISSUES) as $entity) {
            if (! $this->entityPresent($row, $entity)) continue;

            $decision = $decisions[$entity.'_action'] ?? null;
            if (! in_array($decision, ['update', 'use_existing'], true)) {
                throw ValidationException::withMessages([
                    $entity.'_action' => 'Choose Update Existing or Use Existing Without Changes for the matched '.ucfirst($entity).'.',
                ]);
            }
            $resolutions[$entity] = $decision;
        }

        $result['resolutions'] = $resolutions;
        $issues = collect($row->issues ?? [])->reject(function (array $issue) use ($resolutions): bool {
            foreach (self::ENTITY_ISSUES as $entity => $code) {
                if (($issue['code'] ?? null) === $code && isset($resolutions[$entity])) return true;
            }
            return false;
        })->values()->all();

        $status = collect($issues)->contains(fn (array $issue) => ($issue['severity'] ?? null) === 'error')
            ? 'error'
            : (collect($issues)->contains(fn (array $issue) => ($issue['severity'] ?? null) === 'review') ? 'review' : 'ready');

        $row->update([
            'status' => $status,
            'action' => $this->resolvedAction($resolutions),
            'issues' => $issues,
            'result' => $result,
        ]);

        $this->refreshSummary($import);
    }

    public function refreshSummary(DataImport $import): array
    {
        $counts = $import->rows()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = [
            'total' => (int) $counts->sum(),
            'ready' => (int) ($counts['ready'] ?? 0),
            'review' => (int) ($counts['review'] ?? 0),
            'error' => (int) ($counts['error'] ?? 0),
            'ignored' => (int) ($counts['ignored'] ?? 0),
        ];

        $import->update([
            'status' => $summary['error'] > 0 || $summary['review'] > 0 ? 'validated_with_issues' : 'validated',
            'validation_summary' => $summary,
            'failed_count' => $summary['error'],
            'skipped_count' => $summary['ignored'],
        ]);

        return $summary;
    }

    private function entityDifferences(string $entity, ?Model $model, array $mapped): ?array
    {
        if (! $model) return null;

        $prefix = $entity.'.';
        $fields = [];
        foreach ($mapped as $key => $incoming) {
            if (! str_starts_with($key, $prefix)) continue;
            $attribute = substr($key, strlen($prefix));
            $current = $this->currentValue($model, $attribute);

            $fields[] = [
                'destination_key' => $key,
                'field' => $this->label($attribute),
                'current' => $this->display($current),
                'incoming' => $this->display($incoming),
                'different' => $this->comparable($current) !== $this->comparable($incoming),
            ];
        }

        return [
            'entity' => $entity,
            'id' => $model->getKey(),
            'fields' => $fields,
        ];
    }

    private function currentValue(Model $model, string $attribute): mixed
    {
        if (array_key_exists($attribute, $model->getAttributes())) {
            return $model->getAttribute($attribute);
        }

        // These importer destinations intentionally represent application concepts
        // rather than direct columns. Show the closest persisted display value when
        // one is available without invoking optional relationships.
        if ($attribute === 'staffing_state' && array_key_exists('status', $model->getAttributes())) {
            return $model->getAttribute('status');
        }
        if ($attribute === 'job_title' && array_key_exists('job_title', $model->getAttributes())) {
            return $model->getAttribute('job_title');
        }

        return null;
    }

    private function entityPresent(DataImportRow $row, string $entity): bool
    {
        return match ($entity) {
            'person' => filled($row->person_id),
            'position' => filled($row->position_id),
            'candidate' => filled($row->candidate_id),
            default => false,
        };
    }

    private function resolvedAction(array $resolutions): string
    {
        if (($resolutions['row'] ?? null) === 'skip') return 'skip';
        if (in_array('update', $resolutions, true)) return 'update_existing';
        if (in_array('use_existing', $resolutions, true)) return 'use_existing';
        return 'create';
    }

    private function label(string $attribute): string
    {
        return str($attribute)->replace('_', ' ')->title()->toString();
    }

    private function display(mixed $value): mixed
    {
        if ($value === null || $value === '') return null;
        if (is_bool($value)) return $value ? 'Yes' : 'No';
        if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d');
        if (is_array($value)) return implode(', ', array_map('strval', $value));
        return (string) $value;
    }

    private function comparable(mixed $value): string
    {
        $display = $this->display($value);
        return mb_strtolower(trim((string) ($display ?? '')));
    }
}
