<?php

return [
    'enabled' => (bool) env('USER_EVENT_LOG_ENABLED', true),

    /*
     * 0 keeps audit events indefinitely. Set USER_EVENT_LOG_RETENTION_DAYS
     * to a positive number to enable automatic pruning.
     */
    'retention_days' => (int) env('USER_EVENT_LOG_RETENTION_DAYS', 0),

    /*
     * These requests are development/system mechanics rather than meaningful
     * user activity. Wildcards use Laravel Str::is() matching.
     */
    'ignore_routes' => [
        'dev.switch-user',
        'dev.clear-user',
        'debugbar.*',
        'ignition.*',
    ],
];
