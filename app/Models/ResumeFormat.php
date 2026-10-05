<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResumeFormat extends Model
{
    protected $fillable = ['name', 'description', 'version', 'is_active', 'mappings', 'column_orders'];

    protected $casts = ['mappings' => 'array', 'column_orders' => 'array', 'is_active' => 'boolean'];
}
