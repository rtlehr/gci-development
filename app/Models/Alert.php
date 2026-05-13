<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'user_id',
        'person_id',
        'type',
        'priority',
        'title',
        'message',
        'action_url',
        'source_type',
        'source_id',
        'metadata',
        'read_at',
        'should_email',
        'email_queued_at',
        'emailed_at',
        'email_error',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'emailed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }
}