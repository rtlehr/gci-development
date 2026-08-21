<?php

namespace App\Models;

use App\Casts\EncryptedValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PositionAssignment;
use App\Models\Position;
use App\Models\User;
use App\Models\PersonPhoneNumber;
use App\Models\Address;
use App\Models\Attachment;
use App\Models\Group;
use App\Models\Team;
use App\Models\PersonNote;
use App\Models\CustomFieldValue;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'person_code',
        'first_name',
        'alternate_first_name',
        'preferred_name',
        'last_name',
        'alternate_last_name',
        'company_name',
        'email',
        'employment_status',
        'notes',
        'resume_path',
    ];


    protected $casts = [
        'notes' => EncryptedValue::class,
    ];

    public function assignments()
    {
        return $this->hasMany(PositionAssignment::class);
    }

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'position_assignments')
            ->withPivot(['start_date', 'end_date', 'assignment_status', 'assignment_type', 'notes'])
            ->withTimestamps();
    }


    public function getPrimaryAddressDisplayAttribute(): string
    {
        $address = $this->relationLoaded('primaryAddress')
            ? $this->getRelation('primaryAddress')
            : $this->primaryAddress()->first();

        if (! $address) {
            return '';
        }

        $cityStatePostal = trim(implode(' ', array_filter([
            trim((string) $address->state),
            trim((string) $address->postal_code),
        ], fn ($value) => $value !== '')));

        return implode(', ', array_filter([
            trim((string) $address->line_1),
            trim((string) $address->city),
            $cityStatePostal,
        ], fn ($value) => $value !== ''));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function phoneNumbers()
    {
        return $this->hasMany(PersonPhoneNumber::class);
    }

    public function primaryPhoneNumber()
    {
        return $this->hasOne(PersonPhoneNumber::class)->where('is_primary', true);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function primaryAddress()
    {
        return $this->hasOne(Address::class)->where('is_primary', true);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'person_group')
            ->withTimestamps();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'person_team')
            ->withTimestamps();
    }

    public function personNotes()
    {
        return $this->hasMany(PersonNote::class)
            ->orderByRaw("CASE category WHEN 'kudos' THEN 1 WHEN 'reprimand' THEN 2 ELSE 3 END")
            ->latest();
    }
    
    public function customFieldValues()
    {
        return $this->morphMany(CustomFieldValue::class, 'fieldable');
    }

}