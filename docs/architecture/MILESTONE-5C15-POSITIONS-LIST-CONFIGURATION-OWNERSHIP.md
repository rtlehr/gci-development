# Phase 5C.15 — Positions List Configuration Ownership

## Baseline

Built against the supplied `5c-14.zip` snapshot with Phase 5C.14 applied (commit `a5bd75e`). Previous verified suite: **368 tests / 2,308 assertions**.

## Scope

- Adds `Insite\Modules\Positions\Services\PositionListConfiguration` as the sole module implementation of the augmented Positions list definition and status filtering.
- Both module controllers now use the shared definition for **index and CSV export**, and a single callable for their status filter.
- `PositionListPreferences` now obtains its permitted column keys from the same configuration service.
- The host controller and host list definition are intentionally unchanged for installations without the Positions module.
- Updates the prior ownership test to check the new service boundary instead of a direct controller import.

## Deliberate nonchanges

No route names, authentication, authorization, data model, schema, Vue, or CSV response contract changes. Portal scoping via `PortalWorkforceAccessService` remains applied separately from the shared status filter. `CustomFieldListService` remains an application-level dependency pending a future explicit interface/ownership decision.

## Verification

```powershell
php artisan test tests/Feature/Core/PositionsModuleListConfigurationTest.php tests/Feature/Core/PositionsModuleListDefinitionOwnershipTest.php tests/Feature/Core/PositionsModuleListPreferencesTest.php tests/Feature/Portal/PortalPositionTest.php
php artisan test
npm run build
git status --short
```

Expected full-suite count: **371 tests**, assuming 368 baseline and the 3 new tests. Manually check index status filtering, CSV export with status filtering, and Column Settings save/reset. Verify on a module-enabled installation as well as the host fallback before deployment.
