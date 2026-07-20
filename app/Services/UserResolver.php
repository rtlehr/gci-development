<?php

namespace App\Services;

use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class UserResolver
{
    /**
     * Get the active person code for a non-Laravel-authenticated request.
     *
     * Resolution order:
     * 1. Session-based development override.
     * 2. Default development person code.
     * 3. ADFS/server-provided identity value.
     */
    public function getPersonCode(): string|int
    {
        if (
            config('devuser.enabled') === true
            && session()->has('dev_person_code')
        ) {
            return session('dev_person_code');
        }

        if (config('devuser.enabled') === true) {
            $personCode = config('devuser.person_code');

            if (blank($personCode)) {
                throw new RuntimeException(
                    'DEV_USER_ENABLED is true, but no DEV_PERSON_CODE is configured.'
                );
            }

            return $personCode;
        }

        $personCode = $this->getPersonCodeFromAdfs();

        if (blank($personCode)) {
            throw new RuntimeException(
                'No person_code was found from ADFS/server authentication.'
            );
        }

        return $personCode;
    }

    /**
     * Return the Person associated with the supplied or authenticated User.
     *
     * This is the optional lookup. Use resolvePerson() when the Person is
     * required for the operation being performed.
     */
    public function findPerson(?User $user = null): ?Person
    {
        $user ??= Auth::user();

        if ($user) {
            return Person::query()
                ->where('user_id', $user->id)
                ->first();
        }

        try {
            $personCode = $this->getPersonCode();
        } catch (RuntimeException) {
            return null;
        }

        return Person::query()
            ->where('person_code', $personCode)
            ->first();
    }

    /**
     * Resolve a required Person model.
     */
    public function resolvePerson(): Person
    {
        $person = $this->findPerson();

        if ($person) {
            return $person;
        }

        if (Auth::check()) {
            throw new RuntimeException(
                'Authenticated User ['.Auth::id().'] does not have a linked Person record.'
            );
        }

        $personCode = $this->getPersonCode();

        throw new RuntimeException(
            "No Person found for person_code [{$personCode}]."
        );
    }

    /**
     * Resolve the current Laravel user ID through a required Person record.
     */
    public function resolveUserId(): int
    {
        $person = $this->resolvePerson();

        if (! $person->user_id) {
            throw new RuntimeException(
                "Person [{$person->person_code}] does not have a linked user_id."
            );
        }

        return (int) $person->user_id;
    }

    /**
     * Resolve the current Laravel User model.
     *
     * Normal Laravel authentication always takes precedence. Development and
     * ADFS identity resolution are fallbacks for requests that have not yet
     * established a Laravel-authenticated user.
     */
    public function resolveUser(): User
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            return $user;
        }

        $userId = $this->resolveUserId();
        $user = User::query()->find($userId);

        if (! $user) {
            throw new RuntimeException(
                "No User found for user_id [{$userId}]."
            );
        }

        return $user;
    }

    /**
     * Resolve the complete current-user context.
     *
     * The User is required; the Person is optional.
     *
     * @return array{person_code: string|int|null, person: Person|null, user_id: int, user: User}
     */
    public function resolve(): array
    {
        $user = $this->resolveUser();
        $person = $this->findPerson($user);

        return [
            'person_code' => $person?->person_code,
            'person' => $person,
            'user_id' => $user->id,
            'user' => $user,
        ];
    }

    /**
     * Read the person code supplied by ADFS, IIS, Apache, or a reverse proxy.
     */
    protected function getPersonCodeFromAdfs(): ?string
    {
        return request()->server('HTTP_PERSON_CODE')
            ?? request()->server('HTTP_EMPLOYEEID')
            ?? request()->server('HTTP_EMPLOYEE_ID')
            ?? request()->server('HTTP_ADFS_PERSON_CODE')
            ?? request()->server('AUTH_USER')
            ?? request()->server('LOGON_USER')
            ?? request()->server('REMOTE_USER')
            ?? null;
    }
}
