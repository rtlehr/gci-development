# Phase 5C.11 — Positions List Definition Ownership

## Prerequisites

Apply after Phase 5C.10, which extracted the modern Portal Positions controller, and after Phase 5C.9, which extracted the legacy Positions controller. **Confirm Phase 5C.10 is tested and committed before treating this milestone as complete.**

## Objective

Reduce the module controllers' dependency on host-owned presentation metadata. Move the existing Positions list definition (column keys, labels, database fields, sorting, visibility, searching, and export metadata) into a module-owned namespace while preserving behavior.

## Changes

- Add `Insite\Modules\Positions\Support\ListDefinitions\PositionsDefinition` with the existing definition unchanged except for its namespace.
- Point the module-owned legacy and Portal Positions controllers to the new class. Both controllers continue using the same `CustomFieldListService`, `ListEngine`, and `ListExportService` as before.
- Retain `App\Support\ListDefinitions\PositionsDefinition` unchanged so installations without Positions selected continue to use the host implementation.
- Add three regression tests for host/module parity, exact column metadata, and controller imports.

## Compatibility

No changes to route paths, middleware, permissions, model schema, view names, or Vue components. No migrations. This is a **definition ownership** milestone, not a claim that Positions models, list infrastructure, or Inertia pages are independent of Core. Host and module definitions currently match deliberately. Future changes to shared list behavior should be assessed for both installation modes.

## Verification (PowerShell)

```powershell
php artisan test tests/Feature/Core/PositionsModuleListDefinitionOwnershipTest.php tests/Feature/Core/PositionsModulePortalControllerExtractionTest.php tests/Feature/Core/PositionsModuleLegacyControllerExtractionTest.php tests/Feature/Portal/PortalPositionTest.php
php artisan test
npm run build
git status --short
```

If Phase 5C.10's three tests are installed and the prior suite was 353 passing, **359 tests** are expected in full once this milestone's three tests are added. The supplied patch has been syntax-checked and ZIP-checked, but Laravel tests must run against the local project and `irad_testing` database.

Browser: inspect Portal Positions list and CSV export; check default columns, show/hide columns, search, sorting, and saved list preferences. For module-specific runtime verification, select `positions` in a test installation only, not production.
