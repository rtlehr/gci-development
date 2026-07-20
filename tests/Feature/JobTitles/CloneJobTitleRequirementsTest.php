<?php

use App\Models\JobTitle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('clones skills and tasks when creating a job title', function () {
    $this->withoutMiddleware();

    $source = JobTitle::create([
        'name' => 'Source Job Title',
        'description' => 'Source requirements.',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $source->skills()->createMany([
        [
            'name' => 'Required Skill',
            'description' => 'A required skill.',
            'requirement_type' => 'required',
            'is_active' => true,
            'sort_order' => 1,
        ],
        [
            'name' => 'Desired Skill',
            'description' => 'A desired skill.',
            'requirement_type' => 'desired',
            'is_active' => false,
            'sort_order' => 2,
        ],
    ]);

    $source->tasks()->create([
        'name' => 'Source Task',
        'description' => 'A standard task.',
        'is_active' => true,
        'sort_order' => 3,
    ]);

    $response = $this->post(route('job-titles.store'), [
        'name' => 'Cloned Job Title',
        'description' => 'Created from another title.',
        'is_active' => true,
        'sort_order' => 20,
        'clone_job_title_id' => $source->id,
    ]);

    $created = JobTitle::where('name', 'Cloned Job Title')->firstOrFail();

    $response->assertRedirect(route('job-titles.show', $created));

    expect($created->skills()->count())->toBe(2)
        ->and($created->tasks()->count())->toBe(1);

    $this->assertDatabaseHas('job_title_skills', [
        'job_title_id' => $created->id,
        'name' => 'Required Skill',
        'description' => 'A required skill.',
        'requirement_type' => 'required',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->assertDatabaseHas('job_title_skills', [
        'job_title_id' => $created->id,
        'name' => 'Desired Skill',
        'description' => 'A desired skill.',
        'requirement_type' => 'desired',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    $this->assertDatabaseHas('job_title_tasks', [
        'job_title_id' => $created->id,
        'name' => 'Source Task',
        'description' => 'A standard task.',
        'is_active' => true,
        'sort_order' => 3,
    ]);
});

it('creates a job title without cloned requirements when no source is selected', function () {
    $this->withoutMiddleware();

    $response = $this->post(route('job-titles.store'), [
        'name' => 'Blank Job Title',
        'description' => null,
        'is_active' => true,
        'sort_order' => 0,
        'clone_job_title_id' => null,
    ]);

    $created = JobTitle::where('name', 'Blank Job Title')->firstOrFail();

    $response->assertRedirect(route('job-titles.show', $created));

    expect($created->skills()->count())->toBe(0)
        ->and($created->tasks()->count())->toBe(0);
});
