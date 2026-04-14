<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ListEngine
{
    public function run(
        Request $request,
        array $definition,
        int $userId,
        Builder $query,
        ?callable $filterCallback = null,
        int $perPage = 10
    ): array {
        $search = $request->input('search', '');
        $sort = $request->input('sort', $definition['default_sort']);
        $direction = $request->input('direction', $definition['default_direction']);

        $preferences = ListPreferenceService::getUserPreferences(
            $userId,
            $definition['list_key']
        );

        $merged = ListPreferenceService::merge($definition, $preferences);

        if ($search) {
            $searchableFields = collect($definition['columns'])
                ->whereIn('key', $merged['visible'])
                ->where('searchable', true)
                ->pluck('db_field');

            $query->where(function ($q) use ($searchableFields, $search) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        if ($filterCallback) {
            $filterCallback($query, $request, $definition, $merged);
        }

        $sortableColumn = collect($definition['columns'])
            ->firstWhere('key', $sort);

        if (
            $sortableColumn &&
            in_array($sort, $merged['visible']) &&
            ($sortableColumn['sortable'] ?? false)
        ) {
            $query->orderBy($sortableColumn['db_field'], $direction);
        } else {
            $defaultSortCol = collect($definition['columns'])
                ->firstWhere('key', $definition['default_sort']);

            if ($defaultSortCol) {
                $query->orderBy(
                    $defaultSortCol['db_field'],
                    $definition['default_direction']
                );

                $sort = $definition['default_sort'];
                $direction = $definition['default_direction'];
            }
        }

        $rows = $query->paginate($perPage)->withQueryString();

        return [
            'rows' => $rows,
            'columns' => $definition['columns'],
            'visibleColumns' => $merged['visible'],
            'columnOrder' => $merged['order'],
            'filters' => [
                'search' => $search,
            ],
            'sort' => $sort,
            'direction' => $direction,
        ];
    }
}