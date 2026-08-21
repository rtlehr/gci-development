<?php

namespace App\Models;

use App\Casts\EncryptedValue;
use Illuminate\Database\Eloquent\Model;

class PersonPhoneNumber extends Model
{
    protected $fillable = [
        'person_id',
        'phone_number',
        'phone_type',
        'is_primary',
        'extension',
        'notes',
    ];

    protected $casts = [
        'extension' => EncryptedValue::class,
        'notes' => EncryptedValue::class,
        'is_primary' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
