<?php

namespace App\Http\Controllers;

use App\Models\JobTitle;
use App\Models\JobTitleSkill;
use App\Models\JobTitleTask;

use Illuminate\Http\Request;
use Inertia\Inertia;

class JobTitleController extends Controller
{
    /**
     * Display all Job Titles.
     */
    public function index()
    {
        $jobTitles = JobTitle::withCount([
                'skills',
                'tasks',
                'positions',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('JobTitles/Index', [
            'jobTitles' => $jobTitles,
        ]);
    }

    /**
     * Show the create Job Title form.
     */
    public function create()
    {
        return Inertia::render('JobTitles/Create');
    }

    /**
     * Store a new Job Title.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:job_titles,name',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
            ],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $jobTitle = JobTitle::create($validated);

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Job Title created successfully.');
    }

    /**
     * Display one Job Title with its skills and tasks.
     */
    public function show(JobTitle $jobTitle)
    {
        $jobTitle->load([
            'skills',
            'tasks',
        ]);

        return Inertia::render('JobTitles/Show', [
            'jobTitle' => $jobTitle,
        ]);
    }

    /**
     * Show the edit Job Title form.
     */
    public function edit(JobTitle $jobTitle)
    {
        return Inertia::render('JobTitles/Edit', [
            'jobTitle' => $jobTitle,
        ]);
    }

    /**
     * Update an existing Job Title.
     */
    public function update(Request $request, JobTitle $jobTitle)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:job_titles,name,' . $jobTitle->id,
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
            ],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? false;

        $jobTitle->update($validated);

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Job Title updated successfully.');
    }

    /**
     * Delete a Job Title.
     */
    public function destroy(JobTitle $jobTitle)
    {
        /*
        |--------------------------------------------------------------------------
        | Prevent Delete When Positions Use This Job Title
        |--------------------------------------------------------------------------
        */

        if ($jobTitle->positions()->exists()) {
            return redirect()
                ->route('job-titles.index')
                ->with(
                    'error',
                    'This Job Title cannot be deleted because it is assigned to one or more Positions.'
                );
        }

        $jobTitle->delete();

        return redirect()
            ->route('job-titles.index')
            ->with('success', 'Job Title deleted successfully.');
    }

    /**
     * Add a Skill to this Job Title.
     */
    public function storeSkill(Request $request, JobTitle $jobTitle)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'sort_order' => [
                'nullable',
                'integer',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);

        $validated['job_title_id'] = $jobTitle->id;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        JobTitleSkill::create($validated);

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Skill added successfully.');
    }

    /**
     * Add a Task to this Job Title.
     */
    public function storeTask(Request $request, JobTitle $jobTitle)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'sort_order' => [
                'nullable',
                'integer',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);

        $validated['job_title_id'] = $jobTitle->id;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        JobTitleTask::create($validated);

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Task added successfully.');
    }

    /**
     * Delete a Job Title Skill.
     */
    public function destroySkill(JobTitle $jobTitle, JobTitleSkill $skill)
    {
        if ($skill->job_title_id !== $jobTitle->id) {
            abort(404);
        }

        $skill->delete();

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Skill deleted successfully.');
    }

    /**
     * Delete a Job Title Task.
     */
    public function destroyTask(JobTitle $jobTitle, JobTitleTask $task)
    {
        if ($task->job_title_id !== $jobTitle->id) {
            abort(404);
        }

        $task->delete();

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Task deleted successfully.');
    }
}