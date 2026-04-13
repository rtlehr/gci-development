<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserListPreference extends Model
{
    protected $fillable = [
        'user_id',
        'list_key',
        'visible_columns',
        'column_order',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'column_order' => 'array',
    ];
}
