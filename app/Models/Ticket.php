<?php

namespace App\Models;

use App\Casts\EncryptedValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'title',
        'submitted_by_user_id',
        'request_type',
        'importance',
        'category',
        'description',
        'source_url',
        'screenshot_path',
        'assigned_to_user_id',
        'status',
        'resolution_notes',
    ];

    protected $casts = [
        'source_url' => EncryptedValue::class,
        'resolution_notes' => EncryptedValue::class,
    ];

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->latest();
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(\App\Models\User::class, 'ticket_user')
            ->withTimestamps();
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'ticket_watchers')
            ->withTimestamps();
    }
    
}