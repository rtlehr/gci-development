<?php

namespace App\Http\Controllers;

use App\Models\JobTitle;
use App\Models\JobTitleSkill;
use App\Models\JobTitleTask;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Display the combined Job Title Requirements workflow.
     */
    public function requirementsIndex()
    {
        $jobTitles = JobTitle::query()
            ->withCount([
                'skills',
                'tasks',

                'skills as required_skills_count' => function ($query) {
                    $query->where('requirement_type', 'required');
                },

                'skills as desired_skills_count' => function ($query) {
                    $query->where('requirement_type', 'desired');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('JobTitleRequirements/Index', [
            'jobTitles' => $jobTitles,
        ]);
    }

    public function create()
    {
        return Inertia::render('JobTitles/Create', [
            'cloneSources' => JobTitle::query()
                ->withCount(['skills', 'tasks'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]),
        ]);
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

            'clone_job_title_id' => [
                'nullable',
                'integer',
                'exists:job_titles,id',
            ],
        ]);

        $cloneSourceId = $validated['clone_job_title_id'] ?? null;
        unset($validated['clone_job_title_id']);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $jobTitle = DB::transaction(function () use ($validated, $cloneSourceId) {
            $jobTitle = JobTitle::create($validated);

            if (! $cloneSourceId) {
                return $jobTitle;
            }

            $cloneSource = JobTitle::query()
                ->with(['skills', 'tasks'])
                ->findOrFail($cloneSourceId);

            $jobTitle->skills()->createMany(
                $cloneSource->skills
                    ->map(fn (JobTitleSkill $skill) => [
                        'name' => $skill->name,
                        'description' => $skill->description,
                        'requirement_type' => $skill->requirement_type,
                        'is_active' => $skill->is_active,
                        'sort_order' => $skill->sort_order,
                    ])
                    ->all()
            );

            $jobTitle->tasks()->createMany(
                $cloneSource->tasks
                    ->map(fn (JobTitleTask $task) => [
                        'name' => $task->name,
                        'description' => $task->description,
                        'is_active' => $task->is_active,
                        'sort_order' => $task->sort_order,
                    ])
                    ->all()
            );

            return $jobTitle;
        });

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with(
                'success',
                $cloneSourceId
                    ? 'Job Title created and its skills and tasks were cloned successfully.'
                    : 'Job Title created successfully.'
            );
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
    public function storeSkill(Request $request, int $jobTitle)
    {
        $jobTitle = JobTitle::query()->findOrFail($jobTitle);

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

            'requirement_type' => [
                'required',
                'in:required,desired',
            ],

            'sort_order' => [
                'nullable',
                'integer',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);

        $jobTitle->skills()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'requirement_type' => $validated['requirement_type'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Skill added successfully.');
    }

    /**
     * Update a Skill assigned to this Job Title.
     */
    public function updateSkill(Request $request, int $jobTitle, int $skill)
    {
        $jobTitle = JobTitle::query()->findOrFail($jobTitle);
        $skill = JobTitleSkill::query()->findOrFail($skill);

        abort_unless(
            (int) $skill->job_title_id === (int) $jobTitle->getKey(),
            404
        );

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

            'requirement_type' => [
                'required',
                'in:required,desired',
            ],

            'sort_order' => [
                'nullable',
                'integer',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);

        $skill->forceFill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'requirement_type' => $validated['requirement_type'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? false,
        ])->saveOrFail();

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Skill updated successfully.');
    }

    /**
     * Delete a Job Title Skill.
     */
    public function destroySkill(int $jobTitle, int $skill)
    {
        $jobTitle = JobTitle::query()->findOrFail($jobTitle);
        $skill = JobTitleSkill::query()->findOrFail($skill);

        abort_unless(
            (int) $skill->job_title_id === (int) $jobTitle->getKey(),
            404
        );

        $skill->delete();

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Skill deleted successfully.');
    }

    /**
     * Add a Task to this Job Title.
     */
    public function storeTask(Request $request, int $jobTitle)
    {
        $jobTitle = JobTitle::query()->findOrFail($jobTitle);

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

        $jobTitle->tasks()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Task added successfully.');
    }

    /**
     * Update a Task assigned to this Job Title.
     */
    public function updateTask(Request $request, int $jobTitle, int $task)
    {
        $jobTitle = JobTitle::query()->findOrFail($jobTitle);
        $task = JobTitleTask::query()->findOrFail($task);

        abort_unless(
            (int) $task->job_title_id === (int) $jobTitle->getKey(),
            404
        );

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

        $task->forceFill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? false,
        ])->saveOrFail();

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Delete a Job Title Task.
     */
    public function destroyTask(int $jobTitle, int $task)
    {
        $jobTitle = JobTitle::query()->findOrFail($jobTitle);
        $task = JobTitleTask::query()->findOrFail($task);

        abort_unless(
            (int) $task->job_title_id === (int) $jobTitle->getKey(),
            404
        );

        $task->delete();

        return redirect()
            ->route('job-titles.show', $jobTitle)
            ->with('success', 'Task deleted successfully.');
    }
}
