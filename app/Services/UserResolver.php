<?php

namespace App\Services;

use App\Models\Person;
use App\Models\User;
use RuntimeException;

class UserResolver
{
    /**
     * Get the active person_code for the current request.
     *
     * Resolution order:
     * 1. Session-based development override, if DEV_USER_ENABLED=true
     * 2. Default development/testing person_code, if DEV_USER_ENABLED=true
     * 3. ADFS/server-provided identity value
     */
    public function getPersonCode(): string|int
    {
        /*
         * Development/testing session override.
         *
         * This allows the DevUserSwitcher to impersonate another person.
         * It is controlled by DEV_USER_ENABLED, not APP_DEBUG, so it can also
         * be used to test production builds locally before ADFS is available.
         */
        if (
            config('devuser.enabled') === true &&
            session()->has('dev_person_code')
        ) {
            return session('dev_person_code');
        }

        /*
         * Default development/testing person_code.
         *
         * This is useful for local development and local production-build
         * testing when ADFS/server authentication is not configured yet.
         */
        if (config('devuser.enabled') === true) {
            $personCode = config('devuser.person_code');

            if (blank($personCode)) {
                throw new RuntimeException('DEV_USER_ENABLED is true, but no DEV_PERSON_CODE is configured.');
            }

            return $personCode;
        }

        /*
         * Production / real authentication path.
         *
         * In real production, ADFS, IIS, Apache, or a reverse proxy should
         * provide a server/header value that maps to people.person_code.
         */
        $personCode = $this->getPersonCodeFromAdfs();

        if (blank($personCode)) {
            throw new RuntimeException('No person_code was found from ADFS/server authentication.');
        }

        return $personCode;
    }

    /**
     * Attempt to read the person_code from ADFS/server variables.
     *
     * The exact key depends on how ADFS, IIS, Apache, or the reverse proxy
     * passes claims into PHP.
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

    /**
     * Resolve the current Person record from person_code.
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
     * Resolve the current Laravel user_id from the linked Person record.
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
     * Resolve the linked Laravel User model.
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
     * Resolve the full current-user context.
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