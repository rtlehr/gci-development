<?php

/*
|--------------------------------------------------------------------------
| Development User Configuration
|--------------------------------------------------------------------------
|
| This configuration is ONLY intended for local development/testing.
|
| In production:
| - Set DEV_USER_ENABLED=false
| - The application should resolve the current user from ADFS
|   or another enterprise authentication provider.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Development User Override
    |--------------------------------------------------------------------------
    |
    | When enabled, the application can impersonate a user using the
    | configured person_code or session-based dev user switching.
    |
    */

    'enabled' => env('DEV_USER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Default Development Person Code
    |--------------------------------------------------------------------------
    |
    | Used when development impersonation is enabled and no session override
    | has been selected.
    |
    */

    'person_code' => env('DEV_PERSON_CODE'),

];