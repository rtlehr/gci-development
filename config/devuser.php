<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local Development User Override
    |--------------------------------------------------------------------------
    |
    | This switcher is never available outside Laravel's "local" environment,
    | even when APP_DEBUG or DEV_USER_ENABLED is accidentally enabled.
    |
    */

    'enabled' => env('DEV_USER_ENABLED', false),
    'person_code' => env('DEV_PERSON_CODE'),
];
