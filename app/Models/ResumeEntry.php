<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResumeEntry extends Model
{
    protected $fillable = ['resume_id', 'section', 'sort_order', 'data', 'search_text'];

    protected $casts = ['data' => 'array'];
}
