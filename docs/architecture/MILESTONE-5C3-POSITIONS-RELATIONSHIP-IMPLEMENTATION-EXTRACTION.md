# Phase 5C.3 — Positions Relationship Implementation Extraction

## Starting checkpoint

`add8bc2` — Phase 5C.2 service registration ownership (329 tests / 1,684 assertions).

## Scope

This is the first physical relocation of a **Positions-owned business implementation**. The `PersonPositionRelationships` contract remains in `app/Contracts/Workforce`, which is Core-facing. A new `Insite\Modules\Positions\Services\ModulePersonPositionRelationships` implementation lives under `modules/positions/Services`.

When the Positions module is activated and legacy business models are present, its provider binds that module-owned adapter. Installations without the module selected continue to use `App\Services\Positions\LegacyPersonPositionRelationships` through the host fallback. The host class remains intentionally for compatibility; removing it is deferred until the independent Core-only installation is verified. Avoid introducing additional consumers of this legacy adapter.

## Behavioral invariants

- `Person::assignments()` stays an Eloquent `HasMany` to `PositionAssignment`, with `person_id` foreign key.
- `Person::positions()` stays a `BelongsToMany` through `position_assignments` and preserves the five pivot attributes plus timestamps.
- The `INSITE_LEGACY_POSITIONS_ENABLED` transitional setting continues to govern whether legacy integration is available; the new module does not implicitly enable it.
- Dashboard data remains provided by `ProjectManagerDashboardService` and is **not** extracted in this milestone.
- No changes to routes, middleware, permissions, database schema/migration history, candidate/resume workflows, or Vue pages.

## Test steps

1. Run `php artisan test tests/Feature/Core/PositionsModuleRelationshipExtractionTest.php tests/Feature/Core/PositionsServiceOwnershipTest.php tests/Feature/Core/PersonPositionsContractTest.php tests/Feature/Core/PersonPositionsOptionalBindingTest.php`.
2. Run `php artisan test` (target 332 tests, assuming prior baseline unchanged).
3. Run `npm run build` and `git status --short`.
4. With normal .env values unchanged, verify People > Position Assignments, Positions list/details, and PMO dashboard in browser.

## Deferred tasks

Extract `ProjectManagerDashboardService` and its dependencies; remove host fallback only after module activation and truly module-absent installations have been fully validated. Preserve historical migrations and existing record IDs.
