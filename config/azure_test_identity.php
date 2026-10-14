<?php

return [
    // Opt-in ONLY for the isolated Azure test App Service; never for production sites.
    'enabled' => env('IRAD_AZURE_TEST_IDENTITY_ENABLED', false),
    // Must exactly match Azure's WEBSITE_SITE_NAME runtime value.
    'app_name' => env('IRAD_AZURE_TEST_APP_NAME', ''),
    'runtime_app_name' => env('WEBSITE_SITE_NAME', ''),
    // Entra tenant-specific Object ID of the one authorized test operator.
    'entra_object_id' => env('IRAD_AZURE_TEST_ENTRA_OBJECT_ID', ''),
];
