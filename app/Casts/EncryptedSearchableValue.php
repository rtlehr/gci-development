<?php

namespace App\Casts;

use App\Services\Encryption\EncryptionManager;
use App\Services\Encryption\LookupHashService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class EncryptedSearchableValue implements CastsAttributes
{
    public function __construct(private readonly string $lookupColumn)
    {
        if ($this->lookupColumn === '') {
            throw new InvalidArgumentException('EncryptedSearchableValue requires a lookup column.');
        }
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        return app(EncryptionManager::class)->decrypt((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [
                $key => null,
                $this->lookupColumn => null,
            ];
        }

        $manager = app(EncryptionManager::class);
        $plain = $manager->isEncrypted($value)
            ? (string) $manager->decrypt((string) $value)
            : (string) $value;

        return [
            $key => $manager->isEncrypted($value) ? (string) $value : $manager->encrypt($plain),
            $this->lookupColumn => app(LookupHashService::class)->hash($plain),
        ];
    }
}
