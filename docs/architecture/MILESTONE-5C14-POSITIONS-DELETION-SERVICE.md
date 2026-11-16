# Milestone 5C.14 — Module-owned Position deletion service

## Purpose

Continue the Positions modular-monolith extraction without changing endpoint names, middleware, authorization, database schema, UI, or host-fallback behavior.

## Changes

- Added `Insite\Modules\Positions\Services\PositionDeletionService`.
- Both module-owned controllers delegate the assignment guard, deletion activity, and model deletion to the service.
- The Portal controller passes `auditPortal: true`, preserving its existing `UserEventLogger::recordModelEvent` call and `metadata: ['deleted' => true]`. The legacy controller does not generate that event, as before.
- Position records with *any* assignment (including ended assignments) are not deleted. The existing redirect targets and session messages remain in each controller.
- Host fallback controllers are untouched.

## Verification

```powershell
php artisan test tests/Feature/Core/PositionsModuleDeletionServiceTest.php tests/Feature/Core/PositionsModuleLegacyControllerExtractionTest.php tests/Feature/Core/PositionsModulePortalControllerExtractionTest.php tests/Feature/Portal/PortalPositionTest.php
php artisan test
npm run build
git status --short
```

Manually verify both module-enabled deletion paths: a Position without assignments can be deleted; a Position with historical or current assignments cannot. Check that Portal deletions retain user-event audit logging. Also verify unselected-module fallback behavior.

## Remaining boundaries

This service still intentionally uses shared `App\Models` and `App\Services\UserEventLogger`. Model ownership and the cross-module audit contract are future migration decisions. Avoid deleting host fallback implementations until optional installations have been proven.
