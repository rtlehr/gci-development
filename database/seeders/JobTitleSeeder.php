<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use App\Models\JobTitleSkill;
use App\Models\JobTitleTask;

use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    /**
     * Seed Job Titles, Skills, and Tasks.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Frontend Developer
        |--------------------------------------------------------------------------
        */

        $frontendDeveloper = JobTitle::create([
            'name' => 'Frontend Developer',
            'description' => 'Develops and maintains user interfaces using Vue, Laravel, and related technologies.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Skills
        |--------------------------------------------------------------------------
        */

        $skills = [
            'Vue.js',
            'Laravel',
            'TypeScript',
            'MySQL',
            'Git',
        ];

        foreach ($skills as $index => $skill) {

            JobTitleSkill::create([
                'job_title_id' => $frontendDeveloper->id,
                'name' => $skill,
                'description' => $skill . ' related knowledge and experience.',
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Tasks
        |--------------------------------------------------------------------------
        */

        $tasks = [
            'Build frontend features',
            'Connect Vue pages to Laravel data',
            'Fix UI defects and bugs',
            'Review application workflows',
            'Maintain technical documentation',
        ];

        foreach ($tasks as $index => $task) {

            JobTitleTask::create([
                'job_title_id' => $frontendDeveloper->id,
                'name' => $task,
                'description' => $task,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Program Manager
        |--------------------------------------------------------------------------
        */

        $programManager = JobTitle::create([
            'name' => 'Program Manager',
            'description' => 'Manages project planning, execution, schedules, and stakeholder communication.',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        JobTitleSkill::create([
            'job_title_id' => $programManager->id,
            'name' => 'Project Management',
            'description' => 'Project planning and execution.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        JobTitleSkill::create([
            'job_title_id' => $programManager->id,
            'name' => 'Risk Management',
            'description' => 'Identifying and managing risks.',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        JobTitleTask::create([
            'job_title_id' => $programManager->id,
            'name' => 'Manage project schedules',
            'description' => 'Maintain project schedules and milestones.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        JobTitleTask::create([
            'job_title_id' => $programManager->id,
            'name' => 'Coordinate stakeholder meetings',
            'description' => 'Communicate with project stakeholders.',
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }
}