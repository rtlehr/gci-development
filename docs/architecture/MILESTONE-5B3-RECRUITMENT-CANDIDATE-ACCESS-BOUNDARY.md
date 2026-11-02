# Phase 5B.3 — Recruitment candidate-access boundary

## Purpose

Separate candidate query scoping and record authorization from the mixed legacy `PortalWorkforceAccessService`. Preserve all existing permission precedence, URLs, candidate workflows, and database tables. This is **transitional**, not a fully extracted Recruitment module.

## Changes

- `app/Services/Recruitment/CandidateAccessService.php` owns candidate scoping and candidate record authorization.
- `app/Http/Controllers/Portal/CandidateController.php` depends on the Recruitment service instead of the mixed legacy service.
- `app/Services/PortalWorkforceAccessService.php` retains `scopeCandidates()` and `authorizeCandidateView()` as delegating compatibility wrappers. Existing Positions methods are unchanged.
- Four targeted regression tests exercise all-position, assigned-position, person-linked candidate access, and legacy compatibility.

## Compatibility contract

Permission precedence remains: `portal_view_all_positions` > `portal_view_assigned_positions` > `portal_view_candidate_positions` > deny. Candidate access checks remain separate from the portal's route middleware permissions. No changes to identity, authentication, database migration history, routes, or frontend components.

## Manual and automated verification

From project root:

```powershell
php artisan test tests/Feature/Core/CandidateAccessBoundaryTest.php tests/Feature/Portal/PortalWorkforceRecordAccessTest.php
php artisan test
npm run build
git status --short
```

Expected baseline: 315 tests before this change, **319** after adding four tests. Do not commit before verification. Browser smoke test: list candidates as an authorized user, open a linked candidate, verify another candidate is denied for scoped users, and open a Position details page.

## Follow-up

- Move `CandidateAccessService` into the actual Recruitment module after module manifests and dependencies are in place.
- Remove legacy wrappers only after all callers are migrated and regression coverage is retained.
- Eventually replace candidate model couplings in the Positions-owned query path with an explicit integration contract.
- Validate a Core-only installation after removing all legacy route and controller references, not merely after introducing this service.
