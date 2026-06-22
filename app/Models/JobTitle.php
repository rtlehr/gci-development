<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTitle extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Fields
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
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

    public function skills(): HasMany
    {
        return $this->hasMany(JobTitleSkill::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(JobTitleTask::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}