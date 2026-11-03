# Phase 5C.9 — Legacy Positions controller extraction

Starting checkpoint: `53f714a` (350 tests, 1,928 assertions).

## Goal and scope

Relocate the host legacy `PositionsController` implementation into the selected Positions module for the five legacy controller-backed `/positions` endpoints. This uses the `PositionOperations` interface introduced in 5C.8, without restoring any inheritance from the legacy concrete write service.

## Changes

- Add `Insite\Modules\Positions\Http\Controllers\PositionsController`, preserving the host controller behavior and method signatures, using `App\Http\Controllers\Controller` as the existing base controller.
- Point five existing module-owned legacy routes (`positions.preferences.save`, `positions.export.csv`, `positions.store`, `positions.update`, `positions.destroy`) to the module controller. The three simple legacy redirects and the `positions.candidates.store` endpoint are deliberately unchanged.
- Retain the host `App\Http\Controllers\PositionsController` untouched as the fallback controller when the Positions module is not enabled.
- Add three regression tests for host fallback, module controller action/middleware ownership, and fail-closed behavior after disabling a previously registered module.

## Compatibility boundaries

No migration, schema, permission, URI, route name, HTTP method, Vue component, or business-logic changes. The module controller continues to depend on legacy `App\Models` and shared list services; full domain decoupling is **not** claimed. The candidate creation endpoint remains explicitly owned by the host controller until its Recruitment boundary is reviewed.

This is a controlled code copy. Future fixes to the legacy and module controllers should be made consistently while both deployments are supported. The modern `/portal/positions` controller remains host implemented and is a separate extraction candidate.

## Verification

```powershell
php artisan test tests/Feature/Core/PositionsModuleLegacyControllerExtractionTest.php tests/Feature/Core/PositionsModuleRouteOwnershipTest.php tests/Feature/Core/PositionsOperationsBoundaryTest.php
php artisan test
npm run build
git status --short
```

Expected full suite: **353 tests**, compared with 350 at 5C.8. Confirm locally.

Manual checks: with current .env settings, open and edit a Position, create a Position, verify history and the Project Manager restriction. To exercise the module-owned controller paths in a selected installation, configure the Positions module in a test environment and verify legacy Position write/export actions. Do not toggle runtime production module selection while testing.
