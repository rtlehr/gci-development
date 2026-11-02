# Insite Portal — Phase 5B.2: Optional Legacy Positions Binding

## Objective

Make the transitional `PersonPositionRelationships` binding conditional while the existing Positions business code remains in the host application. The default is unchanged: legacy Positions remains enabled, so current routes and user workflows continue to operate.

## Changes

- `LegacyPositionsBinding` checks installation configuration and the existence of the legacy Position model, PositionAssignment model, and adapter before registering the contract.
- `AppServiceProvider` delegates legacy contract registration to this helper.
- `INSITE_LEGACY_POSITIONS_ENABLED` defaults to `true`. `false` is **only an interim boundary-test setting**; it is not equivalent to a supported Core-only customer installation.
- Ordinary `Person` attributes and Core relationships do not require the Positions contract. If code explicitly calls `Person::positions()` or `Person::assignments()` without an adapter, it receives a descriptive exception instead of an invented/incorrect empty Eloquent relationship.
- No route, permission, schema, or Vue behavior is changed. The legacy adapter is not yet moved to `modules/positions`.

## Automated validation

1. Unzip into `C:\Development\Vue\IRAD` preserving paths.
2. `php artisan test tests/Feature/Core/PersonPositionsOptionalBindingTest.php tests/Feature/Core/PersonPositionsContractTest.php`
3. `php artisan test` (baseline 311, plus 4 new = **315 expected**).
4. `npm run build`
5. `git status --short`
6. Browser smoke check: open People list, person details/assignments, and Position details with the normal default configuration.

**Do not set `INSITE_LEGACY_POSITIONS_ENABLED=false` in your normal development or production environment.** Existing Positions controllers, dashboards, routes, and shared features are not yet extracted and may continue referencing the legacy models even with this binding disabled.

## Remaining work

- Move adapter into a proper `modules/positions` provider and register it only when enabled.
- Replace Core callers that assume Positions always exists; isolate routes, dashboards, and imports.
- Test a true Core-only application with module files absent, not merely the disabled adapter.
- Keep historical migration records and URLs intact.
