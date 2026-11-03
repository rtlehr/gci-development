<?php

namespace Insite\Modules\Positions\Services;

use App\Contracts\Positions\PositionOperations;
use App\Models\Position;
use App\Services\CustomFieldService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Module-owned validation shared by the legacy and Portal Positions workflows.
 * The Portal workflow includes custom-field rules; legacy does not.
 */
final class PositionInputValidator
{
    public function validate(Request $request, ?Position $position = null, bool $includeCustomFields = false): array
    {
        $rules = [
            'position_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'position_code')->ignore($position?->id),
            ],

            'status' => [
                'required',
                'in:Open,In Process,Closed',
            ],

            'job_title_id' => [
                'required',
                'exists:job_titles,id',
            ],

            'level' => [
                'nullable',
                'integer',
                'between:1,5',
            ],

            'team_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'project_manager_user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
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
        ];

        if ($includeCustomFields) {
            $rules += app(CustomFieldService::class)->rules('position');
        }

        $validated = $request->validate($rules);

        app(PositionOperations::class)->assertEligibleProjectManager(
            $validated['project_manager_user_id'] ?? null
        );

        return $validated;
    }
}
