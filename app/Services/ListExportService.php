<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListExportService
{
    public function __construct(private readonly CustomFieldListService $customFieldLists)
    {
    }

    public function exportCsv(
        Request $request,
        array $definition,
        Builder $query,
        string $filenamePrefix,
        ?callable $filterCallback = null
    ): StreamedResponse {
        $allColumns = collect($definition['columns']);
        $validKeys = $allColumns->pluck('key')->toArray();
        $visibleColumns = $this->sanitizeColumnArray($request->input('visible_columns', []), $validKeys);
        $columnOrder = $this->sanitizeColumnArray($request->input('column_order', []), $validKeys);
        $search = trim((string) $request->input('search', ''));

        if (empty($visibleColumns)) {
            $visibleColumns = $allColumns->where('default_visible', true)->pluck('key')->values()->toArray();
        }
        if (empty($columnOrder)) {
            $columnOrder = $allColumns->sortBy('default_order')->pluck('key')->values()->toArray();
        }

        $activeColumnKeys = collect($columnOrder)->filter(fn ($key) => in_array($key, $visibleColumns, true))->values()->toArray();
        $activeColumns = $allColumns
            ->filter(fn ($col) => in_array($col['key'], $activeColumnKeys, true) && ($col['exportable'] ?? true))
            ->sortBy(fn ($col) => array_search($col['key'], $activeColumnKeys, true))->values();

        if ($search !== '') {
            if (! empty($definition['entity_model']) && ! empty($definition['entity_table'])) {
                $this->customFieldLists->applySearch($query, $activeColumns, $search, $definition['entity_model'], $definition['entity_table']);
            } else {
                $searchableFields = $activeColumns->where('searchable', true)->pluck('db_field')->filter()->values();
                if ($searchableFields->isNotEmpty()) {
                    $query->where(function ($q) use ($searchableFields, $search) {
                        foreach ($searchableFields as $field) $q->orWhere($field, 'like', '%'.$search.'%');
                    });
                }
            }
        }

        if (! empty($definition['custom_field_entity_type']) && ! empty($definition['entity_model']) && ! empty($definition['entity_table'])) {
            $this->customFieldLists->applyFilters($query, $request, $definition['custom_field_entity_type'], $definition['entity_model'], $definition['entity_table']);
        }

        if ($filterCallback) $filterCallback($query, $request, $definition, $activeColumns);

        $rows = $query->get();
        $this->customFieldLists->hydrateRows($rows, $definition);
        $filename = $filenamePrefix.'-'.now()->format('Y-m-d_H-i-s').'.csv';

        return Response::streamDownload(function () use ($rows, $activeColumns, $definition) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $activeColumns->pluck('label')->toArray());
            foreach ($rows as $rowModel) {
                $row = [];
                foreach ($activeColumns as $column) $row[] = $this->resolveExportValue($rowModel, $column, $definition);
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, no-cache']);
    }

    protected function sanitizeColumnArray(mixed $columns, array $validKeys): array
    {
        if (!is_array($columns)) return [];
        return collect($columns)->filter(fn ($key) => is_string($key) && in_array($key, $validKeys, true))->values()->toArray();
    }

    protected function resolveExportValue($rowModel, array $column, array $definition): string
    {
        $listKey = $definition['list_key'] ?? '';
        $key = $column['key'] ?? '';
        if ($listKey === 'user_permissions') return $this->resolveUserPermissionsExportValue($rowModel, $key);
        $value = data_get($rowModel, $key, '');
        if ($value instanceof Collection) $value = $value->toArray();
        if (is_array($value)) return collect($value)->implode('; ');
        if (is_object($value)) return json_encode($value) ?: '';
        return $value === null ? '' : (string) $value;
    }

    protected function resolveUserPermissionsExportValue($user, string $key): string
    {
        return match ($key) {
            'person_code' => $user->person->person_code ?? '',
            'full_name' => trim((($user->person->first_name ?? '').' '.($user->person->last_name ?? ''))),
            'roles' => $user->roles && $user->roles->count() ? $user->roles->map(fn ($role) => $role->label ?: $role->name)->implode('; ') : '',
            'permissions' => $user->permissions && $user->permissions->count() ? $user->permissions->map(fn ($permission) => $permission->label ?: $permission->name)->implode('; ') : '',
            'email' => $user->email ?? '',
            default => (string) data_get($user, $key, ''),
        };
    }
}
