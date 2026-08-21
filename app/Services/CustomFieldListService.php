<?php

namespace App\Services;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Services\Encryption\LookupHashService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomFieldListService
{
    public function augmentDefinition(array $definition, string $entityType): array
    {
        $nextOrder = collect($definition['columns'])->max('default_order') ?? 0;

        $columns = CustomField::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->where('is_list_column', true)
            ->where('is_sensitive', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (CustomField $field) use (&$nextOrder): array {
                $nextOrder++;

                return [
                    'key' => $this->columnKey($field->id),
                    'label' => $field->name,
                    'db_field' => null,
                    'sortable' => $field->field_type !== CustomField::TYPE_CHECKBOX,
                    'searchable' => (bool) $field->is_searchable,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => $nextOrder,
                    'custom_field_id' => $field->id,
                    'custom_field_type' => $field->field_type,
                ];
            })
            ->all();

        $definition['columns'] = array_merge($definition['columns'], $columns);
        $definition['custom_field_entity_type'] = $entityType;

        return $definition;
    }

    public function filterDefinitions(string $entityType): array
    {
        return CustomField::query()
            ->with('activeOptions')
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->where('is_filterable', true)
            ->where('is_sensitive', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CustomField $field) => [
                'id' => $field->id,
                'name' => $field->name,
                'field_type' => $field->field_type,
                'options' => $field->activeOptions->map(fn ($option) => [
                    'value' => $option->value,
                    'label' => $option->label,
                ])->values()->all(),
            ])->all();
    }

    public function applySearch(Builder $query, Collection $columns, string $search, string $modelClass, string $table): void
    {
        if ($search === '') return;

        $query->where(function (Builder $outer) use ($columns, $search, $modelClass, $table): void {
            foreach ($columns as $column) {
                if (! ($column['searchable'] ?? false)) continue;

                if (! empty($column['custom_field_id'])) {
                    $fieldId = (int) $column['custom_field_id'];
                    $outer->orWhereExists(function ($sub) use ($fieldId, $search, $modelClass, $table): void {
                        $sub->selectRaw('1')
                            ->from('custom_field_values as cfv')
                            ->whereColumn('cfv.fieldable_id', "{$table}.id")
                            ->where('cfv.fieldable_type', $modelClass)
                            ->where('cfv.custom_field_id', $fieldId)
                            ->where(function ($valueQuery) use ($search): void {
                                $valueQuery->where('cfv.value_text', 'like', "%{$search}%")
                                    ->orWhere('cfv.value_date', 'like', "%{$search}%")
                                    ->orWhereRaw('CAST(cfv.value_json AS CHAR) LIKE ?', ["%{$search}%"]);
                            });
                    });
                } elseif (! empty($column['lookup_hash_field'])) {
                    $outer->orWhere(
                        $column['lookup_hash_field'],
                        app(LookupHashService::class)->hash($search),
                    );
                } elseif (! empty($column['db_field'])) {
                    $outer->orWhere($column['db_field'], 'like', "%{$search}%");
                }
            }
        });
    }

    public function applyFilters(Builder $query, Request $request, string $entityType, string $modelClass, string $table): void
    {
        $input = $request->input('custom_filters', []);
        if (! is_array($input)) return;

        $fields = CustomField::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->where('is_filterable', true)
            ->where('is_sensitive', false)
            ->whereIn('id', array_map('intval', array_keys($input)))
            ->get()
            ->keyBy('id');

        foreach ($input as $fieldId => $raw) {
            $field = $fields->get((int) $fieldId);
            if (! $field || $raw === null || $raw === '' || $raw === []) continue;

            $query->whereExists(function ($sub) use ($field, $raw, $modelClass, $table): void {
                $sub->selectRaw('1')
                    ->from('custom_field_values as cfv')
                    ->whereColumn('cfv.fieldable_id', "{$table}.id")
                    ->where('cfv.fieldable_type', $modelClass)
                    ->where('cfv.custom_field_id', $field->id);

                if ($field->field_type === CustomField::TYPE_DATE) {
                    $sub->whereDate('cfv.value_date', $raw);
                } elseif ($field->field_type === CustomField::TYPE_CHECKBOX) {
                    $sub->whereJsonContains('cfv.value_json', $raw);
                } elseif ($field->field_type === CustomField::TYPE_RADIO) {
                    $sub->where('cfv.value_text', $raw);
                } else {
                    $sub->where('cfv.value_text', 'like', '%'.trim((string) $raw).'%');
                }
            });
        }
    }

    public function applySort(Builder $query, array $column, string $direction, string $modelClass, string $table): void
    {
        $fieldId = (int) ($column['custom_field_id'] ?? 0);
        if (! $fieldId) return;

        $valueColumn = ($column['custom_field_type'] ?? null) === CustomField::TYPE_DATE
            ? 'value_date'
            : 'value_text';

        $query->orderBy(
            CustomFieldValue::query()
                ->select($valueColumn)
                ->whereColumn('fieldable_id', "{$table}.id")
                ->where('fieldable_type', $modelClass)
                ->where('custom_field_id', $fieldId)
                ->limit(1),
            $direction
        );
    }

    public function hydrateRows(iterable $rows, array $definition): void
    {
        $customColumns = collect($definition['columns'])->filter(fn ($column) => ! empty($column['custom_field_id']));
        if ($customColumns->isEmpty()) return;

        $models = $rows instanceof LengthAwarePaginator ? collect($rows->items()) : collect($rows);
        $ids = $models->pluck('id')->filter()->values();
        if ($ids->isEmpty()) return;

        $modelClass = $definition['entity_model'] ?? null;
        if (! $modelClass) return;

        $fieldIds = $customColumns->pluck('custom_field_id')->map(fn ($id) => (int) $id)->values();
        $fields = CustomField::with('options')->whereIn('id', $fieldIds)->get()->keyBy('id');
        $values = CustomFieldValue::query()
            ->where('fieldable_type', $modelClass)
            ->whereIn('fieldable_id', $ids)
            ->whereIn('custom_field_id', $fieldIds)
            ->get()
            ->keyBy(fn ($value) => $value->fieldable_id.':'.$value->custom_field_id);

        foreach ($models as $model) {
            foreach ($customColumns as $column) {
                $field = $fields->get((int) $column['custom_field_id']);
                $value = $values->get($model->id.':'.$column['custom_field_id']);
                $model->setAttribute($column['key'], $this->displayValue($field, $value));
            }
        }
    }

    private function displayValue(?CustomField $field, ?CustomFieldValue $value): string
    {
        if (! $field || ! $value) return '';

        return match ($field->field_type) {
            CustomField::TYPE_DATE => $value->value_date?->format('Y-m-d') ?? '',
            CustomField::TYPE_RADIO => $field->options->firstWhere('value', $value->value_text)?->label ?? (string) $value->value_text,
            CustomField::TYPE_CHECKBOX => $field->options
                ->whereIn('value', collect($value->value_json ?? []))
                ->pluck('label')->implode(', '),
            default => (string) ($value->value_text ?? ''),
        };
    }

    private function columnKey(int $fieldId): string
    {
        return "custom_field_{$fieldId}";
    }
}
