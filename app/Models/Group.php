<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Person;

class Group extends Model
{
    protected $fillable = [
        'group_name',
    ];

    public function people()
    {
        return $this->belongsToMany(Person::class, 'person_group')
            ->withTimestamps();
    }

}