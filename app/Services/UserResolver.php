<?php

namespace App\Services;

use App\Models\Person;
use App\Models\User;

use RuntimeException;

class UserResolver
{
    /**
     * Get the person_code from config/devuser.php
     */
    public function getPersonCode(): string|int
    {
        $personCode = config('devuser.person_code');

        if (blank($personCode)) {
            throw new RuntimeException('No person_code is configured in config/devuser.php.');
        }

        return $personCode;
    }

    /**
     * Resolve the current Person from person_code
     */
    public function resolvePerson(): Person
    {
        $personCode = $this->getPersonCode();

        $person = Person::query()
            ->where('person_code', $personCode)
            ->first();

        if (! $person) {
            throw new RuntimeException("No Person found for person_code [{$personCode}].");
        }

        return $person;
    }

    /**
     * Resolve the current user_id from the linked Person
     */
    public function resolveUserId(): int
    {
        $person = $this->resolvePerson();

        if (! $person->user_id) {
            throw new RuntimeException("Person [{$person->person_code}] does not have a linked user_id.");
        }

        return (int) $person->user_id;
    }

    /**
     * Resolve the linked Laravel User model
     */
    public function resolveUser(): User
    {
        $userId = $this->resolveUserId();

        $user = User::query()->find($userId);

        if (! $user) {
            throw new RuntimeException("No User found for user_id [{$userId}].");
        }

        return $user;
    }

    /**
     * Resolve the full current-user context
     */
    public function resolve(): array
    {
        $person = $this->resolvePerson();
        $user = $this->resolveUser();

        return [
            'person_code' => $person->person_code,
            'person' => $person,
            'user_id' => $user->id,
            'user' => $user,
        ];
    }
}