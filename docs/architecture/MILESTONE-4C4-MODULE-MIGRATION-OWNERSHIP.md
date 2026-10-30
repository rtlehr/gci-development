# Insite Portal — Milestone 4C.4: Module-owned migrations

**Starting commit:** `5d61740` (`architecture/modular-foundation`).

## Architectural contract

- Every customer has its **own Insite application installation and database**.
- Core migrations remain in `database/migrations/`; a module owns `modules/{module-id}/database/migrations/*.php`.
- Module migration execution is **not coupled to module activation, request routing, disabling, or uninstall**.
- Disabling a module must **never roll back** its migrations, drop tables, or delete customer data.
- Core checks module dependencies and Insite Core compatibility before any explicit module migration command runs.
- The command is **preview-only by default**. It does not determine database pending state, verify backups, or apply changes.
- `--apply` enables migration execution for one installed, enabled module; it uses Laravel's migration ledger and a single directory through `--path` and `--realpath`.
- For nonproduction use, interactive confirmation is required unless `--force` is provided. Production requires `--force`, but **that does not verify a backup**. A restorable backup, release window, rollback plan, and deployment approval must be confirmed externally.
- Never automatically call `migrate:rollback`, `migrate:fresh`, `db:wipe`, or destructive operations as part of disablement.

## Included demonstration

The Demo module now contains an illustrative migration creating `insite_demo_records`. It is **not executed by installing this patch, starting Insite, enabling or disabling Demo, or running ordinary module preflight**. It will create that table **only if explicitly applied**.

## Tests (no migration execution)

```powershell
php artisan test tests/Feature/Core/ModuleMigrationSafetyTest.php
php artisan test
npm run build
php artisan insite:modules:preflight
```

Target: **6 new tests, 306 total** (provided existing 300 still pass).

## Manual preview — safe for local development

PowerShell session only:

```powershell
$env:INSITE_ENABLED_MODULES = 'demo'
php artisan optimize:clear
php artisan insite:modules:migrate demo
php artisan insite:modules:preflight
Remove-Item Env:\INSITE_ENABLED_MODULES
php artisan optimize:clear
```

Expected preview: one Demo migration file and `Preview only. No migrations executed`. **Do not run `--apply` on your primary development or production database just to validate this milestone**. The read-only preview and automated tests provide this milestone's required verification. A separate isolated `irad_testing` database and confirmed connection should be used for later end-to-end execution testing.

### Explicit execution (future controlled deployment only)

After checking the target database and making a restorable backup:

```powershell
php artisan insite:modules:migrate demo --apply
```

For a production release after approval, `--force` is additionally necessary. Running this command is not part of the 4C.4 acceptance steps.

## Limitations and next milestones

1. Existing application migrations are still in Core; no ownership transfer happens here.
2. A separate schema inventory and cross-module foreign-key policy are needed before moving Positions/Candidates migrations.
3. Automated testing of **actual migration application, ledger behavior and idempotency** should happen against a disposable isolated database before relying on the runner in production.
4. Migration rollback remains a manual, separately reviewed restore or forward-fix procedure; it is not automatically derived from `down()` methods.
5. Treat module files as trusted local packages. The plan prevents symlinked migration files/directories but does not sandbox executable PHP migration code.
