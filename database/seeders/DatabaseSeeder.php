<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Person;
use App\Models\PositionAssignment;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $people = Person::factory()
            ->count(25)
            ->create();

        $openPositions = Position::factory()->open()->count(10)->create();
        $filledPositions = Position::factory()->filled()->count(8)->create();
        $onHoldPositions = Position::factory()->onHold()->count(4)->create();
        $closedPositions = Position::factory()->closed()->count(3)->create();

        foreach ($filledPositions as $position) {
            PositionAssignment::factory()
                ->active()
                ->create([
                    'position_id' => $position->id,
                    'person_id' => $people->random()->id,
                ]);
        }

        foreach ($filledPositions->take(4) as $position) {
            PositionAssignment::factory()
                ->ended()
                ->create([
                    'position_id' => $position->id,
                    'person_id' => $people->random()->id,
                ]);
        }
    }
}