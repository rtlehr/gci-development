<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionActivity extends Model
{
    protected $fillable = [
        'position_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'description',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}