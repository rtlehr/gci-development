<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\BootstrapOwnerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BootstrapOwner extends Command
{
    protected $signature = 'app:bootstrap-owner
        {--email= : Email address to use if the Owner user must be created}
        {--name= : Display name to use if the Owner user must be created}';

    protected $description = 'Prepare the initial Owner password for a brand-new Insite Portal installation';

    public function handle(BootstrapOwnerService $bootstrap): int
    {
        if (! $bootstrap->configured()) {
            $this->error('Bootstrap login is disabled. Set IRAD_BOOTSTRAP_LOGIN_ENABLED=true for the initial installation.');
            return self::FAILURE;
        }

        if ($bootstrap->setupComplete()) {
            $this->error('Initial setup has already been completed. Bootstrap password login cannot be reopened by this command.');
            return self::FAILURE;
        }

        $ownerRole = Role::query()->where('name', 'owner')->first();
        if (! $ownerRole) {
            $this->error('The Owner role does not exist. Seed permissions and roles before running this command.');
            return self::FAILURE;
        }

        $personCode = $bootstrap->ownerPersonCode();
        $person = Person::findByPersonCode($personCode);
        $user = $person?->user;

        if (! $user) {
            $name = trim((string) ($this->option('name') ?: $this->ask('Owner display name', 'Initial Owner')));
            $email = trim((string) ($this->option('email') ?: $this->ask('Owner email', 'owner@localhost')));

            if ($name === '' || $email === '') {
                $this->error('Owner name and email are required.');
                return self::FAILURE;
            }

            [$firstName, $lastName] = $this->splitName($name);

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(Str::random(64)),
                ],
            );

            $person = Person::updateOrCreateByPersonCode($personCode, [
                'user_id' => $user->id,
                'first_name' => $firstName,
                'preferred_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'employment_status' => 'Active',
                'notes' => 'Initial installation Owner account.',
            ]);
        }

        $user->roles()->syncWithoutDetaching([$ownerRole->id]);

        $password = (string) $this->secret('Temporary bootstrap Owner password (minimum 12 characters)');
        $confirm = (string) $this->secret('Confirm temporary password');

        if (strlen($password) < 12) {
            $this->error('The bootstrap password must be at least 12 characters.');
            return self::FAILURE;
        }

        if (! hash_equals($password, $confirm)) {
            $this->error('The passwords do not match.');
            return self::FAILURE;
        }

        $user->forceFill(['password' => Hash::make($password)])->save();

        $this->newLine();
        $this->info('Bootstrap Owner is ready.');
        $this->line('Person code: '.$personCode);
        $this->line('Email: '.$user->email);
        $this->line('Initial login: /login');
        $this->warn('This password works only while initial setup is incomplete and bootstrap login is enabled.');

        return self::SUCCESS;
    }

    /** @return array{0:string,1:string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = array_shift($parts) ?: 'Initial';
        $last = implode(' ', $parts) ?: 'Owner';

        return [$first, $last];
    }
}
