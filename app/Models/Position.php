<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PositionAssignment;
use App\Models\Person;
use App\Models\Organization;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_code',
        'status',
        'job_title',
        'experience_level',
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

    protected $casts = [
        'is_essential' => 'boolean',
        'travel_required' => 'boolean',
        'high_risk_role' => 'boolean',
        'request_to_close' => 'boolean',

        'scheduled_to_close' => 'date',
        'close_date' => 'date',
        'customer_created_at' => 'date',
    ];

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

    public function activities()
    {
        return $this->hasMany(PositionActivity::class)
            ->latest();
    }

    /**
     * Keep labor_category automatically aligned with
     * job_title + experience_level.
     */
    protected static function booted(): void
    {
        static::saving(function (Position $position) {
            if (
                filled($position->job_title) &&
                filled($position->experience_level)
            ) {
                $position->labor_category =
                    $position->job_title . ' - ' . $position->experience_level;
            }
        });
    }
}