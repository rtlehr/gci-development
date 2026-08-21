<?php

namespace App\Models;

use App\Casts\EncryptedValue;
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
        'line_1' => EncryptedValue::class,
        'line_2' => EncryptedValue::class,
        'city' => EncryptedValue::class,
        'state' => EncryptedValue::class,
        'postal_code' => EncryptedValue::class,
        'country' => EncryptedValue::class,
        'notes' => EncryptedValue::class,
        'is_primary' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
