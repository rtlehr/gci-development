<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            // These are the development scenarios that should appear as Filled
            // in the Staffing Matrix. Each candidate has a completed workflow.
            $filled = [
                'IRAD-SWE-001' => 'CAND-001',
                'IRAD-DBA-005' => 'CAND-011',
            ];

            $positionIds = Position::query()
                ->whereIn('position_code', array_keys($filled))
                ->pluck('id');

            // Make rerunning the seeder deterministic without disturbing
            // assignments for positions outside these development scenarios.
            PositionAssignment::query()
                ->whereIn('position_id', $positionIds)
                ->delete();

            foreach ($filled as $positionCode => $candidateCode) {
                $position = Position::query()
                    ->where('position_code', $positionCode)
                    ->firstOrFail();

                $candidate = Candidate::query()
                    ->with('person')
                    ->where('candidate_code', $candidateCode)
                    ->where('position_id', $position->id)
                    ->firstOrFail();

                PositionAssignment::create([
                    'position_id' => $position->id,
                    'person_id' => $candidate->person_id,
                    'start_date' => $candidate->scheduled_start_date ?? now()->subDays(14)->toDateString(),
                    'assignment_status' => 'active',
                    'assignment_type' => 'staffing',
                    'notes' => 'Development seed assignment created from the completed selected-candidate workflow.',
                ]);
            }
        });
    }
}
