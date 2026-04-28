<?php

// database/seeders/PositionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            [
                'position_code' => 'TEST-001',
                'status' => 'open',
                'labor_category' => 'Software Engineer',
                'job_title' => 'Frontend Developer',
                'level' => 2,
                'project_team_name' => 'China Team',
                'organization_name' => 'ABC/ABC/ABC/ABC',
                'customer_lead_name' => 'John Smith',
                'customer_created_at' => now()->subDays(30),
            ],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}