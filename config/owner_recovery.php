<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Owner recovery access
    |--------------------------------------------------------------------------
    |
    | Emergency local Owner authentication for situations where ADFS cannot
    | resolve the current network identity to an active Insite user. This is
    | deliberately separate from normal authentication: ordinary users never
    | receive local passwords and ADFS remains authoritative whenever it
    | supplies a valid mapped identity.
    |
    | The recovery password is stored only as the designated Owner user's
    | normal Laravel password hash in the database. Do not put a plaintext
    | recovery password in .env.
    |
    */

    'enabled' => env('IRAD_OWNER_RECOVERY_ENABLED', false),

    'owner_person_code' => env('IRAD_OWNER_RECOVERY_PERSON_CODE', '1111111'),

    'session_minutes' => (int) env('IRAD_OWNER_RECOVERY_SESSION_MINUTES', 30),
];
