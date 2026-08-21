<?php

namespace App\Models;

use App\Casts\EncryptedValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateStepEvent extends Model
{
    protected $fillable = [
        'candidate_id',
        'workflow_step_id',
        'status_code',
        'requested_at',
        'scheduled_at',
        'completed_at',
        'performed_by_person_id',
        'notes',
        'comments',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'notes' => EncryptedValue::class,
        'comments' => EncryptedValue::class,
        'metadata' => 'array',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'performed_by_person_id');
    }
}