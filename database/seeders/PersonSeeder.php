// database/seeders/PersonSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Person;

class PersonSeeder extends Seeder
{
    public function run(): void
    {
        $people = [
            [
                'person_code' => 'EMP-001',
                'first_name' => 'Alice',
                'last_name' => 'Johnson',
                'company_name' => 'TechCorp',
                'cell_phone' => '555-123-4567',
                'email' => 'alice@example.com',
            ],
            [
                'person_code' => 'EMP-002',
                'first_name' => 'Bob',
                'last_name' => 'Smith',
                'company_name' => 'DevSolutions',
                'cell_phone' => '555-987-6543',
                'email' => 'bob@example.com',
            ],
            [
                'person_code' => 'EMP-003',
                'first_name' => 'Charlie',
                'last_name' => 'Brown',
                'company_name' => 'CloudWorks',
                'cell_phone' => '555-222-3333',
                'email' => 'charlie@example.com',
            ],
        ];

        foreach ($people as $person) {
            Person::create($person);
        }
    }
}