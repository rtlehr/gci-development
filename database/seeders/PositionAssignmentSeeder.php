<?php

// database/seeders/PositionAssignmentSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PositionAssignment;
use App\Models\Position;
use App\Models\Person;

class PositionAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $positions = Position::all();
        $people = Person::all();

        // Assign Bob to ZN-002 (filled position)
        PositionAssignment::create([
            'position_id' => $positions->where('position_code', 'ZN-002')->first()->id,
            'person_id' => $people->where('person_code', 'EMP-002')->first()->id,
            'start_date' => now()->subDays(20),
            'assignment_status' => 'active',
        ]);

        // Assign Alice to ZN-001 (recent assignment)
        PositionAssignment::create([
            'position_id' => $positions->where('position_code', 'ZN-001')->first()->id,
            'person_id' => $people->where('person_code', 'EMP-001')->first()->id,
            'start_date' => now()->subDays(5),
            'assignment_status' => 'active',
        ]);

        // Past assignment example
        PositionAssignment::create([
            'position_id' => $positions->where('position_code', 'ZN-002')->first()->id,
            'person_id' => $people->where('person_code', 'EMP-003')->first()->id,
            'start_date' => now()->subDays(60),
            'end_date' => now()->subDays(25),
            'assignment_status' => 'ended',
        ]);
    }
}