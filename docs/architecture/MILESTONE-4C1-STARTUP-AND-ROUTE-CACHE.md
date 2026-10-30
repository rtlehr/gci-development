# Insite Portal — Milestone 4C.1: Startup and Route Cache Validation

Status: candidate implementation; verify locally before committing. Starting checkpoint: `dbc09a4`.

## Changes

- `config/insite_modules.php` accepts the installation-specific `INSITE_ENABLED_MODULES` comma-separated environment setting. Default remains empty: no optional modules selected.
- `tests/Feature/Core/ModuleStartupTest.php` checks both enabled and disabled module route discovery in **fresh Artisan processes**, rather than only calling the activator after the test application has already booted.
- The demonstration health route now uses an invokable controller instead of a closure, which is required for Laravel route caching.
- Existing late-activation tests remain in `ModuleActivationTest.php`.
- No existing business modules, database tables, permissions, or Vue code are changed.

## Safety and operational conventions

1. A module must be installed, selected, dependency-valid, and separately authorized for the requesting user. Selecting a module is **not** a permission grant.
2. Enablement is a **deployment operation**, not a runtime administrator toggle. Configuration cache and route cache must be rebuilt in a coordinated release.
3. Route caching preserves route definitions until rebuilt. The `insite-module` middleware still checks module selection at request time; a route accidentally retained in an old cache must not provide module access.
4. Do not run route or config cache commands in shared test environments or production without an approved maintenance procedure.
5. The demonstration route is not a production health endpoint or an authorization model for business modules. It should remain disabled in normal customer installations.
6. AI remains optional and has no mandatory Core dependency.

## Automated verification (from project root)

```powershell
php artisan test tests/Feature/Core/ModuleStartupTest.php tests/Feature/Core/ModuleActivationTest.php
php artisan test
npm run build
```

Expected automated test count after this milestone: 289 (assuming the prior 287-test baseline and both new tests). The tests do not change route-cache files.

## Manual route-cache verification (local development only)

**Before starting:** Confirm you are in `C:\Development\Vue\IRAD`, on the architecture branch, with no unrelated modifications. Do not use these steps in production. Commands below change only local Laravel-generated cache files; they do not run database migrations.

Save existing local module selection in the current PowerShell session:

```powershell
$previousModules = $env:INSITE_ENABLED_MODULES
```

Begin with no existing caches and select the demonstration module:

```powershell
php artisan route:clear
php artisan config:clear
$env:INSITE_ENABLED_MODULES = 'demo'
php artisan route:list --name=insite.modules.demo.health
php artisan route:cache
php artisan route:list --name=insite.modules.demo.health
```

Both route-list commands should include `insite.modules.demo.health`; `route:cache` must succeed.

**Disabled-module protection with a stale route cache:** Without clearing the route cache, deselect the module:

```powershell
$env:INSITE_ENABLED_MODULES = ''
php artisan route:list --name=insite.modules.demo.health
```

The cached route may still appear in the route list (it is a stored route definition). It **must not** be accessible to HTTP clients while the module is disabled. Verify through local HTTP (e.g. `Invoke-WebRequest http://127.0.0.1:8000/_insite/modules/demo/health -SkipHttpErrorCheck` in a PowerShell session running the app), and check for 404. Rebuilding route cache after disabling should remove it entirely.

Restore the local state when finished (run even if a command failed):

```powershell
php artisan route:clear
php artisan config:clear
$env:INSITE_ENABLED_MODULES = $previousModules
Remove-Variable previousModules
php artisan route:list --name=insite.modules.demo.health
```

The final route-list output should show no demo route if the original selection was empty. If the site uses cached config before these steps, coordinate restoring that cache separately; these instructions intentionally leave the configuration uncached.

## Installation-specific configuration

To enable a module in a real customer installation later, set `INSITE_ENABLED_MODULES` in that installation's private deployment environment. The planned deployment process will validate dependency versions, run approved migrations, build frontend assets, and rebuild caches before enabling traffic. **Do not configure production with the demo module.**

## Manual frontend testing

No frontend changes in 4C.1. Verify existing Portal Dashboard and a Position details page load, then perform the local demo-route cache check above. No existing business routes should change.
