<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Fields
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'position_code',
        'status',

        'job_title_id',
        'job_title',
        'level',
        'team_name',
        'project_manager_user_id',
        'labor_category',

        'certifications_required',
        'training_required',
        'experience',

        'is_essential',
        'travel_required',
        'high_risk_role',

        'location',
        'building',

        'mission_description',
        'component',

        'position_organization_id',
        'sponsoring_organization_id',
        'funding_organization_id',

        'funding_info',

        'request_to_close',
        'scheduled_to_close',
        'close_date',
        'close_reason',

        'project_team_name',
        'customer_lead_name',
        'customer_created_at',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'is_essential' => 'boolean',
        'travel_required' => 'boolean',
        'high_risk_role' => 'boolean',
        'request_to_close' => 'boolean',

        'scheduled_to_close' => 'date',
        'close_date' => 'date',
        'customer_created_at' => 'date',
        'level' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Position Assignment Relationships
    |--------------------------------------------------------------------------
    */

    public function assignments()
    {
        return $this->hasMany(PositionAssignment::class);
    }

    public function people()
    {
        return $this->belongsToMany(Person::class, 'position_assignments')
            ->withPivot([
                'start_date',
                'end_date',
                'assignment_status',
                'assignment_type',
                'notes',
            ])
            ->withTimestamps();
    }

    public function currentAssignment()
    {
        return $this->hasOne(PositionAssignment::class)
            ->whereNull('end_date');
    }

    /*
    |--------------------------------------------------------------------------
    | Candidate Relationships
    |--------------------------------------------------------------------------
    */

    public function candidates()
    {
        return $this->hasMany(Candidate::class)
            ->latest('submitted_at')
            ->latest('created_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Job Title Relationship
    |--------------------------------------------------------------------------
    */

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(User::class, 'project_manager_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Position-Specific Skills / Tasks
    |--------------------------------------------------------------------------
    */

    public function customSkills()
    {
        return $this->hasMany(PositionCustomSkill::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function customTasks()
    {
        return $this->hasMany(PositionCustomTask::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | Organization Relationships
    |--------------------------------------------------------------------------
    */

    public function positionOrganization()
    {
        return $this->belongsTo(
            Organization::class,
            'position_organization_id'
        );
    }

    public function sponsoringOrganization()
    {
        return $this->belongsTo(
            Organization::class,
            'sponsoring_organization_id'
        );
    }

    public function fundingOrganization()
    {
        return $this->belongsTo(
            Organization::class,
            'funding_organization_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Activity / Audit History
    |--------------------------------------------------------------------------
    */

    public function activities()
    {
        return $this->hasMany(PositionActivity::class)
            ->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Combined Skill / Task Helpers
    |--------------------------------------------------------------------------
    */

    public function inheritedSkills()
    {
        return $this->jobTitle?->skills ?? collect();
    }

    public function inheritedTasks()
    {
        return $this->jobTitle?->tasks ?? collect();
    }

    public function allSkills()
    {
        return $this->inheritedSkills()
            ->map(function ($skill) {
                return [
                    'source' => 'Job Title',
                    'name' => $skill->name,
                    'description' => $skill->description,
                ];
            })
            ->concat(
                $this->customSkills->map(function ($skill) {
                    return [
                        'source' => 'Custom',
                        'name' => $skill->name,
                        'description' => $skill->description,
                    ];
                })
            )
            ->values();
    }

    public function allTasks()
    {
        return $this->inheritedTasks()
            ->map(function ($task) {
                return [
                    'source' => 'Job Title',
                    'name' => $task->name,
                    'description' => $task->description,
                ];
            })
            ->concat(
                $this->customTasks->map(function ($task) {
                    return [
                        'source' => 'Custom',
                        'name' => $task->name,
                        'description' => $task->description,
                    ];
                })
            )
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::saving(function (Position $position) {

            /*
            |--------------------------------------------------------------------------
            | Keep Job Title Snapshot Current
            |--------------------------------------------------------------------------
            |
            | job_title_id points to the master JobTitle record.
            | job_title stores only the readable name as a plain string.
            |
            */

            if ($position->job_title_id) {
                $jobTitle = JobTitle::query()
                    ->select('id', 'name')
                    ->find($position->job_title_id);

                if ($jobTitle) {
                    $position->job_title = $jobTitle->name;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Keep Labor Category Current
            |--------------------------------------------------------------------------
            */

            if (
                filled($position->job_title) &&
                filled($position->level)
            ) {
                $position->labor_category =
                    $position->job_title . ' - Level ' . $position->level;
            }

            /*
            |--------------------------------------------------------------------------
            | Clear Labor Category When Incomplete
            |--------------------------------------------------------------------------
            */

            if (
                blank($position->job_title) ||
                blank($position->level)
            ) {
                $position->labor_category = null;
            }
        });
    }
}