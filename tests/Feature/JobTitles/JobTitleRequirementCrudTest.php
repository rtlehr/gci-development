<?php

use App\Models\JobTitle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a skill using the explicit Job Title lookup', function () {
    $this->withoutMiddleware();

    $jobTitle = JobTitle::create([
        'name' => 'Systems Engineer',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $response = $this->post(
        route('job-titles.skills.store', $jobTitle),
        [
            'name' => 'Requirements Analysis',
            'description' => 'Analyzes operational requirements.',
            'requirement_type' => 'required',
            'is_active' => true,
            'sort_order' => 4,
        ]
    );

    $response
        ->assertRedirect(route('job-titles.show', $jobTitle))
        ->assertSessionHas('success', 'Skill added successfully.');

    $this->assertDatabaseHas('job_title_skills', [
        'job_title_id' => $jobTitle->id,
        'name' => 'Requirements Analysis',
        'requirement_type' => 'required',
        'is_active' => true,
        'sort_order' => 4,
    ]);
});

it('stores a task using the explicit Job Title lookup', function () {
    $this->withoutMiddleware();

    $jobTitle = JobTitle::create([
        'name' => 'Program Manager',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $response = $this->post(
        route('job-titles.tasks.store', $jobTitle),
        [
            'name' => 'Prepare weekly status report',
            'description' => 'Summarizes project activity.',
            'is_active' => true,
            'sort_order' => 6,
        ]
    );

    $response
        ->assertRedirect(route('job-titles.show', $jobTitle))
        ->assertSessionHas('success', 'Task added successfully.');

    $this->assertDatabaseHas('job_title_tasks', [
        'job_title_id' => $jobTitle->id,
        'name' => 'Prepare weekly status report',
        'is_active' => true,
        'sort_order' => 6,
    ]);
});

it('deletes a skill belonging to the selected Job Title', function () {
    $this->withoutMiddleware();

    $jobTitle = JobTitle::create([
        'name' => 'Cybersecurity Analyst',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $skill = $jobTitle->skills()->create([
        'name' => 'Temporary Skill',
        'description' => null,
        'requirement_type' => 'desired',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->delete(route('job-titles.skills.destroy', [$jobTitle, $skill]))
        ->assertRedirect(route('job-titles.show', $jobTitle))
        ->assertSessionHas('success', 'Skill deleted successfully.');

    $this->assertDatabaseMissing('job_title_skills', [
        'id' => $skill->id,
    ]);
});

it('does not delete a skill through a different Job Title', function () {
    $this->withoutMiddleware();

    $jobTitle = JobTitle::create([
        'name' => 'First Job Title',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $otherJobTitle = JobTitle::create([
        'name' => 'Second Job Title',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $skill = $jobTitle->skills()->create([
        'name' => 'Protected Skill',
        'description' => null,
        'requirement_type' => 'required',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->delete(route('job-titles.skills.destroy', [$otherJobTitle, $skill]))
        ->assertNotFound();

    $this->assertDatabaseHas('job_title_skills', [
        'id' => $skill->id,
        'name' => 'Protected Skill',
    ]);
});

it('deletes a task belonging to the selected Job Title', function () {
    $this->withoutMiddleware();

    $jobTitle = JobTitle::create([
        'name' => 'Operations Manager',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $task = $jobTitle->tasks()->create([
        'name' => 'Temporary Task',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->delete(route('job-titles.tasks.destroy', [$jobTitle, $task]))
        ->assertRedirect(route('job-titles.show', $jobTitle))
        ->assertSessionHas('success', 'Task deleted successfully.');

    $this->assertDatabaseMissing('job_title_tasks', [
        'id' => $task->id,
    ]);
});

it('does not delete a task through a different Job Title', function () {
    $this->withoutMiddleware();

    $jobTitle = JobTitle::create([
        'name' => 'Primary Job Title',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $otherJobTitle = JobTitle::create([
        'name' => 'Other Job Title',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $task = $jobTitle->tasks()->create([
        'name' => 'Protected Task',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->delete(route('job-titles.tasks.destroy', [$otherJobTitle, $task]))
        ->assertNotFound();

    $this->assertDatabaseHas('job_title_tasks', [
        'id' => $task->id,
        'name' => 'Protected Task',
    ]);
});
