<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentPage extends Model
{
    public const TYPE_STANDARD = 'standard';
    public const TYPE_FAQ = 'faq';
    public const TYPE_CONTACT_DIRECTORY = 'contact_directory';
    public const TYPE_RESOURCE_LIBRARY = 'resource_library';
    public const TYPE_ANNOUNCEMENT = 'announcement';
    public const TYPE_POLICY = 'policy';

    protected $fillable = [
        'title',
        'slug',
        'navigation_label',
        'summary',
        'content_html',
        'page_type',
        'visibility',
        'status',
        'menu_location',
        'is_active',
        'sort_order',
        'effective_at',
        'expires_at',
        'help_key',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'effective_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function faqItems(): HasMany
    {
        return $this->hasMany(ContentPageFaqItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeFaqItems(): HasMany
    {
        return $this->faqItems()->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(fn (Builder $q) => $q
                ->whereNull('effective_at')
                ->orWhere('effective_at', '<=', now()))
            ->where(fn (Builder $q) => $q
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    public function isVisibleTo(bool $authenticated): bool
    {
        return match ($this->visibility) {
            'public' => true,
            'portal' => $authenticated,
            'both' => true,
            default => false,
        };
    }
}
