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
                'position_code' => 'ZN-001',
                'status' => 'open',
                'labor_category' => 'Software Engineer',
                'job_title' => 'Frontend Developer',
                'level' => 2,
                'project_team_name' => 'China Team',
                'organization_name' => 'ABC/ABC/ABC/ABC',
                'customer_lead_name' => 'John Smith',
                'customer_created_at' => now()->subDays(30),
            ],
            [
                'position_code' => 'ZN-002',
                'status' => 'filled',
                'labor_category' => 'Backend Engineer',
                'job_title' => 'API Developer',
                'level' => 3,
                'project_team_name' => 'Europe Team',
                'organization_name' => 'XYZ/XYZ/XYZ',
                'customer_lead_name' => 'Jane Doe',
                'customer_created_at' => now()->subDays(60),
            ],
            [
                'position_code' => 'ZN-003',
                'status' => 'on_hold',
                'labor_category' => 'DevOps',
                'job_title' => 'Cloud Engineer',
                'level' => 4,
                'project_team_name' => 'US Team',
                'organization_name' => 'DEF/DEF',
                'customer_lead_name' => 'Mike Johnson',
                'customer_created_at' => now()->subDays(10),
            ],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}