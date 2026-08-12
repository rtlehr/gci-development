<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ListEngine
{
    public function __construct(private readonly CustomFieldListService $customFieldLists)
    {
    }

    public function run(
        Request $request,
        array $definition,
        int $userId,
        Builder $query,
        ?callable $filterCallback = null,
        int $perPage = 10
    ): array {
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', $definition['default_sort']);
        $direction = $request->input('direction', $definition['default_direction']);
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : $definition['default_direction'];

        $preferences = ListPreferenceService::getUserPreferences($userId, $definition['list_key']);
        $merged = ListPreferenceService::merge($definition, $preferences);

        $visibleColumns = collect($definition['columns'])
            ->whereIn('key', $merged['visible'])
            ->values();

        if ($search !== '') {
            if (! empty($definition['entity_model']) && ! empty($definition['entity_table'])) {
                $this->customFieldLists->applySearch(
                    $query,
                    $visibleColumns,
                    $search,
                    $definition['entity_model'],
                    $definition['entity_table']
                );
            } else {
                $searchableFields = $visibleColumns->where('searchable', true)->pluck('db_field')->filter();
                if ($searchableFields->isNotEmpty()) {
                    $query->where(function ($q) use ($searchableFields, $search) {
                        foreach ($searchableFields as $field) {
                            $q->orWhere($field, 'like', "%{$search}%");
                        }
                    });
                }
            }
        }

        if (! empty($definition['custom_field_entity_type']) && ! empty($definition['entity_model']) && ! empty($definition['entity_table'])) {
            $this->customFieldLists->applyFilters(
                $query,
                $request,
                $definition['custom_field_entity_type'],
                $definition['entity_model'],
                $definition['entity_table']
            );
        }

        if ($filterCallback) {
            $filterCallback($query, $request, $definition, $merged);
        }

        $sortableColumn = collect($definition['columns'])->firstWhere('key', $sort);

        if ($sortableColumn && in_array($sort, $merged['visible'], true) && ($sortableColumn['sortable'] ?? false)) {
            if (! empty($sortableColumn['custom_field_id']) && ! empty($definition['entity_model']) && ! empty($definition['entity_table'])) {
                $this->customFieldLists->applySort($query, $sortableColumn, $direction, $definition['entity_model'], $definition['entity_table']);
            } elseif (! empty($sortableColumn['db_field'])) {
                $query->orderBy($sortableColumn['db_field'], $direction);
            }
        } else {
            $defaultSortCol = collect($definition['columns'])->firstWhere('key', $definition['default_sort']);
            if ($defaultSortCol && ! empty($defaultSortCol['db_field'])) {
                $query->orderBy($defaultSortCol['db_field'], $definition['default_direction']);
                $sort = $definition['default_sort'];
                $direction = $definition['default_direction'];
            }
        }

        $rows = $query->paginate($perPage)->withQueryString();
        $this->customFieldLists->hydrateRows($rows, $definition);

        return [
            'rows' => $rows,
            'columns' => $definition['columns'],
            'visibleColumns' => $merged['visible'],
            'columnOrder' => $merged['order'],
            'filters' => [
                'search' => $search,
                'custom_filters' => $request->input('custom_filters', []),
            ],
            'sort' => $sort,
            'direction' => $direction,
        ];
    }
}
