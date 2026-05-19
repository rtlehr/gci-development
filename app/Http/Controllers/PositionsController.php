<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

use App\Models\Position;
use App\Models\PositionActivity;
use App\Models\UserListPreference;
use App\Models\Organization;

use App\Services\ListEngine;
use App\Services\UserResolver;
use App\Services\ListExportService;

use App\Support\ListDefinitions\PositionsDefinition;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\StreamedResponse;

class PositionsController extends Controller
{
    public function index(
        Request $request,
        UserResolver $userResolver,
        ListEngine $listEngine
    ) {

        $definition = PositionsDefinition::get();

        $userId = $userResolver->resolveUserId();

        $list = $listEngine->run(
            request: $request,
            definition: $definition,
            userId: $userId,
            query: Position::query(),

            filterCallback: function ($query, $request) {

                $status = $request->input('status', '');

                if ($status) {
                    $query->where('status', $status);
                }
            }
        );

        $list['filters']['status'] = $request->input('status', '');

        return inertia('Positions/Index', [
            'positions' => $list['rows'],
            'columns' => $list['columns'],
            'visibleColumns' => $list['visibleColumns'],
            'columnOrder' => $list['columnOrder'],
            'filters' => $list['filters'],
            'sort' => $list['sort'],
            'direction' => $list['direction'],
        ]);
    }

    public function create()
    {
        $organizations = Organization::orderBy('full_path')
            ->get(['id', 'name', 'full_path', 'depth']);

        return Inertia::render('Positions/Create', [
            'organizations' => $organizations,
        ]);
    }

    public function store(
        Request $request,
        UserResolver $userResolver
    ) {

        $validated = $request->validate([

            'position_code' => ['nullable', 'string', 'max:255'],

            'status' => [
                'required',
                'in:Open,In Process,Closed',
            ],

            'job_title' => [
                'required',
                'string',
                'max:255',
            ],

            'experience_level' => [
                'nullable',
                'in:Beginner,Novice,Experienced,Senior',
            ],

            'labor_category' => [
                'nullable',
                'string',
                'max:255',
            ],

            'certifications_required' => [
                'nullable',
                'string',
            ],

            'training_required' => [
                'nullable',
                'string',
            ],

            'experience' => [
                'nullable',
                'string',
            ],

            'is_essential' => [
                'boolean',
            ],

            'travel_required' => [
                'boolean',
            ],

            'high_risk_role' => [
                'boolean',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'building' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mission_description' => [
                'nullable',
                'string',
            ],

            'component' => [
                'nullable',
                'string',
                'max:255',
            ],

            'position_organization_id' => [
                'nullable',
                'exists:organizations,id',
            ],

            'sponsoring_organization_id' => [
                'nullable',
                'exists:organizations,id',
            ],

            'funding_organization_id' => [
                'nullable',
                'exists:organizations,id',
            ],

            'funding_info' => [
                'nullable',
                'string',
            ],

            'request_to_close' => [
                'boolean',
            ],

            'scheduled_to_close' => [
                'nullable',
                'date',
            ],

            'close_date' => [
                'nullable',
                'date',
                'required_if:status,Closed',
            ],

            'close_reason' => [
                'nullable',
                'string',
                'required_if:status,Closed',
            ],

            'project_team_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'customer_lead_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'customer_created_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        $position = Position::create($validated);

        PositionActivity::create([
            'position_id' => $position->id,
            'user_id' => $userResolver->resolveUserId(),
            'action' => 'created',
            'description' => 'Position created.',
        ]);

        return redirect()
            ->route('positions.show', $position->id)
            ->with('success', 'Position created successfully.');
    }

    public function show($id)
    {
        $position = Position::with([
            'currentAssignment.person',
            'assignments.person',
            'activities.user',

            'positionOrganization',
            'sponsoringOrganization',
            'fundingOrganization',
        ])->findOrFail($id);

        return inertia('Positions/Show', [
            'position' => $position,
        ]);
    }

    public function edit($id)
    {
        $position = Position::findOrFail($id);

        $organizations = Organization::orderBy('full_path')
            ->get(['id', 'name', 'full_path', 'depth']);

        return inertia('Positions/Edit', [
            'position' => $position,
            'organizations' => $organizations,
        ]);
    }

    public function update(
        Request $request,
        UserResolver $userResolver,
        $id
    ) {

        $position = Position::findOrFail($id);

        $original = $position->getOriginal();

        $validated = $request->validate([

            'position_code' => ['nullable', 'string', 'max:255'],

            'status' => [
                'required',
                'in:Open,In Process,Closed',
            ],

            'job_title' => [
                'required',
                'string',
                'max:255',
            ],

            'experience_level' => [
                'nullable',
                'in:Beginner,Novice,Experienced,Senior',
            ],

            'labor_category' => [
                'nullable',
                'string',
                'max:255',
            ],

            'certifications_required' => [
                'nullable',
                'string',
            ],

            'training_required' => [
                'nullable',
                'string',
            ],

            'experience' => [
                'nullable',
                'string',
            ],

            'is_essential' => [
                'boolean',
            ],

            'travel_required' => [
                'boolean',
            ],

            'high_risk_role' => [
                'boolean',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'building' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mission_description' => [
                'nullable',
                'string',
            ],

            'component' => [
                'nullable',
                'string',
                'max:255',
            ],

            'position_organization_id' => [
                'nullable',
                'exists:organizations,id',
            ],

            'sponsoring_organization_id' => [
                'nullable',
                'exists:organizations,id',
            ],

            'funding_organization_id' => [
                'nullable',
                'exists:organizations,id',
            ],

            'funding_info' => [
                'nullable',
                'string',
            ],

            'request_to_close' => [
                'boolean',
            ],

            'scheduled_to_close' => [
                'nullable',
                'date',
            ],

            'close_date' => [
                'nullable',
                'date',
                'required_if:status,Closed',
            ],

            'close_reason' => [
                'nullable',
                'string',
                'required_if:status,Closed',
            ],

            'project_team_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'customer_lead_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'customer_created_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        $position->update($validated);

        /*
        |--------------------------------------------------------------------------
        | Ignore Fields
        |--------------------------------------------------------------------------
        |
        | Some fields are normalized/formatted differently between
        | Laravel and the frontend and would create noisy activity logs.
        |
        */

        $ignoredActivityFields = [
            'customer_created_at',
        ];

        foreach ($validated as $field => $newValue) {

            // Skip ignored fields.
            if (in_array($field, $ignoredActivityFields)) {
                continue;
            }

            $oldValue = $original[$field] ?? null;

            if ((string) $oldValue !== (string) $newValue) {

                PositionActivity::create([
                    'position_id' => $position->id,
                    'user_id' => $userResolver->resolveUserId(),

                    'action' => 'updated',

                    'field_name' => $field,

                    'old_value' => is_array($oldValue)
                        ? json_encode($oldValue)
                        : $oldValue,

                    'new_value' => is_array($newValue)
                        ? json_encode($newValue)
                        : $newValue,

                    'description' => "Updated {$field}.",
                ]);
            }
        }

        return redirect()
            ->route('positions.show', $position->id)
            ->with('success', 'Position updated successfully.');
    }

    public function destroy(
        UserResolver $userResolver,
        $id
    ) {

        $position = Position::with('assignments')
            ->findOrFail($id);

        if ($position->assignments()->exists()) {

            return redirect()
                ->route('positions.index')
                ->with(
                    'error',
                    'This position cannot be deleted because it has assignments.'
                );
        }

        PositionActivity::create([
            'position_id' => $position->id,
            'user_id' => $userResolver->resolveUserId(),
            'action' => 'deleted',
            'description' => 'Position deleted.',
        ]);

        $position->delete();

        return redirect()
            ->route('positions.index')
            ->with('success', 'Position deleted successfully.');
    }

    public function savePreferences(
        Request $request,
        UserResolver $userResolver
    ) {

        $definition = PositionsDefinition::get();

        $validKeys = collect($definition['columns'])
            ->pluck('key')
            ->toArray();

        $validated = $request->validate([
            'visible_columns' => ['required', 'array'],
            'visible_columns.*' => ['string'],

            'column_order' => ['required', 'array'],
            'column_order.*' => ['string'],
        ]);

        $visibleColumns = collect($validated['visible_columns'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $columnOrder = collect($validated['column_order'])
            ->filter(fn ($key) => in_array($key, $validKeys))
            ->values()
            ->toArray();

        $userId = $userResolver->resolveUserId();

        UserListPreference::updateOrCreate(
            [
                'user_id' => $userId,
                'list_key' => $definition['list_key'],
            ],
            [
                'visible_columns' => $visibleColumns,
                'column_order' => $columnOrder,
            ]
        );

        return redirect()
            ->route('positions.index')
            ->with('success', 'Column preferences saved.');
    }

    public function resetPreferences(
        UserResolver $userResolver
    ) {

        $definition = PositionsDefinition::get();

        $userId = $userResolver->resolveUserId();

        UserListPreference::where('user_id', $userId)
            ->where('list_key', $definition['list_key'])
            ->delete();

        return redirect()
            ->route('positions.index')
            ->with(
                'success',
                'Column preferences reset to defaults.'
            );
    }

    public function exportCsv(
        Request $request,
        ListExportService $listExportService
    ): StreamedResponse {

        return $listExportService->exportCsv(
            request: $request,

            definition: PositionsDefinition::get(),

            query: Position::query(),

            filenamePrefix: 'positions-export',

            filterCallback: function ($query, $request) {

                $status = $request->input('status', '');

                if ($status) {
                    $query->where('status', $status);
                }
            }
        );
    }
}