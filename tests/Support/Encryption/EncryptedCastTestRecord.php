<?php

namespace Tests\Support\Encryption;

use App\Casts\EncryptedValue;
use Illuminate\Database\Eloquent\Model;

class EncryptedCastTestRecord extends Model
{
    protected $table = 'encrypted_cast_test_records';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'secret' => EncryptedValue::class,
        ];
    }
}