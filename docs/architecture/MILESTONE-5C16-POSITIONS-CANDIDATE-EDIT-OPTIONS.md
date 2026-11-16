# Milestone 5C.16 — Positions candidate edit options ownership

## Baseline

Commit `b14fa18` (Phase 5C.15); 371 passing tests / 2,321 assertions.

## Change

Both module-owned Positions edit controllers previously duplicated the exact same candidate-person and active-workflow option queries. `PositionCandidateEditOptions` now owns those queries. Both controllers retain their independent Inertia payloads and their existing position candidate projections, access controls, and other edit data.

- `candidateOptions(Position $position)`: excludes already-attached candidate persons, sorts people by last/first name, and retains the existing field shape and primary phone details.
- `workflows()`: only active workflows, primary first and name next, with the same field shape.
- Neither host fallback controller was changed.
- No routes, database schema, permissions, Vue files, or module activation wiring were changed.

## Boundaries and future work

This is **module ownership of the edit-option query**, not isolation of the `Person`, `Candidate`, `Workflow`, or `Position` models. Those remain host models with explicit integration dependencies to review before optional module uninstall.

## Verification

```powershell
php artisan test tests/Feature/Core/PositionsModuleCandidateEditOptionsTest.php tests/Feature/Core/PositionsModuleLegacyControllerExtractionTest.php tests/Feature/Core/PositionsModulePortalControllerExtractionTest.php tests/Feature/Portal/PortalPositionTest.php
php artisan test
npm run build
git status --short
```

Expected full suite: 374 tests if the 371-test baseline is unchanged. Browser-check both legacy and Portal Position edit pages: candidate picker excludes attached people, active workflows are selectable and primary workflows come first. Test module-enabled installation before production deployment.
