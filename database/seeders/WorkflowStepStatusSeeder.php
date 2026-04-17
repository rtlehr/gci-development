<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowStepStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $interviewId = DB::table('workflow_steps')->where('code', 'interview')->value('id');
        $techScreenId = DB::table('workflow_steps')->where('code', 'tech_screen')->value('id');
        $crossoverId = DB::table('workflow_steps')->where('code', 'crossover')->value('id');

        if (!$interviewId || !$techScreenId || !$crossoverId) {
            throw new \Exception('One or more workflow steps were not found. Make sure WorkflowStepSeeder ran successfully first.');
        }

        DB::table('workflow_step_statuses')->insert([
            [
                'workflow_step_id' => $interviewId,
                'status_code' => 'requested',
                'status_label' => 'Requested',
                'sort_order' => 1,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workflow_step_id' => $interviewId,
                'status_code' => 'scheduled',
                'status_label' => 'Scheduled',
                'sort_order' => 2,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workflow_step_id' => $interviewId,
                'status_code' => 'completed',
                'status_label' => 'Completed',
                'sort_order' => 3,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workflow_step_id' => $interviewId,
                'status_code' => 'cancelled',
                'status_label' => 'Cancelled',
                'sort_order' => 4,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'workflow_step_id' => $techScreenId,
                'status_code' => 'requested',
                'status_label' => 'Requested',
                'sort_order' => 1,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workflow_step_id' => $techScreenId,
                'status_code' => 'scheduled',
                'status_label' => 'Scheduled',
                'sort_order' => 2,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workflow_step_id' => $techScreenId,
                'status_code' => 'completed',
                'status_label' => 'Completed',
                'sort_order' => 3,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workflow_step_id' => $techScreenId,
                'status_code' => 'cancelled',
                'status_label' => 'Cancelled',
                'sort_order' => 4,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'workflow_step_id' => $crossoverId,
                'status_code' => 'submitted',
                'status_label' => 'Submitted',
                'sort_order' => 1,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workflow_step_id' => $crossoverId,
                'status_code' => 'approved',
                'status_label' => 'Approved',
                'sort_order' => 2,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workflow_step_id' => $crossoverId,
                'status_code' => 'denied',
                'status_label' => 'Denied',
                'sort_order' => 3,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}