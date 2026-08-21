<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EncryptionHealthCheck extends Command
{
    protected $signature = 'irad:encryption-health {--strict : Return a failure code when production safeguards are not satisfied}';

    protected $description = 'Check IRAD encryption and transport-security configuration.';

    public function handle(): int
    {
        $issues = [];
        $warnings = [];

        $driver = (string) config('data-encryption.driver');
        $lookupKey = (string) config('data-encryption.lookup_key');
        $appKey = (string) config('app.key');

        $this->line('IRAD encryption driver: '.$driver);
        $this->line('Encryption key version: '.(string) config("data-encryption.drivers.{$driver}.key_version", 'unknown'));
        $this->line('HTTPS enforcement: '.(config('security.enforce_https') ? 'enabled' : 'disabled'));
        $this->line('HSTS: '.(config('security.hsts.enabled') ? 'enabled' : 'disabled'));
        $this->line('Secure session cookie: '.(config('session.secure') ? 'enabled' : 'disabled'));
        $this->line('Session encryption: '.(config('session.encrypt') ? 'enabled' : 'disabled'));

        if (config('data-encryption.allow_plaintext_fallback')) {
            $issues[] = 'IRAD_ENCRYPTION_ALLOW_PLAINTEXT_FALLBACK must be false after data migration.';
        }

        if ($lookupKey === '') {
            $issues[] = 'No lookup-hash key is configured.';
        } elseif (hash_equals($appKey, $lookupKey)) {
            $warnings[] = 'The lookup-hash key currently falls back to APP_KEY. Use a dedicated IRAD_ENCRYPTION_LOOKUP_KEY in production.';
        }

        if (app()->isProduction()) {
            if (! config('security.enforce_https')) {
                $issues[] = 'HTTPS enforcement is disabled in production.';
            }

            if (! config('security.hsts.enabled')) {
                $issues[] = 'HSTS is disabled in production.';
            }

            if (! config('session.secure')) {
                $issues[] = 'SESSION_SECURE_COOKIE must be true in production.';
            }

            if (! config('session.encrypt')) {
                $issues[] = 'SESSION_ENCRYPT should be true in production.';
            }

            if ($lookupKey !== '' && hash_equals($appKey, $lookupKey)) {
                $issues[] = 'Production should use a dedicated IRAD_ENCRYPTION_LOOKUP_KEY instead of APP_KEY.';
            }
        }

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($issues as $issue) {
            $this->error($issue);
        }

        if ($issues === []) {
            $this->info('Encryption and transport-security configuration looks ready.');

            return self::SUCCESS;
        }

        if ($this->option('strict') || app()->isProduction()) {
            return self::FAILURE;
        }

        $this->warn('Configuration has issues to resolve before production deployment.');

        return self::SUCCESS;
    }
}
