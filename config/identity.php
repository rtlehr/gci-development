<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IRAD Identity Driver
    |--------------------------------------------------------------------------
    |
    | Both identity drivers resolve the same authoritative IRAD key:
    | people.person_code.
    |
    | When IRAD_IDENTITY_DRIVER is not set, the legacy behavior is preserved:
    | DEV_USER_ENABLED=true selects development; otherwise ADFS is selected.
    |
    */

    'driver' => env(
        'IRAD_IDENTITY_DRIVER',
        env('DEV_USER_ENABLED', false) ? 'development' : 'adfs'
    ),

    'drivers' => [
        'development' => [
            'provider' => App\Services\Identity\DevelopmentPersonCodeProvider::class,
        ],
        'adfs' => [
            'provider' => App\Services\Identity\AdfsPersonCodeProvider::class,

            /*
             * Exact trusted server variable populated by the ADFS/web-server
             * integration. IRAD intentionally does not fall back through a
             * list of alternate headers in production.
             */
            'person_code_source' => env('IRAD_ADFS_PERSON_CODE_SOURCE', 'HTTP_PERSON_CODE'),
        ],
    ],

    /*
     * Normal tests continue to use Laravel's actingAs()/guest behavior. The
     * ADFS middleware tests opt in explicitly through config().
     */
    'middleware_in_testing' => env('IRAD_IDENTITY_MIDDLEWARE_IN_TESTING', false),
];
