<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionsController extends Controller
{

    public function index(Request $request)
    {
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $search = $request->input('search');
        $status = $request->input('status');

        $allowedSorts = [
            'position_code',
            'job_title',
            'status',
            'labor_category',
            'project_team_name',
            'created_at'
        ];

        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        $positions = Position::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('position_code', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%")
                        ->orWhere('labor_category', 'like', "%{$search}%")
                        ->orWhere('project_team_name', 'like', "%{$search}%")
                        ->orWhere('organization_name', 'like', "%{$search}%")
                        ->orWhere('customer_lead_name', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return inertia('Positions/Index', [
            'positions' => $positions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        return inertia('Positions/Create');
    }

    public function edit($id)
    {
        $position = Position::findOrFail($id);

        return inertia('Positions/Edit', [
            'position' => $position,
        ]);
    }

    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $validated = $request->validate([
            'position_code' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:Open,In Process,Closed'],
            'labor_category' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'integer'],
            'project_team_name' => ['nullable', 'string', 'max:255'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'customer_lead_name' => ['nullable', 'string', 'max:255'],
            'customer_created_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date'],
            'closed_reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $position->update($validated);

        return redirect()->route('positions.index');
    }

    public function show($id)
    {
        $position = Position::with(['currentAssignment.person', 'assignments.person'])
            ->findOrFail($id);

        return inertia('Positions/Show', [
            'position' => $position,
        ]);
    }

    
}