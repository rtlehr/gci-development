# Insite Portal — Milestone 4C.5: Isolated migration execution and data preservation

**Starting Git checkpoint:** `3365138` on `architecture/modular-foundation`.

## Scope

This milestone adds only an integration test and documentation. It does **not** change runtime migration code, move existing business tables, or trigger automatic module migrations.

The demonstration migration from 4C.4 creates `insite_demo_records`. This test proves that:

1. An explicit `insite:modules:migrate demo --apply --force` can apply that migration when the demo module is enabled.
2. Laravel logs exactly one applied migration and a second application is idempotent.
3. A row inserted into the demo table remains after disabling the module.
4. Attempts to apply a disabled module's migrations are rejected.
5. Default preview mode does not create tables.
6. The test removes **only its own** demo table and migration ledger row after verification, leaving unrelated application records alone.

## Mandatory database safeguard

`ModuleMigrationExecutionTest.php` refuses to run unless **both** are true:

- `APP_ENV=testing`.
- The **actual database connection** reports database name **`irad_testing`**.

It refuses to overwrite an existing `insite_demo_records` table or an existing corresponding migration record. If either exists, stop and investigate; **do not drop it just to make the test pass**.

The `--force` flag in this automated test only bypasses the interactive confirmation; it must never be copied into a normal development/production migration procedure. An application-level flag is not a substitute for database isolation or backups.

## Run from PowerShell

Before testing, verify `.env.testing` has `DB_DATABASE=irad_testing` and your terminal has no unexpected `DB_DATABASE` overrides. Do not run manual `--apply` against `laravel_app`.

```powershell
php artisan test tests/Feature/Core/ModuleMigrationExecutionTest.php
```

**Target:** 2 passing tests. The test applies its own Demo migration, checks it, and cleans up afterward.

Then:

```powershell
php artisan test
npm run build
git status --short
```

**Target total:** 308 passing tests, assuming the previous 306 remain unchanged.

### Optional read-only verification (safe with Demo disabled)

```powershell
php artisan insite:modules:status
php artisan insite:modules:preflight
```

Expected: a valid, disabled-by-default demo module and passing static preflight.

## Data integrity and rollout rules

- Each customer installation has its own database; module migrations affect **only that customer's database**.
- Disablement is a routing/activation decision, not schema deletion.
- Production migrations must require an independently verified database backup, deployment approval, and restore/forward-fix plan.
- No automatic `migrate:rollback`, `migrate:fresh`, `db:wipe`, or deletion during module disablement.
- Migration history is currently tracked through Laravel's shared `migrations` table. A future production hardening milestone should consider migration filename collision prevention, migration ownership mapping, and cross-module foreign-key policy.
- This integration test validates the demo module path; it does not yet prove production backup or rollback correctness.

## Acceptance and checkpoint

Focused tests, full suite, and build must all pass. Verify Git status only shows these two new files. Commit only after inspection and successful tests.
