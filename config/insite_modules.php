<?php

// Module selection is an installation-level deployment setting, not a per-user permission.
// Empty by default. Example: INSITE_ENABLED_MODULES=demo,travel
// After changing the setting in a deployed environment, rebuild configuration and
// route caches as part of a controlled release.
$selected = array_map('trim', explode(',', (string) env('INSITE_ENABLED_MODULES', '')));

return [
    // Independently versioned Core module contract, not the Laravel framework version.
    'core_version' => '1.0.0',
    // Transitional only: legacy Positions code is still in the host application.
    // Set false only for controlled Core-boundary tests, not normal deployments.
    'legacy_positions_enabled' => filter_var(env('INSITE_LEGACY_POSITIONS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'enabled' => array_values(array_filter($selected, static fn (string $id): bool => $id !== '')),
];
