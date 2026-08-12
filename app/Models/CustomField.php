<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    public const ENTITY_PERSON = 'person';
    public const ENTITY_POSITION = 'position';

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_RADIO = 'radio';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_DATE = 'date';

    protected $fillable = [
        'entity_type', 'name', 'key', 'field_type', 'description', 'placeholder',
        'is_required', 'is_active', 'is_list_column', 'is_searchable', 'is_filterable', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_list_column' => 'boolean',
        'is_searchable' => 'boolean',
        'is_filterable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeOptions(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hasValues(): bool
    {
        return $this->values()->exists();
    }
}
