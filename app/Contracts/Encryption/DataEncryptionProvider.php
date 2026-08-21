<?php

namespace App\Contracts\Encryption;

interface DataEncryptionProvider
{
    /**
     * Encrypt an application value for storage.
     *
     * The key version is supplied by IRAD so future providers (KMS/HSM/etc.)
     * can select the appropriate external key without changing callers.
     */
    public function encrypt(mixed $value, string $keyVersion): string;

    /**
     * Decrypt an application value previously encrypted by this provider.
     */
    public function decrypt(string $payload, string $keyVersion): mixed;
}
