<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Position;
use App\Models\Person;

class PositionAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_id',
        'person_id',
        'start_date',
        'end_date',
        'assignment_status',
        'assignment_type',
        'notes',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}