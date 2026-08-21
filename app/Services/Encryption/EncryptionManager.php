<?php

namespace App\Services\Encryption;

use App\Contracts\Encryption\DataEncryptionProvider;
use App\Exceptions\DataEncryptionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class EncryptionManager
{
    private const PREFIX = 'irad';

    /** @var array<string, DataEncryptionProvider> */
    private array $providers = [];

    public function __construct(
        private readonly Container $container,
    ) {
    }

    /**
     * Encrypt a value with the currently configured IRAD data-encryption driver.
     *
     * Stored values are self-describing so a future driver/key rotation can
     * continue decrypting records written by an older provider.
     */
    public function encrypt(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $driver = $this->defaultDriver();
        $keyVersion = (string) config("data-encryption.drivers.{$driver}.key_version", '1');
        $envelopeVersion = (string) config('data-encryption.envelope_version', '1');
        $ciphertext = $this->provider($driver)->encrypt($value, $keyVersion);

        return implode(':', [
            self::PREFIX,
            'v'.$envelopeVersion,
            $driver,
            'k'.$keyVersion,
            $ciphertext,
        ]);
    }

    public function decrypt(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $envelope = $this->parseEnvelope($value);

        if ($envelope === null) {
            if ((bool) config('data-encryption.allow_plaintext_fallback', false)) {
                return $value;
            }

            throw new DataEncryptionException(
                'The stored value is not a recognized IRAD encrypted payload.'
            );
        }

        if ($envelope['version'] !== (string) config('data-encryption.envelope_version', '1')) {
            throw new DataEncryptionException(
                "Unsupported IRAD encryption envelope version [{$envelope['version']}]."
            );
        }

        return $this->provider($envelope['driver'])->decrypt(
            $envelope['payload'],
            $envelope['key_version'],
        );
    }

    public function isEncrypted(mixed $value): bool
    {
        return is_string($value) && $this->parseEnvelope($value) !== null;
    }

    public function provider(?string $driver = null): DataEncryptionProvider
    {
        $driver ??= $this->defaultDriver();

        if (isset($this->providers[$driver])) {
            return $this->providers[$driver];
        }

        $providerClass = config("data-encryption.drivers.{$driver}.provider");

        if (! is_string($providerClass) || ! class_exists($providerClass)) {
            throw new InvalidArgumentException(
                "Unsupported IRAD encryption driver [{$driver}]."
            );
        }

        $provider = $this->container->make($providerClass);

        if (! $provider instanceof DataEncryptionProvider) {
            throw new InvalidArgumentException(
                "Encryption provider [{$providerClass}] must implement ".DataEncryptionProvider::class.'.'
            );
        }

        return $this->providers[$driver] = $provider;
    }

    private function defaultDriver(): string
    {
        $driver = (string) config('data-encryption.driver', 'laravel');

        if ($driver === '') {
            throw new InvalidArgumentException('IRAD encryption driver cannot be blank.');
        }

        return $driver;
    }

    /**
     * @return array{version: string, driver: string, key_version: string, payload: string}|null
     */
    private function parseEnvelope(string $value): ?array
    {
        $parts = explode(':', $value, 5);

        if (count($parts) !== 5 || $parts[0] !== self::PREFIX) {
            return null;
        }

        if (! str_starts_with($parts[1], 'v') || ! str_starts_with($parts[3], 'k')) {
            return null;
        }

        $version = substr($parts[1], 1);
        $driver = $parts[2];
        $keyVersion = substr($parts[3], 1);
        $payload = $parts[4];

        if ($version === '' || $driver === '' || $keyVersion === '' || $payload === '') {
            return null;
        }

        return [
            'version' => $version,
            'driver' => $driver,
            'key_version' => $keyVersion,
            'payload' => $payload,
        ];
    }
}
