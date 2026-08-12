<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomFieldValue extends Model
{
    protected $fillable = [
        'custom_field_id', 'value_text', 'value_date', 'value_json',
    ];

    protected $casts = [
        'value_date' => 'date:Y-m-d',
        'value_json' => 'array',
    ];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }

    public function fieldable(): MorphTo
    {
        return $this->morphTo();
    }
}
