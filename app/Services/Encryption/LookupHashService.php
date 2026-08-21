<?php

namespace App\Services\Encryption;

use RuntimeException;

class LookupHashService
{
    public function hash(string|int $value): string
    {
        $key = (string) config('data-encryption.lookup_key', '');

        if ($key === '') {
            throw new RuntimeException('IRAD encryption lookup key is not configured.');
        }

        return hash_hmac('sha256', $this->normalize($value), $key);
    }

    public function normalize(string|int $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }
}
