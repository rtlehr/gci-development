<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            [
                'team_name' => 'TEAM ONE',
            ],
            [
                'team_name' => 'TEAM TWO',
            ],
            [
                'team_name' => 'TEAM THREE',
            ],
        ];

        foreach ($teams as $team) {
            Team::updateOrCreate(
                [
                    'team_name' => $team['team_name'],
                ],
                $team
            );
        }
    }
}