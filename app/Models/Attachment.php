<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'extension',
        'size',
        'category',
        'description',
        'uploaded_by_user_id',
        'is_primary',
        'sort_order',
    ];

    protected $hidden = [
        'stored_name',
        'disk',
        'path',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'url',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->exists || ! $this->path) {
            return null;
        }

        return route('attachments.show', $this);
    }
}