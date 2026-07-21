<?php

namespace Database\Seeders;

use App\Models\WorkflowStep;
use App\Models\WorkflowStepStatus;
use Illuminate\Database\Seeder;

class WorkflowStepStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'interview' => [
                ['requested', 'Requested', 1, true],
                ['scheduled', 'Scheduled', 2, false],
                ['completed', 'Completed', 3, false],
                ['cancelled', 'Cancelled', 4, false],
            ],
            'tech_screen' => [
                ['requested', 'Requested', 1, true],
                ['scheduled', 'Scheduled', 2, false],
                ['completed', 'Completed', 3, false],
                ['cancelled', 'Cancelled', 4, false],
            ],
            'crossover' => [
                ['submitted', 'Submitted', 1, true],
                ['approved', 'Approved', 2, false],
                ['denied', 'Denied', 3, false],
            ],
        ];

        foreach ($statuses as $stepCode => $stepStatuses) {
            $step = WorkflowStep::query()->where('code', $stepCode)->firstOrFail();

            foreach ($stepStatuses as [$code, $label, $sortOrder, $isDefault]) {
                WorkflowStepStatus::updateOrCreate(
                    ['workflow_step_id' => $step->id, 'status_code' => $code],
                    [
                        'status_label' => $label,
                        'sort_order' => $sortOrder,
                        'is_default' => $isDefault,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
