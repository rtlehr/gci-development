<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

class InsiteIdentityCheck extends Command
{
    protected $signature = 'insite:identity-check
        {--person-code= : Validate a specific person_code against the Insite database}
        {--show-code : Display the supplied person_code instead of masking it}';

    protected $description = 'Validate Insite Portal identity configuration and optional person_code mapping';

    public function handle(): int
    {
        $driver = trim((string) config('identity.driver'));
        $source = trim((string) config('identity.drivers.adfs.person_code_source'));
        $personCode = $this->option('person-code');

        $this->newLine();
        $this->info('Insite Portal Identity Check');
        $this->line(str_repeat('-', 42));
        $this->line('Environment:            '.app()->environment());
        $this->line('Identity driver:        '.($driver !== '' ? $driver : '[blank]'));

        if ($driver === 'adfs') {
            $this->line('Configured source:      '.($source !== '' ? $source : '[blank]'));
        }

        if (! in_array($driver, ['development', 'adfs'], true)) {
            $this->error('Status: INVALID - unsupported identity driver.');

            return self::FAILURE;
        }

        if ($driver === 'development' && app()->isProduction()) {
            $this->error('Status: INVALID - the development identity driver is not allowed in production.');

            return self::FAILURE;
        }

        if ($driver === 'adfs' && $source === '') {
            $this->error('Status: INVALID - IRAD_ADFS_PERSON_CODE_SOURCE is blank.');

            return self::FAILURE;
        }

        if ($driver === 'adfs') {
            $this->line('Upstream claim test:    Not available from CLI');
            $this->comment('The live web-server claim must be verified through an HTTP request. See docs/INSITE_IDENTITY_INTEGRATION.md.');
        }

        if ($personCode === null || trim((string) $personCode) === '') {
            $this->newLine();
            $this->info('Status: CONFIGURATION READY');
            $this->comment('Use --person-code=<value> to validate that an enterprise identifier maps to a Person/User record.');

            return self::SUCCESS;
        }

        $personCode = trim((string) $personCode);
        $displayCode = $this->option('show-code') ? $personCode : $this->mask($personCode);

        $this->newLine();
        $this->line('Person code supplied:   '.$displayCode);

        try {
            $person = Person::findByPersonCode($personCode);
        } catch (Throwable $exception) {
            $this->error('Database lookup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! $person) {
            $this->line('Person found:           No');
            $this->error('Status: NOT READY - no Person matches that person_code.');

            return self::FAILURE;
        }

        $this->line('Person found:           Yes');
        $this->line('Person ID:              '.$person->id);
        $this->line('Linked user_id:         '.($person->user_id ?: '[none]'));

        if (! $person->user_id) {
            $this->error('Status: NOT READY - Person exists but is not linked to a User.');

            return self::FAILURE;
        }

        $user = User::query()->find($person->user_id);

        if (! $user) {
            $this->line('User found:             No');
            $this->error('Status: NOT READY - linked User record is missing.');

            return self::FAILURE;
        }

        $this->line('User found:             Yes');
        $this->line('User ID:                '.$user->id);

        $roles = method_exists($user, 'roles')
            ? $user->roles()->pluck('name')->filter()->values()->all()
            : [];

        $this->line('Roles:                  '.($roles !== [] ? implode(', ', $roles) : '[none]'));
        $this->newLine();
        $this->info('Status: READY');

        return self::SUCCESS;
    }

    private function mask(string $value): string
    {
        $length = mb_strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return mb_substr($value, 0, 2)
            .str_repeat('*', max(1, $length - 4))
            .mb_substr($value, -2);
    }
}
