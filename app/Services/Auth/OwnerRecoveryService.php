<?php

namespace App\Services\Auth;

use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class OwnerRecoveryService
{
    public const SESSION_KEY = 'owner_recovery_authenticated_at';

    public function enabled(): bool
    {
        return config('owner_recovery.enabled') === true;
    }

    public function ownerPersonCode(): string
    {
        return (string) config('owner_recovery.owner_person_code', '1111111');
    }

    public function sessionMinutes(): int
    {
        return max(5, (int) config('owner_recovery.session_minutes', 30));
    }

    public function ownerUser(): ?User
    {
        $person = Person::findByPersonCode($this->ownerPersonCode());

        return $person?->user_id
            ? User::query()->find($person->user_id)
            : null;
    }

    public function isRecoveryOwner(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $person = $user->relationLoaded('person')
            ? $user->person
            : $user->person()->first();

        if (! $person || (string) $person->person_code !== $this->ownerPersonCode()) {
            return false;
        }

        return $user->roles()->where('name', 'owner')->exists();
    }

    public function authenticate(string $email, string $password): ?User
    {
        if (! $this->enabled()) {
            return null;
        }

        $owner = $this->ownerUser();

        if (! $owner || ! $this->isRecoveryOwner($owner)) {
            return null;
        }

        if (! hash_equals(
            mb_strtolower(trim((string) $owner->email)),
            mb_strtolower(trim($email)),
        )) {
            return null;
        }

        return Hash::check($password, (string) $owner->password)
            ? $owner
            : null;
    }

    public function markSession(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, now()->getTimestamp());
    }

    public function clearSession(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function hasValidSession(Request $request): bool
    {
        if (! $this->enabled() || ! $request->hasSession()) {
            return false;
        }

        $authenticatedAt = $request->session()->get(self::SESSION_KEY);

        if (! is_numeric($authenticatedAt)) {
            return false;
        }

        $expiresAt = Carbon::createFromTimestamp((int) $authenticatedAt)
            ->addMinutes($this->sessionMinutes());

        if (now()->greaterThanOrEqualTo($expiresAt)) {
            $this->clearSession($request);
            return false;
        }

        return $this->isRecoveryOwner($request->user());
    }
}
