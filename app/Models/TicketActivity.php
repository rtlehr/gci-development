<?php

namespace App\Models;

use App\Casts\EncryptedValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    protected $fillable = [
        'ticket_id',
        'changed_by_user_id',
        'event_type',
        'field_name',
        'old_value',
        'new_value',
        'comment',
    ];

    protected $casts = [
        'old_value' => EncryptedValue::class,
        'new_value' => EncryptedValue::class,
        'comment' => EncryptedValue::class,
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}