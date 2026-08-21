<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEventLog extends Model
{
    protected $fillable = [
        'occurred_at',
        'user_id',
        'person_id',
        'user_name',
        'user_email',
        'event_type',
        'module',
        'action',
        'route_name',
        'route_parameters',
        'path',
        'http_method',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'session_identifier',
        'request_identifier',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'route_parameters' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
