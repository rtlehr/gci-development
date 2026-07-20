<?php

use App\Models\JobTitle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates a Job Title skill without leaving the Job Title page', function () {
    $this->withoutMiddleware();

    $jobTitle = JobTitle::create([
        'name' => 'Systems Engineer',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $skill = $jobTitle->skills()->create([
        'name' => 'Old Skill Name',
        'description' => 'Old description.',
        'requirement_type' => 'required',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $response = $this->from(route('job-titles.show', $jobTitle))
        ->put(route('job-titles.skills.update', [$jobTitle, $skill]), [
            'name' => 'Updated Skill Name',
            'description' => 'Updated description.',
            'requirement_type' => 'desired',
            'is_active' => false,
            'sort_order' => 9,
        ]);

    $response->assertRedirect(route('job-titles.show', $jobTitle));
    $response->assertSessionHas('success', 'Skill updated successfully.');

    $this->assertDatabaseHas('job_title_skills', [
        'id' => $skill->id,
        'job_title_id' => $jobTitle->id,
        'name' => 'Updated Skill Name',
        'description' => 'Updated description.',
        'requirement_type' => 'desired',
        'is_active' => false,
        'sort_order' => 9,
    ]);
});

it('updates a Job Title task without leaving the Job Title page', function () {
    $this->withoutMiddleware();

    $jobTitle = JobTitle::create([
        'name' => 'Program Manager',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $task = $jobTitle->tasks()->create([
        'name' => 'Old Task Name',
        'description' => 'Old task description.',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $response = $this->from(route('job-titles.show', $jobTitle))
        ->put(route('job-titles.tasks.update', [$jobTitle, $task]), [
            'name' => 'Updated Task Name',
            'description' => 'Updated task description.',
            'is_active' => false,
            'sort_order' => 12,
        ]);

    $response->assertRedirect(route('job-titles.show', $jobTitle));
    $response->assertSessionHas('success', 'Task updated successfully.');

    $this->assertDatabaseHas('job_title_tasks', [
        'id' => $task->id,
        'job_title_id' => $jobTitle->id,
        'name' => 'Updated Task Name',
        'description' => 'Updated task description.',
        'is_active' => false,
        'sort_order' => 12,
    ]);
});

it('does not update a skill through a different Job Title', function () {
    $this->withoutMiddleware();

    $jobTitle = JobTitle::create([
        'name' => 'First Job Title',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $otherJobTitle = JobTitle::create([
        'name' => 'Second Job Title',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $skill = $jobTitle->skills()->create([
        'name' => 'Protected Skill',
        'requirement_type' => 'required',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->put(route('job-titles.skills.update', [$otherJobTitle, $skill]), [
        'name' => 'Invalid Update',
        'requirement_type' => 'desired',
        'is_active' => true,
        'sort_order' => 0,
    ])->assertNotFound();

    $this->assertDatabaseHas('job_title_skills', [
        'id' => $skill->id,
        'name' => 'Protected Skill',
        'requirement_type' => 'required',
    ]);
});
