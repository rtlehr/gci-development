<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use App\Models\Organization;
use App\Models\Position;
use App\Models\PositionActivity;
use App\Models\PositionCustomSkill;
use App\Models\PositionCustomTask;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $rootOrganization = Organization::firstOrCreate(
            ['id' => 1],
            [
                'parent_id' => null,
                'name' => 'Org Root',
                'status' => 'active',
                'notes' => 'Default root organization.',
            ]
        );

        $rootOrganization->rebuildHierarchyFields();

        $jobTitles = JobTitle::query()
            ->whereIn('name', ['Frontend Developer', 'Program Manager'])
            ->get()
            ->keyBy('name');

        $frontendDeveloper = $jobTitles->get('Frontend Developer');
        $programManager = $jobTitles->get('Program Manager');

        if (! $frontendDeveloper || ! $programManager) {
            $this->command?->warn('PositionSeeder skipped: required job titles were not found.');

            return;
        }

        $positions = [
            [
                'position_code' => 'TEST-001',
                'status' => 'Open',
                'job_title_id' => $frontendDeveloper->id,
                'experience_level' => 'Experienced',
                'certifications_required' => 'Security+ Certification',
                'training_required' => 'Annual Cyber Awareness Training',
                'experience' => '5+ years of experience with Vue.js, Laravel, and enterprise application development.',
                'is_essential' => true,
                'travel_required' => false,
                'high_risk_role' => false,
                'location' => 'Washington, DC',
                'building' => 'Building A',
                'mission_description' => 'Supports enterprise modernization initiatives and frontend application development.',
                'component' => 'Technology Services',
                'funding_info' => 'FY26 approved funding allocation.',
                'project_team_name' => 'China Team',
                'customer_lead_name' => 'Sherman Potter',
                'customer_created_at' => now()->subDays(30),
                'notes' => 'Seeded testing position for the development environment.',
            ],
            [
                'position_code' => 'IRAD-DEV-002',
                'status' => 'Open',
                'job_title_id' => $frontendDeveloper->id,
                'experience_level' => 'Senior',
                'certifications_required' => 'Security+ or equivalent',
                'training_required' => 'Secure coding and annual cyber awareness training',
                'experience' => '7+ years building accessible enterprise web applications.',
                'is_essential' => true,
                'travel_required' => false,
                'high_risk_role' => false,
                'location' => 'Arlington, VA',
                'building' => 'Innovation Center',
                'mission_description' => 'Leads frontend architecture and reusable component development for IRAD.',
                'component' => 'Application Engineering',
                'funding_info' => 'Funded through the FY26 modernization portfolio.',
                'project_team_name' => 'IRAD Modernization',
                'customer_lead_name' => 'Margaret Houlihan',
                'customer_created_at' => now()->subDays(45),
                'notes' => 'Priority senior development position.',
            ],
            [
                'position_code' => 'IRAD-DEV-003',
                'status' => 'In Process',
                'job_title_id' => $frontendDeveloper->id,
                'experience_level' => 'Experienced',
                'certifications_required' => null,
                'training_required' => 'Vue 3 and TypeScript standards orientation',
                'experience' => '4+ years of modern JavaScript and component-based UI development.',
                'is_essential' => false,
                'travel_required' => false,
                'high_risk_role' => false,
                'location' => 'Remote',
                'building' => null,
                'mission_description' => 'Builds responsive user interfaces and improves usability across portal modules.',
                'component' => 'User Experience',
                'funding_info' => 'Funded through September 2027.',
                'project_team_name' => 'Portal Experience',
                'customer_lead_name' => 'B. J. Hunnicutt',
                'customer_created_at' => now()->subDays(22),
                'notes' => 'Candidate interviews are underway.',
            ],
            [
                'position_code' => 'IRAD-DEV-004',
                'status' => 'Open',
                'job_title_id' => $frontendDeveloper->id,
                'experience_level' => 'Novice',
                'certifications_required' => null,
                'training_required' => 'Annual cyber awareness training',
                'experience' => '1-3 years of frontend development experience.',
                'is_essential' => false,
                'travel_required' => false,
                'high_risk_role' => false,
                'location' => 'Winchester, VA',
                'building' => 'Building C',
                'mission_description' => 'Supports feature delivery, defect correction, and automated UI testing.',
                'component' => 'Application Engineering',
                'funding_info' => 'Entry-level labor allocation approved for FY26.',
                'project_team_name' => 'Development Support',
                'customer_lead_name' => 'Maxwell Klinger',
                'customer_created_at' => now()->subDays(18),
                'notes' => 'Suitable for a developer growing into enterprise application work.',
            ],
            [
                'position_code' => 'IRAD-DEV-005',
                'status' => 'Closed',
                'job_title_id' => $frontendDeveloper->id,
                'experience_level' => 'Experienced',
                'certifications_required' => 'Security+ Certification',
                'training_required' => 'Accessibility and secure coding training',
                'experience' => '5+ years of frontend development.',
                'is_essential' => false,
                'travel_required' => false,
                'high_risk_role' => false,
                'location' => 'Washington, DC',
                'building' => 'Building B',
                'mission_description' => 'Provided short-term support for the initial portal release.',
                'component' => 'Technology Services',
                'funding_info' => 'Funding period completed.',
                'request_to_close' => true,
                'scheduled_to_close' => now()->subDays(20),
                'close_date' => now()->subDays(15),
                'close_reason' => 'Initial release support period completed.',
                'project_team_name' => 'Release Support',
                'customer_lead_name' => 'Henry Blake',
                'customer_created_at' => now()->subMonths(6),
                'notes' => 'Retained as a closed-position test scenario.',
            ],
            [
                'position_code' => 'IRAD-PM-006',
                'status' => 'Open',
                'job_title_id' => $programManager->id,
                'experience_level' => 'Senior',
                'certifications_required' => 'PMP preferred',
                'training_required' => 'Program governance and records management training',
                'experience' => '8+ years managing complex software delivery programs.',
                'is_essential' => true,
                'travel_required' => true,
                'high_risk_role' => false,
                'location' => 'Arlington, VA',
                'building' => 'Program Office',
                'mission_description' => 'Directs the IRAD roadmap, delivery schedule, risks, and stakeholder coordination.',
                'component' => 'Program Management Office',
                'funding_info' => 'Fully funded senior program management billet.',
                'project_team_name' => 'IRAD Leadership',
                'customer_lead_name' => 'Sherman Potter',
                'customer_created_at' => now()->subDays(60),
                'notes' => 'High-priority leadership position.',
            ],
            [
                'position_code' => 'IRAD-PM-007',
                'status' => 'In Process',
                'job_title_id' => $programManager->id,
                'experience_level' => 'Experienced',
                'certifications_required' => 'PMP or DAWIA certification preferred',
                'training_required' => 'Customer engagement orientation',
                'experience' => '5+ years managing technical projects and cross-functional teams.',
                'is_essential' => true,
                'travel_required' => true,
                'high_risk_role' => false,
                'location' => 'Washington, DC',
                'building' => 'Headquarters',
                'mission_description' => 'Manages project execution, dependencies, milestones, and customer communications.',
                'component' => 'Program Management Office',
                'funding_info' => 'FY26 and FY27 funding approved.',
                'project_team_name' => 'Customer Delivery',
                'customer_lead_name' => 'Trapper John McIntyre',
                'customer_created_at' => now()->subDays(33),
                'notes' => 'Selection package is under review.',
            ],
            [
                'position_code' => 'IRAD-PM-008',
                'status' => 'Open',
                'job_title_id' => $programManager->id,
                'experience_level' => 'Experienced',
                'certifications_required' => null,
                'training_required' => 'Agile delivery and risk management training',
                'experience' => '4+ years coordinating software projects.',
                'is_essential' => false,
                'travel_required' => false,
                'high_risk_role' => false,
                'location' => 'Remote',
                'building' => null,
                'mission_description' => 'Coordinates backlog readiness, sprint planning, and release communications.',
                'component' => 'Delivery Operations',
                'funding_info' => 'Funded through the digital transformation initiative.',
                'project_team_name' => 'Agile Delivery',
                'customer_lead_name' => 'Charles Winchester',
                'customer_created_at' => now()->subDays(27),
                'notes' => 'Remote-capable project management role.',
            ],
            [
                'position_code' => 'IRAD-PM-009',
                'status' => 'Open',
                'job_title_id' => $programManager->id,
                'experience_level' => 'Novice',
                'certifications_required' => 'CAPM preferred',
                'training_required' => 'Project controls and scheduling fundamentals',
                'experience' => '2+ years supporting project schedules and status reporting.',
                'is_essential' => false,
                'travel_required' => false,
                'high_risk_role' => false,
                'location' => 'Winchester, VA',
                'building' => 'Building C',
                'mission_description' => 'Supports schedule maintenance, action tracking, and project reporting.',
                'component' => 'Program Controls',
                'funding_info' => 'Junior project support allocation.',
                'project_team_name' => 'Program Controls',
                'customer_lead_name' => 'Frank Burns',
                'customer_created_at' => now()->subDays(16),
                'notes' => 'Designed as a growth position for a developing project manager.',
            ],
            [
                'position_code' => 'IRAD-PM-010',
                'status' => 'Closed',
                'job_title_id' => $programManager->id,
                'experience_level' => 'Senior',
                'certifications_required' => 'PMP',
                'training_required' => 'Program governance training',
                'experience' => '10+ years of program management experience.',
                'is_essential' => true,
                'travel_required' => true,
                'high_risk_role' => false,
                'location' => 'Arlington, VA',
                'building' => 'Program Office',
                'mission_description' => 'Managed the planning phase for the original IRAD implementation.',
                'component' => 'Program Management Office',
                'funding_info' => 'Planning-phase funding completed.',
                'request_to_close' => true,
                'scheduled_to_close' => now()->subMonths(2),
                'close_date' => now()->subMonth(),
                'close_reason' => 'Planning phase completed and responsibilities transitioned.',
                'project_team_name' => 'Initial Planning',
                'customer_lead_name' => 'Henry Blake',
                'customer_created_at' => now()->subYear(),
                'notes' => 'Closed program manager position for reporting and filtering tests.',
            ],
            [
                'position_code' => 'IRAD-DEV-011',
                'status' => 'In Process',
                'job_title_id' => $frontendDeveloper->id,
                'experience_level' => 'Senior',
                'certifications_required' => 'Security+ Certification',
                'training_required' => 'Section 508 and WCAG implementation training',
                'experience' => '7+ years delivering accessible enterprise interfaces.',
                'is_essential' => true,
                'travel_required' => false,
                'high_risk_role' => false,
                'location' => 'Washington, DC',
                'building' => 'Accessibility Lab',
                'mission_description' => 'Leads accessibility remediation, design-system standards, and usability validation.',
                'component' => 'User Experience',
                'funding_info' => 'Accessibility modernization funding approved.',
                'project_team_name' => 'Accessibility Initiative',
                'customer_lead_name' => 'Kellye Nakahara',
                'customer_created_at' => now()->subDays(38),
                'notes' => 'Candidate screening is in progress.',
            ],
            [
                'position_code' => 'IRAD-PM-012',
                'status' => 'Open',
                'job_title_id' => $programManager->id,
                'experience_level' => 'Senior',
                'certifications_required' => 'PMP preferred',
                'training_required' => 'Portfolio governance and executive briefing training',
                'experience' => '8+ years overseeing multiple concurrent technical projects.',
                'is_essential' => true,
                'travel_required' => true,
                'high_risk_role' => true,
                'location' => 'Washington, DC',
                'building' => 'Headquarters',
                'mission_description' => 'Oversees portfolio priorities, executive reporting, and cross-program risk resolution.',
                'component' => 'Executive Portfolio Office',
                'funding_info' => 'Executive portfolio funding approved through FY28.',
                'project_team_name' => 'Portfolio Governance',
                'customer_lead_name' => 'Sherman Potter',
                'customer_created_at' => now()->subDays(50),
                'notes' => 'High-visibility portfolio management position.',
            ],
        ];

        foreach ($positions as $positionData) {
            $position = Position::updateOrCreate(
                ['position_code' => $positionData['position_code']],
                array_merge([
                    'position_organization_id' => $rootOrganization->id,
                    'sponsoring_organization_id' => $rootOrganization->id,
                    'funding_organization_id' => $rootOrganization->id,
                    'request_to_close' => false,
                    'scheduled_to_close' => null,
                    'close_date' => null,
                    'close_reason' => null,
                ], $positionData)
            );

            $this->seedPositionDetails($position);
        }

        $this->seedActivityHistory(
            Position::query()->where('position_code', 'TEST-001')->first()
        );
    }

    private function seedPositionDetails(Position $position): void
    {
        $skills = [
            ['name' => 'Active Secret Clearance', 'description' => 'Candidate must be eligible for or already hold an active clearance.'],
            ['name' => 'GCI Process Familiarity', 'description' => 'Knowledge of internal GCI workflows and development processes.'],
        ];

        foreach ($skills as $index => $skill) {
            PositionCustomSkill::updateOrCreate(
                ['position_id' => $position->id, 'name' => $skill['name']],
                [
                    'description' => $skill['description'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        $tasks = [
            ['name' => 'Support IRAD modernization work', 'description' => 'Assist with modernization and refinement of IRAD application modules.'],
            ['name' => 'Prepare customer-facing demos', 'description' => 'Support demonstrations and walkthroughs for customer stakeholders.'],
        ];

        foreach ($tasks as $index => $task) {
            PositionCustomTask::updateOrCreate(
                ['position_id' => $position->id, 'name' => $task['name']],
                [
                    'description' => $task['description'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function seedActivityHistory(?Position $position): void
    {
        if (! $position) {
            return;
        }

        $activities = [
            ['field_name' => 'status', 'old_value' => 'Draft', 'new_value' => 'Open', 'description' => 'Updated status.', 'days_ago' => 14],
            ['field_name' => 'experience_level', 'old_value' => 'Novice', 'new_value' => 'Experienced', 'description' => 'Updated experience level.', 'days_ago' => 12],
            ['field_name' => 'high_risk_role', 'old_value' => 'No', 'new_value' => 'Yes', 'description' => 'Updated high risk role.', 'days_ago' => 10],
            ['field_name' => 'location', 'old_value' => 'Washington, DC', 'new_value' => 'Arlington, VA', 'description' => 'Updated location.', 'days_ago' => 7],
            ['field_name' => 'funding_info', 'old_value' => 'FY25 approved funding allocation.', 'new_value' => 'FY26 approved funding allocation.', 'description' => 'Updated funding information.', 'days_ago' => 3],
        ];

        foreach ($activities as $activity) {
            PositionActivity::updateOrCreate(
                [
                    'position_id' => $position->id,
                    'action' => 'updated',
                    'field_name' => $activity['field_name'],
                    'description' => $activity['description'],
                ],
                [
                    'user_id' => null,
                    'old_value' => $activity['old_value'],
                    'new_value' => $activity['new_value'],
                    'created_at' => now()->subDays($activity['days_ago']),
                    'updated_at' => now()->subDays($activity['days_ago']),
                ]
            );
        }
    }
}
