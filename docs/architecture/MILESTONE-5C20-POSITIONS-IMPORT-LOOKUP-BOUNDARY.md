# Phase 5C.20 — Positions / Data Import matching boundary

## Scope

The Data Import validation matcher now depends on the Core-facing `App\Contracts\Positions\PositionImportLookup` rather than directly querying Positions and Candidate models. The activated Positions module binds `ModulePositionImportLookup`; the unselected legacy installation binds `LegacyPositionImportLookup`. The existing `ImportRecordMatcher::person()` logic remains in Core.

## Preserved behavior

- Case-insensitive, whitespace-trimmed `position_code` lookup.
- Existing Person + Position candidate lookup, returning the matching Candidate or `null`.
- The import validator's existing response shape, conflict resolution, and workflow behavior.
- Legacy installation fallback when Positions is not selected.
- No routes, UI, tables, migrations, or import execution changes.

## Limits and remaining work

This is **one incremental read-boundary extraction**, not the complete 5C.20 integration project. The contract still exposes transitional Core Eloquent model types (`Position`, `Candidate`, `Person`); moving them is deferred to the model ownership milestones. `ImportExecutionService`, `ImportRollbackService`, and other readers still refer directly to those models. The disabled/without-legacy configuration does not register this capability; callers must not run Positions imports there.

## Validation

`php artisan test tests/Feature/Core/PositionsModuleImportLookupBoundaryTest.php tests/Feature/Admin/DataImportTest.php`

`php artisan test`

`npm run build`

Verify Data Import: validation of an existing Position code, case-insensitive matching, matching an existing Person/Position candidate, and legacy fallback. Commit only after verification.
