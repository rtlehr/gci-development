<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Initial-install bootstrap login
    |--------------------------------------------------------------------------
    |
    | This is NOT a fallback authentication method. It exists only so the
    | designated Owner can configure a brand-new installation before the
    | enterprise identity provider is available. The database installation
    | state permanently disables it after setup is completed.
    |
    */

    'enabled' => env('IRAD_BOOTSTRAP_LOGIN_ENABLED', false),

    'owner_person_code' => env('IRAD_BOOTSTRAP_OWNER_PERSON_CODE', '1111111'),

    /*
     * Existing Fortify auth tests can continue exercising Laravel's stock
     * password path unless a bootstrap-security test explicitly opts in.
     */
    'enforce_in_testing' => env('IRAD_BOOTSTRAP_LOGIN_ENFORCE_IN_TESTING', false),
];
