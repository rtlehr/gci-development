<?php

namespace App\Models;

use App\Casts\EncryptedValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonNote extends Model
{
    public const CATEGORY_KUDOS = 'kudos';
    public const CATEGORY_REPRIMAND = 'reprimand';
    public const CATEGORY_GENERAL = 'general';

    public const CATEGORIES = [
        self::CATEGORY_KUDOS,
        self::CATEGORY_REPRIMAND,
        self::CATEGORY_GENERAL,
    ];

    protected $fillable = [
        'person_id',
        'entered_by_user_id',
        'entered_by_name',
        'category',
        'note',
    ];

    protected $casts = [
        'note' => EncryptedValue::class,
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by_user_id');
    }
}
