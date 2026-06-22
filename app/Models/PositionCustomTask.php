<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionCustomTask extends Model
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
     * These tasks are specific to a Position and are
     * added in addition to the Job Title tasks.
     *
     * Example:
     *
     * Job Title Tasks:
     * - Develop Features
     * - Fix Bugs
     * - Review Code
     *
     * Position Custom Tasks:
     * - Support IRAD Modernization
     * - Coordinate Customer Demos
     * - Prepare Monthly Reports
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}