<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageHelp extends Model
{
    protected $table = 'page_help';

    protected $fillable = [
        'help_key',
        'title',
        'content_html',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}