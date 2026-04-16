<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PositionAssignment;
use App\Models\Position;
use App\Models\User;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'person_code',
        'first_name',
        'last_name',
        'company_name',
        'cell_phone',
        'email',
        'employment_status',
        'notes',
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
    
}