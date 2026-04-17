<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'candidate_code',
        'person_id',
        'position_id',
        'status',
        'candidate_fbr',
        'submitted_at',
        'submitted_by_person_id',
        'scheduled_start_date',
    ];

    protected $casts = [
        'candidate_fbr' => 'decimal:2',
        'submitted_at' => 'datetime',
        'scheduled_start_date' => 'date',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'submitted_by_person_id');
    }

    public function stepEvents(): HasMany
    {
        return $this->hasMany(CandidateStepEvent::class);
    }
}