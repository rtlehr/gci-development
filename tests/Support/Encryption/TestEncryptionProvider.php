<?php

namespace Tests\Support\Encryption;

use App\Contracts\Encryption\DataEncryptionProvider;
use App\Exceptions\DataEncryptionException;

class TestEncryptionProvider implements DataEncryptionProvider
{
    public function encrypt(mixed $value, string $keyVersion): string
    {
        return base64_encode(serialize([
            'key' => $keyVersion,
            'value' => $value,
        ]));
    }

    public function decrypt(string $payload, string $keyVersion): mixed
    {
        $decoded = unserialize(base64_decode($payload, true));

        if (! is_array($decoded) || ($decoded['key'] ?? null) !== $keyVersion) {
            throw new DataEncryptionException('Test provider could not decrypt value.');
        }

        return $decoded['value'];
    }
}