<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Person;

class Team extends Model
{
    protected $fillable = [
        'team_name',
    ];

    public function people()
    {
        return $this->belongsToMany(Person::class, 'person_team')
            ->withTimestamps();
    }

}