<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transport Security
    |--------------------------------------------------------------------------
    |
    | IRAD decrypts protected database fields only on the application server.
    | HTTPS/TLS protects those plaintext values while they travel from Laravel
    | to an authorized browser. Production defaults to HTTPS enforcement.
    |
    */
    'enforce_https' => env('IRAD_ENFORCE_HTTPS', env('APP_ENV') === 'production'),

    'hsts' => [
        'enabled' => env('IRAD_HSTS_ENABLED', env('APP_ENV') === 'production'),
        'max_age' => (int) env('IRAD_HSTS_MAX_AGE', 31536000),
        'include_subdomains' => env('IRAD_HSTS_INCLUDE_SUBDOMAINS', true),
        'preload' => env('IRAD_HSTS_PRELOAD', false),
    ],
];
