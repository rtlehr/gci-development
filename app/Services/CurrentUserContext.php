<?php

namespace App\Services;

use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CurrentUserContext
{
    private bool $userResolved = false;

    private ?User $user = null;

    private bool $personResolved = false;

    private ?Person $person = null;

    /** @var array<int, string>|null */
    private ?array $permissions = null;

    /** @var array<string, mixed>|null */
    private ?array $payload = null;

    public function __construct(
        private readonly UserResolver $userResolver,
        private readonly PermissionService $permissionService,
    ) {
    }

    /**
     * Return the current User, or null when the request has no resolvable user.
     *
     * Laravel authentication takes precedence. When no authenticated user
     * exists, the resolver may still locate a development or ADFS user.
     * Failure to resolve a guest request is expected and is not reported.
     */
    public function user(): ?User
    {
        if ($this->userResolved) {
            return $this->user;
        }

        $this->userResolved = true;

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            return $this->user = $user;
        }

        try {
            $this->user = $this->userResolver->resolveUser();
        } catch (Throwable) {
            $this->user = null;
        }

        return $this->user;
    }

    /**
     * Return the linked Person when one exists.
     */
    public function person(): ?Person
    {
        if ($this->personResolved) {
            return $this->person;
        }

        $this->personResolved = true;

        $user = $this->user();

        if (! $user) {
            return $this->person = null;
        }

        return $this->person = Person::query()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Return all direct and role-derived permissions for the current User.
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }

        $user = $this->user();

        return $this->permissions = $user
            ? $this->permissionService->getUserPermissions($user->id)
            : [];
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
     * Build the shared frontend representation of the current user.
     *
     * Authentication and permissions require only a User. Person fields are
     * intentionally nullable because a linked Person is profile data, not an
     * authentication prerequisite.
     *
     * @return array<string, mixed>|null
     */
    public function payload(): ?array
    {
        if ($this->payload !== null) {
            return $this->payload;
        }

        $user = $this->user();

        if (! $user) {
            return null;
        }

        $person = $this->person();

        return $this->payload = [
            'id' => $user->id,
            'username' => $this->displayName($user, $person),
            'role' => config('devuser.role', ''),
            'permissions' => $this->permissions(),
            'email' => $user->email,
            'person_id' => $person?->id,
            'person_code' => $person?->person_code,
            'first_name' => $person?->first_name,
            'last_name' => $person?->last_name,
        ];
    }

    /**
     * Clear memoized values when authentication changes during one request.
     */
    public function forget(): void
    {
        $this->userResolved = false;
        $this->user = null;
        $this->personResolved = false;
        $this->person = null;
        $this->permissions = null;
        $this->payload = null;
    }

    private function displayName(User $user, ?Person $person): string
    {
        if ($person) {
            $firstName = $person->preferred_name
                ?: $person->first_name
                ?: '';

            $displayName = trim(
                $firstName.' '.($person->last_name ?: '')
            );

            if ($displayName !== '') {
                return $displayName;
            }
        }

        if (! blank($user->name)) {
            return $user->name;
        }

        return $user->email ?? '';
    }
}