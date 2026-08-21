<?php

namespace App\Services;

use App\Contracts\Identity\PersonCodeProvider;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class UserResolver
{
    public function __construct(
        private readonly PersonCodeProvider $personCodeProvider,
    ) {
    }

    /**
     * Get the active person_code from the configured identity provider.
     *
     * The source may differ between development and production, but the value
     * returned to the rest of IRAD is always the same enterprise identifier:
     * people.person_code.
     */
    public function getPersonCode(): string|int
    {
        $personCode = $this->personCodeProvider->resolve();

        if (blank($personCode)) {
            throw new RuntimeException(
                'No person_code was resolved by the configured identity provider ['
                .config('identity.driver').'].'
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

        return Person::findByPersonCode($personCode);
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

}
