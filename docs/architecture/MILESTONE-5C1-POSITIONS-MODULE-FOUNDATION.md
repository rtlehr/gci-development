# Milestone 5C.1 — Positions Module Foundation

**Starting checkpoint:** `5cabcdb` (Phase 5B.5 reviewed and committed).

## Purpose

Make Positions a discoverable, versioned, selectively activated **module shell** without transferring any existing production functionality or data. This is an additive architecture milestone, **not** completion of the Positions extraction.

## New package files

- `modules/positions/module.json` — module ID `positions`, version `1.0.0`, compatible Core `^1.0.0`, no module dependencies, declared provider.
- `modules/positions/PositionsServiceProvider.php` — intentionally inert Laravel service provider.
- `modules/positions/routes/web.php` — intentionally empty; avoids accidentally claiming legacy host routes.
- `tests/Feature/Core/PositionsModuleFoundationTest.php` — discovery, disabled-by-default, explicit activation, and incompatible Core rejection.

No existing files are modified. No tables or historical migrations are moved. No provider registers duplicate routes, permissions, models, or service bindings.

## Configuration distinction

`INSITE_ENABLED_MODULES=positions` activates **the new shell only**. It does **not** disable the existing host application's Positions routes or move any functionality to the module.

`INSITE_LEGACY_POSITIONS_ENABLED` is a separate, temporary compatibility switch introduced in Phase 5B.2. Its default `true` should remain unchanged for normal testing. The two switches are **not yet wired as equivalents**; both are part of the transition.

The shell is intentionally disabled by default. For this milestone, leave your normal `.env` unchanged. The focused test selects and activates it in isolation.

## Dependency and activation model

The existing Core `ModuleCatalog` discovers the manifest; `ModuleDependencyResolver` checks module selection and dependencies; the read-only `ModuleDeploymentPreflight` checks Core-version compatibility before deployment; `ModuleActivator` registers its provider and loads its route file when routes are not cached. Existing Composer PSR-4 mapping `Insite\\Modules\\` to `modules/` supports this provider; no autoload edit is required.

## Verification

Run from the application root:

```powershell
php artisan test tests/Feature/Core/PositionsModuleFoundationTest.php
php artisan test
npm run build
git status --short
```

Expected after applying to the last committed checkpoint: **326 tests** total (previous baseline 322 plus 4 new). Test assertion counts may change. Manual smoke test with regular `.env`: People, Positions detail and assignments, Candidates, PMO dashboard. Because no production route/UI behavior is changed, no dedicated shell UI is expected.

## Remaining blockers before extraction

1. Move Positions models, services, and controllers only in tested, small steps with compatibility adapters.
2. Separate legacy Positions route registration from unconditional host route loading; preserve route names, URLs, auth and permission middleware, and route caching.
3. Extract shared import adapters and audit link resolution without forcing Core to reference Positions or Recruitment models.
4. Preserve historical migrations in their current migration history. Introduce module-owned migrations for *new* schema and independently validate fresh installs and upgrades.
5. Retire `INSITE_LEGACY_POSITIONS_ENABLED` only after all relevant runtime boundaries follow module activation and a Core-only installation has passed its tests.

## Scope guard

Do **not** deploy the shell as a replacement for legacy Positions. Do **not** delete tables, re-run migrations against production data, or disable legacy integration based solely on shell activation.

**Current limitation:** Runtime activation itself does not enforce the Core-version constraint. Run deployment preflight before enabling modules in an installation; tightening that enforcement is a separate Core change.
