<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user and linked Person record.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::query()->create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            [$firstName, $lastName] = $this->splitName($input['name']);

            Person::query()->create([
                'user_id' => $user->id,
                'person_code' => $this->generatePersonCode(),
                'first_name' => $firstName,
                'preferred_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user->email,
                'employment_status' => 'active',
            ]);

            return $user;
        });
    }

    /**
     * Split a display name into first and last names.
     *
     * A fallback last name is used because IRAD Person records require one.
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        $firstName = array_shift($parts) ?: 'User';
        $lastName = trim(implode(' ', $parts));

        return [
            $firstName,
            $lastName !== '' ? $lastName : 'Unknown',
        ];
    }

    /**
     * Generate a unique temporary IRAD person code for self-registration.
     */
    private function generatePersonCode(): string
    {
        do {
            $personCode = 'REG-'.Str::upper(Str::random(12));
        } while (
            Person::query()
                ->where('person_code', $personCode)
                ->exists()
        );

        return $personCode;
    }
}
