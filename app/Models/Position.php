<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PositionAssignment;
use App\Models\Person;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_code',
        'status',
        'labor_category',
        'job_title',
        'level',
        'project_team_name',
        'organization_id',
        'customer_lead_name',
        'customer_created_at',
        'closed_at',
        'closed_reason',
        'notes',
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
        return $this->hasOne(PositionAssignment::class)->whereNull('end_date');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

}