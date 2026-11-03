# Phase 5C.5 — Portal Positions Route Ownership

## Baseline and intent

Starting checkpoint: `76be997` (Phase 5C.4). Relocate **route definitions only** for the 15 `/portal/positions` endpoints into `modules/positions/routes/web.php`, without changing the URLs, names, HTTP methods, controllers, permissions, database schema, or Vue UI.

## Ownership behavior

- **No Positions module selected:** `routes/portal.php` continues to register the original `/portal/positions` endpoints under the `auth`, `permission:access_portal`, and per-action permission middleware. This maintains existing installations.
- **Positions selected:** the host skips those 15 endpoints; `modules/positions/routes/web.php` registers them using the same route signatures and adds `insite-module:positions` to the original authentication and authorization guards. The module activator refreshes name lookups after dynamic activation.
- **Selection disabled after registration / stale route cache:** module middleware checks the current resolved enabled set and returns 404. Configuration and route caches must be regenerated during deployments that change `INSITE_ENABLED_MODULES`.
- **Transitional legacy integration disabled:** the selected module route file returns early, as in Phase 5C.4; this is an internal Core-boundary test state, not a supported business deployment.

## Scope and limitations

This is not a full Positions extraction. Portal Positions controllers, models, frontend pages, and shared candidate/skill/task controllers stay in the host. Other portal endpoints (`job-titles`, `candidates`, staffing dashboard, and People) remain host-owned. No historical migrations or permissions are moved.

## Verification

```powershell
php artisan test tests/Feature/Core/PositionsPortalRouteOwnershipTest.php tests/Feature/Core/PositionsModuleRouteOwnershipTest.php tests/Feature/Portal/PortalPositionTest.php tests/Feature/Portal/PortalPermissionStrategyTest.php
php artisan test
npm run build
php artisan route:list --name=positions
git status --short
```

Browser spot-check: `/portal/positions` listing; existing Position detail and edit; create Position permissions; legacy `/positions` redirect. For a module-enabled deployment, explicitly set `INSITE_ENABLED_MODULES=positions` through controlled deployment configuration and rebuild config and route caches; test routing/permissions in that mode before release. Avoid changing the developer's `.env` just for the ordinary legacy browser spot-check.

## Future work

Continue extracting Positions controllers, services, model dependencies, frontend ownership, shared integrations, and a clean module-disabled experience; retire the legacy fallback only after all dependent workflows have been separated and verified.
