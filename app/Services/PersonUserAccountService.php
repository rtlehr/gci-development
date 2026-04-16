<?php

namespace App\Services;

use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Str;

class PersonUserAccountService
{
    public function createForPerson(array $personData): User
    {
        return User::create([
            'name' => $personData['person_code'],
            'email' => $personData['email'] ?? null,
            'password' => bcrypt(Str::random(32)),
        ]);
    }

    public function syncFromPerson(Person $person, array $personData): void
    {
        if (!$person->user_id) {
            return;
        }

        $user = User::find($person->user_id);

        if (!$user) {
            return;
        }

        $user->name = $personData['person_code'];
        $user->email = $personData['email'] ?? null;
        $user->save();
    }
}