<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListExportService
{
    public function exportCsv(
        Request $request,
        array $definition,
        Builder $query,
        string $filenamePrefix,
        ?callable $filterCallback = null
    ): StreamedResponse {
        $visibleColumns = $request->input('visible_columns', []);
        $columnOrder = $request->input('column_order', []);
        $search = $request->input('search', '');

        $allColumns = collect($definition['columns']);
        $validKeys = $allColumns->pluck('key')->toArray();

        $visibleColumns = collect($visibleColumns)
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $columnOrder = collect($columnOrder)
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $activeColumnKeys = collect($columnOrder)
            ->filter(fn ($key) => in_array($key, $visibleColumns))
            ->values()
            ->toArray();

        $activeColumns = $allColumns
            ->whereIn('key', $activeColumnKeys)
            ->sortBy(function ($col) use ($activeColumnKeys) {
                return array_search($col['key'], $activeColumnKeys);
            })
            ->values();

        if ($search) {
            $searchableFields = $activeColumns
                ->where('searchable', true)
                ->pluck('db_field');

            $query->where(function ($q) use ($searchableFields, $search) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        if ($filterCallback) {
            $filterCallback($query, $request, $definition, $activeColumns);
        }

        $rows = $query->get();

        $filename = $filenamePrefix . '-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return Response::streamDownload(function () use ($rows, $activeColumns) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $activeColumns->pluck('label')->toArray());

            foreach ($rows as $rowModel) {
                $row = [];

                foreach ($activeColumns as $column) {
                    $key = $column['key'];
                    $row[] = $rowModel->{$key} ?? '';
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}