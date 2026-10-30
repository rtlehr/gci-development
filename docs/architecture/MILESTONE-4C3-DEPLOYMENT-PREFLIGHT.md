# Insite Portal — Milestone 4C.3: Read-Only Deployment Preflight

**Starting checkpoint:** `d6b136b` on `architecture/modular-foundation`  
**Scope:** Static compatibility and migration-file inventory only. No schema changes, activation changes, or automatic upgrades.

## Contract decisions

- Installation configuration `config/insite_modules.php` declares `core_version = 1.0.0` as the **Insite Core module API contract version**, not the Laravel framework or product release version. Increment this only when the Core/module contract changes according to an approved release policy.
- Optional module manifest property `core` accepts the existing version constraint dialect: exact `1.0.0`, `^1.0.0`, `~1.0.0`, `>=1.0.0`, etc. Omitted means no explicit Core compatibility declaration **for legacy modules only**. Require it in future production modules after migration policy is finalized.
- Existing `requires` maps module IDs to constraints and is checked by the existing dependency resolver.
- Disabled modules are inventoried by the catalog, but compatibility is checked only for selected modules. Installing a module is not activating it.
- This milestone **does not** add module migration execution. It counts selected modules' `database/migrations/*.php` files without including them.
- Disabling a module does not delete its data. Rollbacks must be planned separately and must not rely on destructive `migrate:rollback` by default.

## Usage

```powershell
php artisan insite:modules:preflight
php artisan test tests/Feature/Core/ModulePreflightTest.php
php artisan test
npm run build
git status --short
```

The command returns exit code 1 for missing dependencies, invalid selections, invalid manifests, or incompatible Core versions. It returns zero on static validation success and always warns that it has **not** verified schema, runtime permissions, backups, migration safety, generated assets, or rollback readiness.

## Release protocol (proposed; not yet automated)

1. Back up database, files, environment configuration and current release; record restoration procedure.
2. Verify module package manifests and compatibility using preflight.
3. Put installation into a controlled deployment window if migrations require it.
4. Run approved, module-scoped, reviewed migration commands (future milestone), never automatically on module enable/disable.
5. Build frontend assets if the module contributes Vue code; rebuild configuration and routes cache consistently with module selection.
6. Run health checks and smoke tests, verify authorization, and record module/Core versions.
7. For rollback, restore compatible code and assets and use a tested database recovery strategy; avoid assuming schema downgrades are safe.

## Future checkpoints

- Define migration ownership, idempotence, table naming and schema-forward compatibility.
- Check actual applied module migrations against the migrations table and plan safe execution.
- Enforce module-supplied Core contract constraints for new production modules.
- Add release manifests/artifact checksums, approved deployment gates and independent module packaging.

**Important:** The command validates static declarations, not whether a release is safe to deploy. A successful result is necessary but not sufficient.
