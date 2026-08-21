<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IRAD Data Encryption Driver
    |--------------------------------------------------------------------------
    |
    | Laravel's native authenticated encryption is the default. Application
    | code should use EncryptionManager / EncryptedValue rather than Laravel's
    | Crypt facade directly so a future KMS, HSM, Vault, or approved provider
    | can be introduced without rewriting domain models and services.
    |
    */

    'driver' => env('IRAD_ENCRYPTION_DRIVER', 'laravel'),

    /*
     * Version of IRAD's self-describing ciphertext envelope. This is separate
     * from the underlying provider key version.
     */
    'envelope_version' => '1',

    /*
     * Disabled by default. A later staged data migration may temporarily turn
     * this on so encrypted casts can read legacy plaintext while records are
     * being converted. Production should return it to false after migration.
     */
    'allow_plaintext_fallback' => env('IRAD_ENCRYPTION_ALLOW_PLAINTEXT_FALLBACK', false),

    'drivers' => [
        'laravel' => [
            'provider' => App\Services\Encryption\LaravelDataEncryptionProvider::class,

            /*
             * Logical version recorded with newly encrypted values. Laravel's
             * provider uses APP_KEY (and Laravel-supported previous keys for
             * rotation); future providers can use this version to select an
             * external key.
             */
            'key_version' => env('IRAD_ENCRYPTION_KEY_VERSION', '1'),
        ],
    ],
];
