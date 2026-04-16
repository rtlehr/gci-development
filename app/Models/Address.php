<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'person_id',
        'address_type',
        'line_1',
        'line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}