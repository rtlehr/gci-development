<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'group_name' => 'GROUPS ONE',
            ],
            [
                'group_name' => 'GROUPS TWO',
            ],
            [
                'group_name' => 'GROUPS THREE',
            ],
        ];

        foreach ($groups as $group) {
            Group::updateOrCreate(
                [
                    'group_name' => $group['group_name'],
                ],
                $group
            );
        }
    }
}