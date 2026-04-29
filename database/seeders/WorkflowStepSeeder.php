<?php

namespace Database\Seeders;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

class WorkflowStepSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Create or update the default workflow
        $workflow = Workflow::updateOrCreate(
            ['code' => 'default_candidate_workflow'],
            [
                'name' => 'Default Candidate Workflow',
                'description' => 'Default workflow for candidate processing.',
                'is_active' => true,
                'is_primary' => true,
            ]
        );

        $steps = [
            [
                'code' => 'resume_review',
                'name' => 'Resume Review',
                'step_order' => 1,
                'default_status' => null,
                'allows_status' => false,
                'allows_requested_at' => false,
                'allows_scheduled_at' => false,
                'allows_completed_at' => true,
                'allows_notes' => true,
                'allows_comments' => false,
                'is_active' => true,
            ],
            [
                'code' => 'interview',
                'name' => 'Interview',
                'step_order' => 2,
                'default_status' => 'requested',
                'allows_status' => true,
                'allows_requested_at' => true,
                'allows_scheduled_at' => true,
                'allows_completed_at' => true,
                'allows_notes' => true,
                'allows_comments' => false,
                'is_active' => true,
            ],
            [
                'code' => 'tech_screen',
                'name' => 'Tech Screen',
                'step_order' => 3,
                'default_status' => 'requested',
                'allows_status' => true,
                'allows_requested_at' => true,
                'allows_scheduled_at' => true,
                'allows_completed_at' => true,
                'allows_notes' => true,
                'allows_comments' => false,
                'is_active' => true,
            ],
            [
                'code' => 'offer_sent',
                'name' => 'Offer Sent',
                'step_order' => 4,
                'default_status' => null,
                'allows_status' => false,
                'allows_requested_at' => false,
                'allows_scheduled_at' => false,
                'allows_completed_at' => true,
                'allows_notes' => false,
                'allows_comments' => true,
                'is_active' => true,
            ],
            [
                'code' => 'offer_signed',
                'name' => 'Offer Signed',
                'step_order' => 5,
                'default_status' => null,
                'allows_status' => false,
                'allows_requested_at' => false,
                'allows_scheduled_at' => false,
                'allows_completed_at' => true,
                'allows_notes' => false,
                'allows_comments' => true,
                'is_active' => true,
            ],
            [
                'code' => 'subcontract_signed',
                'name' => 'Subcontract Signed',
                'step_order' => 6,
                'default_status' => null,
                'allows_status' => false,
                'allows_requested_at' => false,
                'allows_scheduled_at' => false,
                'allows_completed_at' => true,
                'allows_notes' => false,
                'allows_comments' => true,
                'is_active' => true,
            ],
            [
                'code' => 'crossover',
                'name' => 'Crossover',
                'step_order' => 7,
                'default_status' => 'submitted',
                'allows_status' => true,
                'allows_requested_at' => true,
                'allows_scheduled_at' => false,
                'allows_completed_at' => true,
                'allows_notes' => false,
                'allows_comments' => false,
                'is_active' => true,
            ],
            [
                'code' => 'security_scrub',
                'name' => 'Security Scrub',
                'step_order' => 8,
                'default_status' => null,
                'allows_status' => false,
                'allows_requested_at' => true,
                'allows_scheduled_at' => false,
                'allows_completed_at' => true,
                'allows_notes' => false,
                'allows_comments' => false,
                'is_active' => true,
            ],
        ];

        foreach ($steps as $step) {
            WorkflowStep::updateOrCreate(
                [
                    'workflow_id' => $workflow->id,
                    'code' => $step['code'],
                ],
                array_merge($step, [
                    'workflow_id' => $workflow->id,
                ])
            );
        }
    }
}