<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionCustomSkill extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Fields
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'position_id',
        'name',
        'description',
        'requirement_type',
        'is_active',
        'sort_order',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Parent Position
     *
     * These skills are specific to a Position and are
     * added in addition to the Job Title skills.
     *
     * Example:
     *
     * Position:
     * Frontend Developer
     *
     * Job Title Skills:
     * - Vue.js
     * - Laravel
     * - TypeScript
     *
     * Position Custom Skills:
     * - Active Secret Clearance
     * - Experience with GCI
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}