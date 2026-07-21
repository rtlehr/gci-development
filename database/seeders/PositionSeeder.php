<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use App\Models\Organization;
use App\Models\Position;
use App\Models\PositionActivity;
use App\Models\PositionCustomSkill;
use App\Models\PositionCustomTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $organization = Organization::query()->firstOrCreate(
                ['name' => 'Org Root'],
                ['parent_id' => null, 'status' => 'active', 'notes' => 'Default root organization.']
            );

            $organization->rebuildHierarchyFields();

            $jobTitles = JobTitle::query()->get()->keyBy('name');
            $projectManagers = User::query()
                ->whereHas('roles', fn ($query) => $query->where('roles.name', 'project_manager'))
                ->get()
                ->keyBy('email');

            $requiredTitles = [
                'Software Engineer', 'Cybersecurity Analyst', 'DevOps Engineer', 'Business Analyst',
                'Database Administrator', 'Network Engineer', 'Project Manager', 'Quality Assurance Analyst',
                'Data Analyst', 'Systems Administrator',
            ];

            if (collect($requiredTitles)->contains(fn (string $title) => ! $jobTitles->has($title))) {
                $this->command?->warn('PositionSeeder skipped because one or more required job titles are missing.');
                return;
            }

            // Remove only records created by earlier development position seeders.
            Position::query()
                ->where('position_code', 'TEST-001')
                ->orWhere('position_code', 'like', 'IRAD-%')
                ->delete();

            $positions = [
                $this->position('IRAD-SWE-001', 'Open', 'Software Engineer', 4, 'Washington, DC', 'Portal Engineering', 'Senior application engineer supporting secure portal modernization.', 'project.manager1@localhost', 54),
                $this->position('IRAD-CYB-002', 'Open', 'Cybersecurity Analyst', 3, 'Arlington, VA', 'Cyber Operations', 'Security analyst supporting vulnerability management and authorization activities.', 'project.manager2@localhost', 41, highRisk: true),
                $this->position('IRAD-DOP-003', 'In Process', 'DevOps Engineer', 4, 'Remote', 'Platform Engineering', 'DevOps engineer improving automated build, test, deployment, and monitoring workflows.', 'project.manager1@localhost', 38),
                $this->position('IRAD-BA-004', 'Open', 'Business Analyst', 3, 'Washington, DC', 'Customer Delivery', 'Business analyst supporting requirements discovery and process improvement.', 'project.manager2@localhost', 25),
                $this->position('IRAD-DBA-005', 'Closed', 'Database Administrator', 4, 'Winchester, VA', 'Data Services', 'Senior database administrator responsible for availability, tuning, and data protection.', 'project.manager1@localhost', 132, closeReason: 'Position filled after successful candidate selection and onboarding.'),
                $this->position('IRAD-NET-006', 'Closed', 'Network Engineer', 3, 'Arlington, VA', 'Infrastructure', 'Network engineer supporting secure enterprise connectivity and operations.', 'project.manager2@localhost', 96, closeReason: 'Customer cancelled the requirement after funding priorities changed.'),
                $this->position('IRAD-PM-007', 'Open', 'Project Manager', 4, 'Washington, DC', 'Program Management Office', 'Project manager coordinating schedule, risks, dependencies, and customer communications.', 'project.manager1@localhost', 18, travel: true),
                $this->position('IRAD-QA-008', 'In Process', 'Quality Assurance Analyst', 3, 'Remote', 'Quality Engineering', 'QA analyst developing test plans and validating functional and accessibility requirements.', 'project.manager2@localhost', 31),
                $this->position('IRAD-DATA-009', 'Open', 'Data Analyst', 3, 'Arlington, VA', 'Business Intelligence', 'Data analyst building operational reports, dashboards, and decision-support products.', null, 12),
                $this->position('IRAD-SYS-010', 'Open', 'Systems Administrator', 2, 'Winchester, VA', 'Infrastructure', 'Systems administrator supporting identity, patching, monitoring, and service availability.', null, 7),
            ];

            foreach ($positions as $positionData) {
                // These values are lookup helpers for the seeder only. They are not
                // columns on the positions table and must not be mass assigned.
                $jobTitleName = $positionData['job_title_name'];
                $projectManagerEmail = $positionData['project_manager_email'];

                unset(
                    $positionData['job_title_name'],
                    $positionData['project_manager_email'],
                );

                $position = Position::updateOrCreate(
                    ['position_code' => $positionData['position_code']],
                    array_merge($positionData, [
                        'position_organization_id' => $organization->id,
                        'sponsoring_organization_id' => $organization->id,
                        'funding_organization_id' => $organization->id,
                        'job_title_id' => $jobTitles[$jobTitleName]->id,
                        'project_manager_user_id' => $this->eligibleProjectManagerId(
                            $projectManagers,
                            $projectManagerEmail,
                        ),
                    ])
                );

                $this->seedDetails($position);
            }

            $this->seedActivityHistory(Position::query()->where('position_code', 'IRAD-SWE-001')->first());
        });
    }

    private function position(
        string $code,
        string $status,
        string $jobTitle,
        int $level,
        string $location,
        string $team,
        string $mission,
        ?string $projectManagerEmail,
        int $daysOld,
        bool $highRisk = false,
        bool $travel = false,
        ?string $closeReason = null,
    ): array {
        $isClosed = $status === 'Closed';

        return [
            'position_code' => $code,
            'status' => $status,
            'job_title_name' => $jobTitle,
            'job_title' => $jobTitle,
            'level' => $level,
            'team_name' => $team,
            'project_team_name' => $team,
            'project_manager_email' => $projectManagerEmail,
            'certifications_required' => in_array($jobTitle, ['Cybersecurity Analyst', 'Systems Administrator', 'Network Engineer'], true) ? 'Security+ or equivalent preferred.' : null,
            'training_required' => 'Annual cyber awareness and IRAD process orientation.',
            'experience' => $level >= 4 ? 'Seven or more years of directly relevant experience.' : 'Three or more years of directly relevant experience.',
            'is_essential' => in_array($jobTitle, ['Software Engineer', 'Cybersecurity Analyst', 'Project Manager'], true),
            'travel_required' => $travel,
            'high_risk_role' => $highRisk,
            'location' => $location,
            'building' => $location === 'Remote' ? null : 'Building '.chr(65 + ($daysOld % 4)),
            'mission_description' => $mission,
            'component' => $team,
            'funding_info' => 'Development seed scenario with approved FY26 funding.',
            'request_to_close' => $isClosed,
            'scheduled_to_close' => $isClosed ? now()->subDays(14)->toDateString() : null,
            'close_date' => $isClosed ? now()->subDays(7)->toDateString() : null,
            'close_reason' => $closeReason,
            'customer_lead_name' => match ($projectManagerEmail) {
                'project.manager1@localhost' => 'B. J. Hunnicutt',
                'project.manager2@localhost' => 'Charles Winchester',
                'admin@localhost' => 'Hawkeye Pierce',
                'cotr@localhost' => 'Margaret Houlihan',
                default => 'Sherman Potter',
            },
            'customer_created_at' => now()->subDays($daysOld)->toDateString(),
            'notes' => $isClosed && str_contains((string) $closeReason, 'filled')
                ? 'Filled-position scenario. The Position status remains Closed because the current schema supports Open, In Process, and Closed.'
                : 'Scenario-driven development position.',
        ];
    }

    private function eligibleProjectManagerId(
        \Illuminate\Support\Collection $projectManagers,
        ?string $email,
    ): ?int {
        if (! $email) {
            return null;
        }

        $projectManager = $projectManagers->get($email);

        if (! $projectManager) {
            $this->command?->warn(
                "PositionSeeder skipped invalid Project Manager assignment for {$email}. "
                .'Only users with the project_manager role may be assigned.'
            );

            return null;
        }

        return $projectManager->id;
    }

    private function seedDetails(Position $position): void
    {
        foreach ([
            ['Active clearance eligibility', 'Candidate must be eligible to satisfy the position security requirements.'],
            ['Customer communication', 'Candidate must communicate clearly with technical and non-technical stakeholders.'],
        ] as $index => [$name, $description]) {
            PositionCustomSkill::updateOrCreate(
                ['position_id' => $position->id, 'name' => $name],
                ['description' => $description, 'is_active' => true, 'sort_order' => $index + 1]
            );
        }

        foreach ([
            ['Support mission delivery', 'Perform role-specific work supporting the assigned mission.'],
            ['Prepare status updates', 'Provide concise progress, risk, and dependency updates.'],
        ] as $index => [$name, $description]) {
            PositionCustomTask::updateOrCreate(
                ['position_id' => $position->id, 'name' => $name],
                ['description' => $description, 'is_active' => true, 'sort_order' => $index + 1]
            );
        }
    }

    private function seedActivityHistory(?Position $position): void
    {
        if (! $position) {
            return;
        }

        foreach ([
            ['status', 'Draft', 'Open', 'Position opened for recruiting.', 45],
            ['level', '3', '4', 'Position level increased after customer review.', 39],
            ['location', 'Arlington, VA', 'Washington, DC', 'Duty location updated.', 30],
            ['funding_info', 'Pending approval', 'FY26 funding approved', 'Funding was approved.', 21],
        ] as [$field, $old, $new, $description, $daysAgo]) {
            PositionActivity::updateOrCreate(
                ['position_id' => $position->id, 'action' => 'updated', 'field_name' => $field, 'description' => $description],
                ['user_id' => null, 'old_value' => $old, 'new_value' => $new, 'created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays($daysAgo)]
            );
        }
    }
}
