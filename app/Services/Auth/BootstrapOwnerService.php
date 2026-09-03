<?php

namespace App\Services\Auth;

use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BootstrapOwnerService
{
    public const SESSION_KEY = 'bootstrap_owner_authenticated';

    public function configured(): bool
    {
        return config('bootstrap_login.enabled') === true;
    }

    public function setupComplete(): bool
    {
        if (! Schema::hasTable('application_installation_state')) {
            return false;
        }

        return DB::table('application_installation_state')
            ->where('id', 1)
            ->whereNotNull('setup_completed_at')
            ->exists();
    }

    public function loginAvailable(): bool
    {
        return $this->configured()
            && Schema::hasTable('application_installation_state')
            && ! $this->setupComplete();
    }

    public function ownerPersonCode(): string
    {
        return (string) config('bootstrap_login.owner_person_code', '1111111');
    }

    public function isBootstrapOwner(?User $user): bool
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

    public function ownerUser(): ?User
    {
        $person = Person::findByPersonCode($this->ownerPersonCode());

        return $person?->user_id
            ? User::query()->find($person->user_id)
            : null;
    }

    public function markSession(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, true);
    }

    public function clearSession(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function hasValidBootstrapSession(Request $request): bool
    {
        return $this->loginAvailable()
            && $request->session()->get(self::SESSION_KEY) === true
            && $this->isBootstrapOwner($request->user());
    }

    public function completeSetup(): void
    {
        DB::table('application_installation_state')
            ->where('id', 1)
            ->update([
                'setup_completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
