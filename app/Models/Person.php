<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PositionAssignment;
use App\Models\Position;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
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
}