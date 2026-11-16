# Milestone 5C.19 — Positions dashboard service ownership

## Objective
When the Positions module is selected, resolve `App\Contracts\Dashboard\WorkforceDashboardProvider` to a module-owned implementation instead of `App\Services\ProjectManagerDashboardService`.

## Changes
- Add `Insite\Modules\Positions\Services\ModuleWorkforceDashboardService` as an independent implementation of the Core workforce dashboard contract.
- Change `PositionsServiceProvider` to bind the contract to the module implementation when activated with legacy integration enabled.
- Keep the host `ProjectManagerDashboardService` untouched for installations without the Positions module selected. Keep `EmptyWorkforceDashboardProvider` for Core-only integration-disabled installations.
- Add contract, binding, and result-parity regression tests; update the existing service ownership assertion.

## Deliberate transitional boundaries
- This change deliberately preserves the existing dashboard query and metric logic to avoid changing staffing statuses, workflow progress, sorting, or return payloads.
- The module implementation still accesses host Eloquent models (`Position`, `Candidate`, `Person`, `User`) while model ownership and cross-module contracts remain future milestones. It does **not** yet make Positions completely independent.
- Existing `routes/dashboard-integration.php` and `routes/dashboard-pmo-integration.php` still reference the legacy concrete service; they are not automatically redirected by the interface binding. Their usage should be reviewed during integration-boundary work.
- `INSITE_LEGACY_POSITIONS_ENABLED=false` continues to suppress workforce provider binding in the module; retained for the current controlled Core-only behavior.

## Verification

```powershell
php artisan test tests/Feature/Core/PositionsModuleWorkforceDashboardOwnershipTest.php tests/Feature/Core/PositionsServiceOwnershipTest.php tests/Feature/Core/WorkforceDashboardBoundaryTest.php tests/Feature/Portal/PortalPmoDashboardTest.php
php artisan test
npm run build
git status --short
```

Browser check: PM, PMO and Candidate dashboards show the same cards, counts, filters and workflow links with Positions selected. Repeat the legacy/Core-only configuration checks as appropriate.
