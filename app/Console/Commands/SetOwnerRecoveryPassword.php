<?php

namespace App\Console\Commands;

use App\Services\Auth\OwnerRecoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetOwnerRecoveryPassword extends Command
{
    protected $signature = 'app:owner-recovery-password
        {--email= : Optionally update the designated Owner email used for recovery login}';

    protected $description = 'Set or rotate the local break-glass Owner recovery password';

    public function handle(OwnerRecoveryService $recovery): int
    {
        if (! $recovery->enabled()) {
            $this->error('Owner recovery is disabled. Set IRAD_OWNER_RECOVERY_ENABLED=true before configuring it.');
            return self::FAILURE;
        }

        $owner = $recovery->ownerUser();

        if (! $owner || ! $recovery->isRecoveryOwner($owner)) {
            $this->error('The designated Owner user could not be found or does not have the Owner role.');
            $this->line('Expected person_code: '.$recovery->ownerPersonCode());
            return self::FAILURE;
        }

        $email = trim((string) ($this->option('email') ?: $owner->email));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('The Owner account does not have a valid email address for recovery login.');
            $this->newLine();
            $this->line('Provide a valid recovery email with the --email option, for example:');
            $this->line('  php artisan app:owner-recovery-password --email=owner@insite.local');
            $this->newLine();
            $this->line('The email will be saved to the designated Owner account and used as the Owner Recovery username.');
            return self::FAILURE;
        }

        $password = (string) $this->secret('New Owner recovery password (minimum 14 characters)');
        $confirm = (string) $this->secret('Confirm Owner recovery password');

        if (strlen($password) < 14) {
            $this->error('The Owner recovery password must be at least 14 characters.');
            return self::FAILURE;
        }

        if (! hash_equals($password, $confirm)) {
            $this->error('The passwords do not match.');
            return self::FAILURE;
        }

        $owner->forceFill([
            'email' => $email,
            'password' => Hash::make($password),
        ])->save();

        $this->newLine();
        $this->info('Owner recovery credentials updated.');
        $this->line('Owner person_code: '.$recovery->ownerPersonCode());
        $this->line('Recovery username: '.$owner->email);
        $this->line('Recovery page: /owner-recovery');
        $this->warn('The password is stored only as a one-way hash in the database. Keep the plaintext password in an approved password vault.');

        return self::SUCCESS;
    }
}
