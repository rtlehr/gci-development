<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    protected $fillable = [
        'code',
        'name',
        'step_order',
        'is_active',
        'allows_requested_at',
        'allows_scheduled_at',
        'allows_completed_at',
        'allows_notes',
        'allows_comments',
        'allows_status',
        'default_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allows_requested_at' => 'boolean',
        'allows_scheduled_at' => 'boolean',
        'allows_completed_at' => 'boolean',
        'allows_notes' => 'boolean',
        'allows_comments' => 'boolean',
        'allows_status' => 'boolean',
    ];

    public function statuses(): HasMany
    {
        return $this->hasMany(WorkflowStepStatus::class)->orderBy('sort_order');
    }

    public function candidateStepEvents(): HasMany
    {
        return $this->hasMany(CandidateStepEvent::class);
    }
}