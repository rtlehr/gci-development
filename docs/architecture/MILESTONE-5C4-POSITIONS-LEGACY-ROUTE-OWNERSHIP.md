# Milestone 5C.4 — Positions legacy route ownership

## Scope
The selected Positions module now registers the existing `/positions` (legacy) route set from `modules/positions/routes/web.php`. Core `routes/web.php` continues to load `routes/position.php` only while the Positions module is *not* selected. This is transitional; the legacy business code is still present in the host.

## Compatibility and safety
- Existing URLs, names, HTTP verbs, controller actions and permission middleware remain identical.
- Module routes add `insite-module:positions` as an outer middleware. Its resolver checks the live selected module configuration, so stale cached routes fail closed after deactivation.
- Host legacy routes continue working in an unselected installation to avoid disrupting the deployed portal.
- `INSITE_LEGACY_POSITIONS_ENABLED=false` prevents the selected module from publishing legacy actions until the underlying business services have been extracted. Do not use it for normal installations yet.
- `/portal/positions`, Job Titles, Position Assignments, Recruitment and Resumes routes remain host-owned. Route caching and route-name collisions must be validated before their separate migration.
- No migration, database table, permission, controller, or Vue page changes.

## Verification
Run:

```powershell
php artisan test tests/Feature/Core/PositionsModuleRouteOwnershipTest.php tests/Feature/Core/ModuleActivationTest.php tests/Feature/Portal/PortalPositionTest.php
php artisan test
npm run build
php artisan route:list --name=positions
```

Review `/positions` redirects, `/portal/positions` list/detail/create/update authorization, and existing person assignment displays. Do not commit until all tests and browser checks succeed.

## Remaining work
Move portal Positions and Job Titles routes separately, with Recruitment candidate route ownership explicitly resolved. Preserve legacy route names and authorization. Test fresh enabled deployment with route cache and an existing installation with cache rebuild; don't move historical migrations.
