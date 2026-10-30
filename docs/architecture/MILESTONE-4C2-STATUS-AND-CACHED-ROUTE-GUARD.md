# Insite Portal — Milestone 4C.2: Status Diagnostics and Cached-Route Guard

## Scope

Starting point: `architecture/modular-foundation`, commit `30ccf9f` (Milestone 4C.1). This patch adds a read-only diagnostic Artisan command and a fail-closed safeguard for module routes. There are no migrations, frontend changes, or activation-policy changes.

## New command

```powershell
php artisan insite:modules:status
```

The command discovers local module manifests and displays each module's identifier, display name, version, selection state and required dependencies. It checks the whole selected dependency graph and exits with a nonzero status when the configuration or a manifest is invalid. It **does not** execute modules or change their status. **Selected** means configured as enabled; it is not a statement about runtime health, endpoint reachability, database migrations, or user permissions.

## Security invariant

When an old route remains registered, `insite-module:<id>` middleware must deny requests unless the module is currently installed, enabled and dependency-valid. If the dependency graph becomes invalid, the middleware returns HTTP 404 rather than exposing the module or leaking an internal exception. The command reports detailed configuration problems to administrators; the HTTP endpoint does not.

## Install

Extract the ZIP into the project root, preserving relative paths. Only these files are introduced/modified:

- `app/Console/Commands/InsiteModulesStatus.php` (new)
- `app/Http/Middleware/EnsureInsiteModuleEnabled.php` (modified)
- `tests/Feature/Core/ModuleStatusTest.php` (new)
- This Markdown document (new)

No migrations or Composer changes are required. Laravel auto-discovers commands in `app/Console/Commands` under the standard Laravel 13 application layout.

## Automated verification

```powershell
php artisan test tests/Feature/Core/ModuleStatusTest.php
php artisan test
npm run build
php artisan insite:modules:status
git status --short
```

Expected: five new tests, so **294 total** if the existing 289 pass. The focused tests cover command output, invalid selection, late registered routes that remain after disabling, and fail-closed behavior when the selected dependency graph is invalid. They do **not** compile a real route cache.

## Optional manual real-cache smoke test (local development only)

A route cache is a deployment artifact. Do not run these commands on production or a shared customer installation. Do not start while another server or build process is depending on the local cache. Ensure no important pre-existing Laravel caches are in use.

1. In PowerShell set `$env:INSITE_ENABLED_MODULES = 'demo'`.
2. Run `php artisan optimize:clear`, then `php artisan route:cache`.
3. Confirm `php artisan route:list --name=insite.modules.demo.health` displays the route.
4. **Keep the route cache**. Set `$env:INSITE_ENABLED_MODULES = ''` and run `php artisan config:clear` (NOT `route:clear`).
5. Start a fresh local HTTP process with `php artisan serve --host=127.0.0.1 --port=8001` (if port 8001 is free) and request `http://127.0.0.1:8001/_insite/modules/demo/health` from another terminal. The response must be **404** even though the cached route remains registered. Stop the local server afterward.
6. Clear caches with `php artisan optimize:clear`; remove the temporary environment variable with `Remove-Item Env:\INSITE_ENABLED_MODULES -ErrorAction SilentlyContinue`.

For real deployments, config/route cache changes must be coordinated with process restarts and deployment releases. Do not treat this guard as a substitute for a controlled deployment or per-user permissions.

## Known limitations

- The demo endpoint is intentionally non-sensitive. Production modules also require authentication and fine-grained authorization.
- A status report does not verify external services, module migrations, or frontend assets.
- An invalid selected module may prevent normal application startup; the status command is most useful for clean installations or when the provider boot path allows diagnostics to execute.
- No customer-facing module management UI is introduced in this milestone.
