# Phase 5C.18 — Positions Candidate-Add Controller Ownership

## Baseline
- Branch: `architecture/modular-foundation`.
- Prior commit: `0b1e2b4` (Phase 5C.16); Phase 5C.17 is documentation-only and should be committed first.
- Prior reported full suite: 374 passing tests.

## Change
The activated Positions module now routes both POST `/positions/{id}/candidates` and POST `/portal/positions/{id}/candidates` to `Insite\Modules\Positions\Http\Controllers\PositionCandidateController`. This module implementation preserves the prior host controller's request validation, duplicate-person rejection, active/default workflow selection, candidate creation fields, `UserEventLogger` call, route-specific redirects, errors, and flash messages.

The existing `App\Http\Controllers\PositionCandidateController` and host routes remain intact for installations where the Positions module is not selected. Module routes retain their module guard and permission middleware. No database, frontend, configuration, or schema changes.

## Boundaries still in Core
- `App\Models\Position`, `Candidate`, and `Workflow` remain Core model classes.
- The `UserEventLogger` remains a host service; the event is categorized under `candidates` as before.
- Candidate CRUD outside the Position-add workflow remains separate from this extraction.

## Verification
Run:

```powershell
php artisan test tests/Feature/Core/PositionsModuleCandidateControllerOwnershipTest.php tests/Feature/Core/PositionsModuleLegacyControllerExtractionTest.php tests/Feature/Core/PositionsModulePortalControllerExtractionTest.php tests/Feature/Positions/PositionCandidateManagementTest.php
php artisan test
npm run build
git status --short
```

Confirm both legacy and Portal candidate-add flows in browser with module enabled: duplicate and inactive workflow errors, successful candidate creation, expected redirect, and no unauthorized access. Preserve module-off host fallback behavior.

## Next
Phase 5C.19 — assess and extract dashboard implementation behind the existing `WorkforceDashboardProvider` contract.
