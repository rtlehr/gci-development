<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Candidate;

class CandidateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Candidate::updateOrCreate(
            [
                'person_id' => 5,
                'position_id' => 1,
            ],
            [
                'workflow_id' => 1,
                'status' => 'selected',
                'candidate_fbr' => 0.01,
                'submitted_by_person_id' => 3,
                'submitted_at' => now(),
                'scheduled_start_date' => null,
            ]
        );
    }
}