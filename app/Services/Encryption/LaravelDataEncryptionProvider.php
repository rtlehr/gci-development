<?php

namespace App\Services\Encryption;

use App\Contracts\Encryption\DataEncryptionProvider;
use App\Exceptions\DataEncryptionException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Throwable;

class LaravelDataEncryptionProvider implements DataEncryptionProvider
{
    public function __construct(
        private readonly Encrypter $encrypter,
    ) {
    }

    public function encrypt(mixed $value, string $keyVersion): string
    {
        try {
            // Laravel's encrypter serializes non-string values and provides
            // authenticated encryption using the application's configured key.
            return $this->encrypter->encrypt($value);
        } catch (Throwable $exception) {
            throw new DataEncryptionException(
                'IRAD could not encrypt the requested value.',
                previous: $exception,
            );
        }
    }

    public function decrypt(string $payload, string $keyVersion): mixed
    {
        try {
            return $this->encrypter->decrypt($payload);
        } catch (DecryptException $exception) {
            throw new DataEncryptionException(
                'IRAD could not decrypt the requested value. The ciphertext may be invalid, tampered with, or encrypted with an unavailable key.',
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new DataEncryptionException(
                'IRAD could not decrypt the requested value.',
                previous: $exception,
            );
        }
    }
}
