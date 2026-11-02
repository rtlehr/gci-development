# Phase 5C.2 — Positions Service Ownership

**Baseline:** `e63bec4` (Phase 5C.1). **Scope:** provider registration only; no route, controller, model, database, or UI relocation.

## Responsibility transfer

- When `positions` is **selected** in `INSITE_ENABLED_MODULES` and `INSITE_LEGACY_POSITIONS_ENABLED=true`, `Insite\Modules\Positions\PositionsServiceProvider` registers the existing `PersonPositionRelationships` adapter and the `WorkforceDashboardProvider` implementation.
- When the module is **not selected**, `AppServiceProvider` preserves the existing legacy relationship adapter. The Core dashboard retains its transitional configuration-selected provider. This is required while legacy Positions routes and models still live in the host.
- When the legacy integration is **disabled**, the module provider registers neither service. Core's dashboard fallback remains the empty workforce provider. Existing legacy routes have **not** been made Core-only safe: do not disable Positions in normal development or production yet.
- The module provider currently references `App\Services\Positions\LegacyPositionsBinding` and `App\Services\ProjectManagerDashboardService`. **Ownership of registration is not ownership of implementation.** Those classes will migrate only after the remaining dependencies are addressed.

## Deployment notes

Do not edit `.env` for ordinary installations. The `positions` module remains inactive by default. When selected, ensure the existing legacy setting is enabled. Never turn off the legacy integration as a simulation of a fully extracted Positions deployment. Core routes and data are not yet separated from all business models.

No database migration, historical migration tracking, or permission changes are included.

## Verify

```powershell
php artisan test tests/Feature/Core/PositionsServiceOwnershipTest.php tests/Feature/Core/PositionsModuleFoundationTest.php tests/Feature/Core/PersonPositionsOptionalBindingTest.php tests/Feature/Core/WorkforceDashboardBoundaryTest.php
php artisan test
npm run build
git status --short
```

Manually open Positions, People assignment details, and the PMO dashboard with the default legacy integration enabled. Do not commit until tests and those views pass.

## Next

Phase 5C.3 can move the relationship adapter implementation into `modules/positions/` under the existing Core contract, retaining the old class temporarily if needed. Dashboard calculations, imports, audit links, routes and migration ownership remain separate extraction work.
