<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Support\ListDefinitions\OrganizationDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $definition = OrganizationDefinition::get();

        $search = $request->input('search');
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = collect($definition['columns'])
            ->where('sortable', true)
            ->pluck('key')
            ->toArray();

        if (! in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $query = DB::table('organizations')
            ->leftJoin('organizations as parents', 'organizations.parent_id', '=', 'parents.id')
            ->select([
                'organizations.id',
                'organizations.parent_id',
                'organizations.name',
                'organizations.status',
                'organizations.notes',
                'organizations.created_at',
                'organizations.updated_at',
                'parents.name as parent_name',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('organizations.name', 'like', "%{$search}%")
                    ->orWhere('parents.name', 'like', "%{$search}%")
                    ->orWhere('organizations.notes', 'like', "%{$search}%");
            });
        }

        $sortColumn = collect($definition['columns'])->firstWhere('key', $sort);

        $sortField = $sortColumn['db_field'] ?? 'organizations.name';

        $query->orderBy($sortField, $direction);

        $organizations = $query
            ->paginate(10)
            ->withQueryString();

        $organizations->getCollection()->transform(function ($org) {
            $organizationModel = Organization::with('parent')->find($org->id);
            $org->full_path = $organizationModel?->full_path ?? $org->name;

            return $org;
        });

        return Inertia::render('Admin/Organizations/Index', [
            'organizations' => $organizations,
            'columns' => $definition['columns'],

            'visibleColumns' => session(
                'organizations.visible_columns',
                collect($definition['columns'])
                    ->where('default_visible', true)
                    ->pluck('key')
                    ->values()
                    ->toArray()
            ),

            'columnOrder' => session(
                'organizations.column_order',
                collect($definition['columns'])
                    ->sortBy('default_order')
                    ->pluck('key')
                    ->values()
                    ->toArray()
            ),

            'filters' => [
                'search' => $search,
            ],

            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        $parents = Organization::orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Organizations/Create', [
            'parents' => $parents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:organizations,id',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $validated['parent_id'] = $validated['parent_id'] ?: 1;

        Organization::create($validated);

        $organization = Organization::create($validated);

        $organization->rebuildHierarchyFields();

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', 'Organization created.');
    }

    public function edit(Organization $organization)
    {
        $parents = Organization::where('id', '!=', $organization->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Organizations/Edit', [
            'organization' => $organization,
            'parents' => $parents,
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:organizations,id',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($organization->id === 1) {
            $validated['parent_id'] = null;
        } else {
            $validated['parent_id'] = $validated['parent_id'] ?: 1;
        }

        if (
            $organization->id !== 1 &&
            ! empty($validated['parent_id']) &&
            $organization->wouldCreateCircularParent((int) $validated['parent_id'])
        ) {
            return back()
                ->withErrors(['parent_id' => 'This parent would create a circular organization relationship.'])
                ->withInput();
        }

        $organization->update($validated);

        $organization->update($validated);

        $organization->rebuildHierarchyFields();
        $organization->rebuildDescendantHierarchyFields();

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', 'Organization updated.');
    }

    public function destroy(Organization $organization)
    {
        if ($organization->id === 1) {
            return back()->with('error', 'Root organization cannot be deleted.');
        }

        if ($organization->children()->exists()) {
            return back()->with('error', 'This organization has child organizations and cannot be deleted.');
        }

        $organization->delete();

        return back()->with('success', 'Organization deleted.');
    }

    public function savePreferences(Request $request)
    {
        $validated = $request->validate([
            'visible_columns' => 'array',
            'visible_columns.*' => 'string',
            'column_order' => 'array',
            'column_order.*' => 'string',
        ]);

        session([
            'organizations.visible_columns' => $validated['visible_columns'] ?? [],
            'organizations.column_order' => $validated['column_order'] ?? [],
        ]);

        return back()->with('success', 'Organization column preferences saved.');
    }

    public function resetPreferences()
    {
        session()->forget([
            'organizations.visible_columns',
            'organizations.column_order',
        ]);

        return back()->with('success', 'Organization column preferences reset.');
    }

    public function exportCsv(Request $request)
    {
        $definition = OrganizationDefinition::get();

        $search = $request->input('search');

        $query = DB::table('organizations')
            ->leftJoin('organizations as parents', 'organizations.parent_id', '=', 'parents.id')
            ->select([
                'organizations.id',
                'organizations.parent_id',
                'organizations.name',
                'organizations.status',
                'organizations.notes',
                'organizations.created_at',
                'organizations.updated_at',
                'parents.name as parent_name',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('organizations.name', 'like', "%{$search}%")
                    ->orWhere('parents.name', 'like', "%{$search}%")
                    ->orWhere('organizations.notes', 'like', "%{$search}%");
            });
        }

        $rows = $query
            ->orderBy('organizations.name')
            ->get();

        $defaultVisibleColumns = collect($definition['columns'])
            ->where('default_visible', true)
            ->pluck('key')
            ->toArray();

        $defaultColumnOrder = collect($definition['columns'])
            ->sortBy('default_order')
            ->pluck('key')
            ->toArray();

        $visibleColumns = $request->input('visible_columns', $defaultVisibleColumns);
        $columnOrder = $request->input('column_order', $defaultColumnOrder);

        $columns = collect($columnOrder)
            ->filter(fn ($key) => in_array($key, $visibleColumns))
            ->values();

        $columnLabels = collect($definition['columns'])
            ->pluck('label', 'key');

        return response()->streamDownload(function () use ($rows, $columns, $columnLabels) {
            $handle = fopen('php://output', 'w');

            fputcsv(
                $handle,
                $columns
                    ->map(fn ($key) => $columnLabels[$key] ?? $key)
                    ->toArray()
            );

            foreach ($rows as $row) {
                $csvRow = [];

                foreach ($columns as $key) {
                    if ($key === 'full_path') {
                        $organizationModel = Organization::with('parent')->find($row->id);
                        $csvRow[] = $organizationModel?->full_path ?? $row->name;
                    } else {
                        $csvRow[] = $row->$key ?? '';
                    }
                }

                fputcsv($handle, $csvRow);
            }

            fclose($handle);
        }, 'organizations.csv');
    }
}