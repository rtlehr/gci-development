# Milestone 5C.7 — Module-owned Position write service

## Baseline

Starting checkpoint `a7187f1`: Phase 5C.6 committed, 342 tests passing (1,903 assertions), successful frontend build.

## Change

The Positions module now supplies `Insite\Modules\Positions\Services\ModulePositionService` when the module is selected and transitional Positions integration is enabled. This relocates the business logic for eligible Project Managers, creating/updating Positions, and PositionActivity journaling under `modules/positions/Services/`.

The module service extends the host `App\Services\PositionService` type because existing host and Portal controllers type-hint that concrete class rather than an interface. The module provider binds `App\Services\PositionService` to the module implementation. This transitional subclass preserves existing injection and public-method compatibility while we extract controllers and introduce a proper module-agnostic contract.

When the module is unselected, the host concrete service resolves normally. When `legacy_positions_enabled=false`, the selected module does **not** register the business service override. The host class still exists for compatibility until the controller extraction is complete; it must not be interpreted as a Core-owned public Positions API in a Core-only deployment.

No HTTP routes, route names, permission checks, migrations, database tables, frontend components, or historical data are changed.

## Files

- New: `modules/positions/Services/ModulePositionService.php`
- Modified: `modules/positions/PositionsServiceProvider.php`
- New: `tests/Feature/Core/PositionsModuleWriteServiceExtractionTest.php`
- New: `docs/architecture/MILESTONE-5C7-POSITIONS-WRITE-SERVICE-EXTRACTION.md`

## Tests

```powershell
php artisan test tests/Feature/Core/PositionsModuleWriteServiceExtractionTest.php tests/Feature/Core/PositionsServiceOwnershipTest.php tests/Feature/ProjectManagerRoleEnforcementTest.php
php artisan test
npm run build
git status --short
```

Expected total if no additional tests were added: **347** (342 baseline + five new tests). Verify actual counts locally.

Manual browser check: open an existing Position; create a Position if practical; modify a Position and verify its history/activity; confirm the Project Manager eligibility restriction is unchanged. Leave `.env` unchanged for host fallback verification. Module-selected behavior is covered by focused tests.

## Technical debt / next

The host write service is intentionally retained as fallback, so its logic and the module implementation are currently duplicated. Keep both aligned while the transition continues. A later milestone should replace the concrete host type hint with a neutral contract, migrate remaining controllers, and remove this compatibility inheritance after dependent modules are separated.
