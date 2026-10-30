# Insite Portal — Milestone 4A: Passive Module Catalog

## Scope

This is the first *implementation* checkpoint in the modular-monolith migration. It adds a read-only PHP module manifest catalog registered in Laravel's service container. It does not load module PHP code, execute providers, alter routes, add migrations, change permissions, change the UI, or modify any existing business logic.

## Manifest convention

An eventual module lives under `modules/<module-id>/module.json`. Directory and manifest ID must match. IDs use lowercase letters, digits, and hyphens, and start with a letter.

Example (for future use; **do not add this module yet**):

```json
{
  "id": "travel",
  "name": "Travel",
  "version": "0.1.0"
}
```

The catalog returns only `id`, `name`, `version`, and the absolute module path. Discovery is deterministic and rejects invalid manifests. Metadata is not equivalent to installed, enabled, licensed, or authorized status. No module code is executed.

## Files in this patch

- `app/Core/Modules/ModuleCatalog.php` — read-only discovery, validation, lookup.
- `app/Providers/ModuleCatalogServiceProvider.php` — container registration.
- `bootstrap/providers.php` — adds the provider without removing existing providers.
- `tests/Unit/Core/ModuleCatalogTest.php` — behavior and registration tests.

## Install

1. Ensure branch is `architecture/modular-foundation` and working tree is clean.
2. Extract the patch ZIP at the repository root, preserving directory paths.
3. No Composer update or database migration is required.

## Validate

```powershell
php artisan test tests/Unit/Core/ModuleCatalogTest.php
php artisan test
npm run build
php artisan route:list --json | Out-File -FilePath current-routes.json -Encoding utf8
```

Compare `current-routes.json` with `docs/architecture/baseline/baseline-routes.json`. The registered route set should be unchanged. Do not add the generated `current-routes.json` to Git unless deliberately preserving it as another snapshot.

## Manual check

Open Insite Portal and verify its usual dashboard, People page, Positions, and Candidates routes load and navigate as before. No new module screen should appear. This checkpoint deliberately introduces no visible UI changes.

## Next step (not included)

Define module state (available/installed/enabled), dependency semantics, API compatibility, authorization policy, and lifecycle commands **before** allowing providers, route registrations, or module migrations to execute.

## Safety notes

The catalog treats module files as trusted locally deployed source, not customer-uploaded packages. Never allow arbitrary runtime uploads or module installation through an ordinary admin UI. Module signatures, compatibility checks, migrations, deployment rollback, and versioned contracts remain future work.
