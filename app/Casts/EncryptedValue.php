<?php

namespace App\Casts;

use App\Services\Encryption\EncryptionManager;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Reusable IRAD encrypted Eloquent cast.
 *
 * No models use this cast in Step 1. Sensitive fields will be introduced in a
 * later migration/classification step after their search/filter requirements
 * have been reviewed.
 */
class EncryptedValue implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        return app(EncryptionManager::class)->decrypt((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $manager = app(EncryptionManager::class);

        // Makes migration/import tooling idempotent if it passes through an
        // already-encrypted raw value.
        if (is_string($value) && $manager->isEncrypted($value)) {
            return $value;
        }

        return $manager->encrypt($value);
    }
}
