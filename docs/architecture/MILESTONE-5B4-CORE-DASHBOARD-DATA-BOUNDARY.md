# Milestone 5B.4 — Core dashboard data boundary

## Goal
The portal dashboard controller must not instantiate or type-hint the legacy Positions/Recruitment dashboard implementation. Its optional workforce panels are provided through a Core-owned contract.

## Changes
- `App\Contracts\Dashboard\WorkforceDashboardProvider`: read-only, dashboard-shaped interface for PMO, project manager, candidate progress and staffing metadata.
- `App\Services\ProjectManagerDashboardService`: unchanged workforce calculations; now implements the interface as the legacy provider.
- `App\Services\Dashboard\EmptyWorkforceDashboardProvider`: safe empty collections, columns and zero staffing totals; accesses no Positions/Recruitment models or tables.
- `AppServiceProvider`: selects the provider from `insite_modules.legacy_positions_enabled`; legacy behavior remains default.
- `DashboardController`: depends on the contract for dashboard, preference and CSV actions; hides workforce-specific panels when legacy integration is disabled.

## Compatibility
No routes, Vue components, database migrations, existing workforce calculation algorithms or permission names have changed. Other callers of `ProjectManagerDashboardService` remain compatible. The setting is transitional, not the final independent module activation mechanism.

## Limitations
This **does not** establish a fully Core-only installation. Other routes, services, resources, dashboard staffing actions, policies, and migrations may still refer to legacy workforce models. Specifically, staffing preference/CSV routes are not yet module-gated. The empty provider is intended for the ordinary Core dashboard, not as an authorization mechanism for other endpoints. Tickets and Alerts are still legacy direct dashboard dependencies and must be addressed in a separate milestone.

## Verification (development/testing database only)
1. `php artisan test tests/Feature/Core/WorkforceDashboardBoundaryTest.php tests/Feature/Portal/PortalPmoDashboardTest.php tests/Feature/DashboardTest.php`
2. `php artisan test`
3. `npm run build`
4. Browser: PMO dashboard, project manager dashboard, candidate dashboard, staffing preferences and CSV export with the setting at its default `true` value.
5. Verify `git status --short`; commit only after checks pass.

Expected new test count: three. Baseline from 5B.3: 319, so anticipated full-suite total is 322 if unchanged. Do not turn off legacy integration in regular development or production installations yet.
