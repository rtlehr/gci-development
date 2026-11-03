# Milestone 5C.8 — Independent Positions operations contract

Starting checkpoint: `a97a90c` (347 tests, 1,915 assertions).

## Goal

Remove the inheritance dependency introduced in 5C.7: previously `ModulePositionService` extended the host concrete `App\Services\PositionService`. Controller type-hints forced this relationship. The new boundary is `App\Contracts\Positions\PositionOperations`.

## Changes

- New host-level interface with the four supported methods: `projectManagers`, `assertEligibleProjectManager`, `create`, and `update`. It retains existing `App\Models\Position` and `Collection` types as **temporary compatibility types**.
- Existing `App\Services\PositionService` implements the interface as the fallback for unselected installations. No business logic is removed or behavior changed.
- Module-owned `ModulePositionService` now directly implements the interface, with no inheritance from host PositionService. It preserves the write behavior extracted during 5C.7.
- The module provider binds the interface to `ModulePositionService` only when activated with transitional Positions integration enabled; the host AppServiceProvider binds the interface to the legacy implementation only when the module is unselected and legacy integration enabled.
- Both modern Portal and legacy Position controllers use the interface for method injection and explicit service resolution.
- Existing 5C.7 tests updated to assert interface behavior, plus 3 focused contract tests.

## Compatibility and limits

This milestone does not alter route names, permissions, Position schema, migrations, workflow behavior, HTTP responses, or frontend components. The actual `Position` Eloquent model and controllers still live in the host; consequently the interface is a **transitional architecture boundary**, not yet a Core-only domain abstraction. The two independent service implementations intentionally share behavior and should stay synchronized until host fallback is retired.

Host-only installations with `INSITE_LEGACY_POSITIONS_ENABLED=false` do not register the contract. Config selection and module activation are installation settings; changing them in a live worker requires controlled deployment and rebuilding caches.

## Verify

```powershell
php artisan test tests/Feature/Core/PositionsOperationsBoundaryTest.php tests/Feature/Core/PositionsModuleWriteServiceExtractionTest.php tests/Feature/Portal/PortalPositionTest.php tests/Feature/ProjectManagerRoleEnforcementTest.php
php artisan test
npm run build
git status --short
```

Expected total: 350 tests, based on the previously reported 347 plus 3 new tests. Confirm counts locally before committing.

Manual verification: create/edit an existing Position with the normal `.env`, verify Project Manager eligibility and Position activity history. The module-selected behavior is tested with activation tests.

## Next

Extract more controllers and resolve the remaining Positions domain dependencies, but keep Recruitment/Candidates ownership separate. Eventually move Position entities behind module boundaries and remove transitional host fallback only after dependent workflows are migrated.
