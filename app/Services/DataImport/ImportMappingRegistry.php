<?php

namespace App\Services\DataImport;

use App\Models\CustomField;
use App\Models\Workflow;
use App\Models\WorkflowStep;

class ImportMappingRegistry
{
    /**
     * Build the destinations available to the mapping UI.
     *
     * Static application fields are intentionally curated instead of exposing
     * model fillable/database columns. Workflow and custom-field destinations
     * are generated from the installation's current configuration.
     */
    public function groups(?Workflow $workflow = null): array
    {
        $groups = [
            $this->group('ignore', 'Import Control', [
                $this->item('ignore', 'Do Not Import', 'ignore', aliases: ['ignore', 'do not import', 'skip']),
            ]),
            $this->group('position', 'Position', $this->positionItems()),
            $this->group('person', 'Person', $this->personItems()),
            $this->group('candidate', 'Candidate', $this->candidateItems()),
            $this->group('assignment', 'Position Assignment', $this->assignmentItems()),
        ];

        $customFields = CustomField::query()
            ->where('is_active', true)
            ->with('activeOptions')
            ->orderBy('entity_type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $positionCustom = $customFields
            ->where('entity_type', CustomField::ENTITY_POSITION)
            ->map(fn (CustomField $field) => $this->customFieldItem($field))
            ->values()->all();

        $personCustom = $customFields
            ->where('entity_type', CustomField::ENTITY_PERSON)
            ->map(fn (CustomField $field) => $this->customFieldItem($field))
            ->values()->all();

        if ($positionCustom !== []) {
            $groups[] = $this->group('position_custom', 'Position Custom Fields', $positionCustom);
        }

        if ($personCustom !== []) {
            $groups[] = $this->group('person_custom', 'Person Custom Fields', $personCustom);
        }

        if ($workflow) {
            $workflowItems = $this->workflowItems($workflow);
            if ($workflowItems !== []) {
                $groups[] = $this->group('workflow', 'Candidate Workflow — '.$workflow->name, $workflowItems);
            }
        }

        return $groups;
    }

    public function flat(?Workflow $workflow = null): array
    {
        return collect($this->groups($workflow))
            ->flatMap(fn (array $group) => $group['items'])
            ->keyBy('key')
            ->all();
    }

    public function has(string $key, ?Workflow $workflow = null): bool
    {
        return array_key_exists($key, $this->flat($workflow));
    }

    private function positionItems(): array
    {
        return [
            $this->item('position.position_code', 'Position Code', 'text', ['pid', 'position id', 'position code']),
            $this->item('position.staffing_state', 'Staffing State', 'lookup', ['status', 'staffing status', 'position status']),
            $this->item('position.job_title', 'Job Title', 'lookup', ['job title']),
            $this->item('position.level', 'Level', 'integer', ['level']),
            $this->item('position.team_name', 'Team Name', 'text', ['team', 'team name']),
            $this->item('position.project_manager', 'Project Manager', 'lookup', ['project manager', 'pm']),
            $this->item('position.labor_category', 'Labor Category', 'text', ['labor category', 'labor cat']),
            $this->item('position.certifications_required', 'Certifications Required', 'text'),
            $this->item('position.training_required', 'Training Required', 'text'),
            $this->item('position.experience', 'Experience', 'text'),
            $this->item('position.is_essential', 'Essential Position', 'boolean'),
            $this->item('position.travel_required', 'Travel Required', 'boolean'),
            $this->item('position.high_risk_role', 'High Risk Role', 'boolean'),
            $this->item('position.location', 'Location', 'text', ['location']),
            $this->item('position.building', 'Building', 'text', ['building']),
            $this->item('position.mission_description', 'Mission Description', 'text'),
            $this->item('position.component', 'Component', 'text', ['component']),
            $this->item('position.position_organization', 'Position Organization', 'lookup'),
            $this->item('position.sponsoring_organization', 'Sponsoring Organization', 'lookup'),
            $this->item('position.funding_organization', 'Funding Organization', 'lookup'),
            $this->item('position.funding_info', 'Funding Information', 'text'),
            $this->item('position.request_to_close', 'Request To Close', 'boolean'),
            $this->item('position.scheduled_to_close', 'Scheduled To Close', 'date'),
            $this->item('position.close_date', 'Close Date', 'date'),
            $this->item('position.close_reason', 'Close Reason', 'text'),
            $this->item('position.project_team_name', 'Project Team', 'text', ['project team', 'project team name']),
            $this->item('position.customer_lead_name', 'Customer Lead', 'text', ['customer lead']),
            $this->item('position.customer_created_at', 'Customer Created Date', 'date'),
            $this->item('position.notes', 'Position Notes', 'text', ['position notes']),
        ];
    }

    private function personItems(): array
    {
        return [
            $this->item('person.person_code', 'Person Code', 'text', ['employee id', 'employee number', 'person code']),
            $this->item('person.first_name', 'First Name', 'text', ['first name']),
            $this->item('person.alternate_first_name', 'Alternate First Name', 'text', ['alt first name', 'alternate first name']),
            $this->item('person.preferred_name', 'Preferred Name', 'text', ['preferred name']),
            $this->item('person.last_name', 'Last Name', 'text', ['last name']),
            $this->item('person.alternate_last_name', 'Alternate Last Name', 'text', ['alt last name', 'atl last name', 'alternate last name']),
            $this->item('person.company_name', 'Company', 'text', ['employer', 'company', 'company name']),
            $this->item('person.email', 'Email', 'email', ['email', 'email address']),
            $this->item('person.employment_status', 'Employment Status', 'lookup', ['employment status']),
            $this->item('person.notes', 'Person Notes', 'text', ['person notes']),
        ];
    }

    private function candidateItems(): array
    {
        return [
            $this->item('candidate.candidate_code', 'Candidate Code', 'text', ['candidate code']),
            $this->item('candidate.status', 'Candidate Status', 'lookup', ['hire status', 'candidate status']),
            $this->item('candidate.candidate_fbr', 'Candidate FBR', 'decimal', ['candidate fbr', 'fbr']),
            $this->item('candidate.submitted_at', 'Submitted Date', 'datetime', ['candidate submitted', 'submitted date']),
            $this->item('candidate.scheduled_start_date', 'Scheduled Start Date', 'date', ['start date', 'scheduled start date']),
        ];
    }

    private function assignmentItems(): array
    {
        return [
            $this->item('assignment.start_date', 'Start Date', 'date', ['assignment start date']),
            $this->item('assignment.end_date', 'End / Departure Date', 'date', ['departure date', 'end date', 'assignment end date']),
            $this->item('assignment.assignment_status', 'Assignment Status', 'lookup', ['assignment status']),
            $this->item('assignment.assignment_type', 'Assignment Type', 'lookup', ['assignment type']),
            $this->item('assignment.notes', 'Assignment Notes', 'text', ['assignment notes']),
        ];
    }

    private function workflowItems(Workflow $workflow): array
    {
        $workflow->loadMissing([
            'steps' => fn ($query) => $query->where('is_active', true)->orderBy('step_order'),
            'steps.statuses' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
        ]);

        return $workflow->steps
            ->where('is_active', true)
            ->flatMap(function (WorkflowStep $step) use ($workflow) {
                $properties = [];
                $dateProperties = [];

                if ($step->allows_requested_at) {
                    $dateProperties[] = 'requested_at';
                    $properties[] = $this->workflowItem($workflow, $step, 'requested_at', 'Requested Date', 'date', ['requested', 'request date', 'submitted', 'submitted date']);
                }
                if ($step->allows_scheduled_at) {
                    $dateProperties[] = 'scheduled_at';
                    $properties[] = $this->workflowItem($workflow, $step, 'scheduled_at', 'Scheduled Date', 'date', ['scheduled', 'scheduled date']);
                }
                if ($step->allows_completed_at) {
                    $dateProperties[] = 'completed_at';
                    $properties[] = $this->workflowItem($workflow, $step, 'completed_at', 'Completed Date', 'date', ['completed', 'completed date', 'approved', 'approved date', 'accepted', 'accepted date']);
                }
                if ($step->allows_status) {
                    $properties[] = $this->workflowItem(
                        $workflow,
                        $step,
                        'status',
                        'Status',
                        'lookup',
                        ['status'],
                        $step->statuses->map(fn ($status) => ['value' => $status->status_code, 'label' => $status->status_label])->values()->all(),
                    );
                }
                if ($step->allows_notes) {
                    $properties[] = $this->workflowItem($workflow, $step, 'notes', 'Notes', 'text', ['notes']);
                }
                if ($step->allows_comments) {
                    $properties[] = $this->workflowItem($workflow, $step, 'comments', 'Comments', 'text', ['comments']);
                }

                // If a step exposes exactly one date property, its plain name is a safe
                // suggestion for that date (for example, "Resume Accepted").
                if (count($dateProperties) === 1) {
                    foreach ($properties as &$property) {
                        if (($property['meta']['property'] ?? null) === $dateProperties[0]) {
                            $property['aliases'][] = $step->name;
                        }
                    }
                    unset($property);
                }

                return $properties;
            })
            ->values()->all();
    }

    private function customFieldItem(CustomField $field): array
    {
        $key = "custom.{$field->entity_type}.{$field->key}";

        return $this->item(
            $key,
            $field->name,
            $field->field_type,
            [$field->name, $field->key],
            [
                'entity_type' => $field->entity_type,
                'custom_field_key' => $field->key,
                'required' => (bool) $field->is_required,
                'sensitive' => (bool) $field->is_sensitive,
                'options' => $field->activeOptions->map(fn ($option) => [
                    'value' => $option->value,
                    'label' => $option->label,
                ])->values()->all(),
            ],
        );
    }

    private function workflowItem(
        Workflow $workflow,
        WorkflowStep $step,
        string $property,
        string $propertyLabel,
        string $type,
        array $propertyAliases,
        array $valueOptions = [],
    ): array {
        $aliases = collect($propertyAliases)
            ->map(fn (string $alias) => $step->name.' '.$alias)
            ->all();

        return $this->item(
            "workflow.{$workflow->code}.{$step->code}.{$property}",
            $step->name.' — '.$propertyLabel,
            $type,
            $aliases,
            [
                'workflow_code' => $workflow->code,
                'step_code' => $step->code,
                'step_name' => $step->name,
                'property' => $property,
                'value_options' => $valueOptions,
            ],
        );
    }

    private function group(string $key, string $label, array $items): array
    {
        return ['key' => $key, 'label' => $label, 'items' => array_values($items)];
    }

    private function item(string $key, string $label, string $type, array $aliases = [], array $meta = []): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'aliases' => array_values(array_unique([$label, ...$aliases])),
            'meta' => $meta,
        ];
    }
}
