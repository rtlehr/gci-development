<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Position;
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

        $positions = [
            [
                'position_code' => 'TEST-001',
                'status' => 'Open',
                'labor_category' => 'Software Engineer',
                'job_title' => 'Frontend Developer',
                'level' => 2,
                'project_team_name' => 'China Team',
                'organization_id' => $rootOrganization->id,
                'customer_lead_name' => 'John Smith',
                'customer_created_at' => now()->subDays(30),
            ],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}