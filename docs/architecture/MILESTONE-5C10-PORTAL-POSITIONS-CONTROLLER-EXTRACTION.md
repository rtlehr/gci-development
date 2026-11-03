# Phase 5C.10 — Portal Positions Controller Extraction

Starting Git checkpoint: `94d2c9e` (353 tests, 1,971 assertions).

## Objective

Transfer implementation ownership of ten existing modern `/portal/positions` actions to the activated Positions module. Preserve host fallback for installations that do not select Positions.

## Delivered changes

- Copy the current `App\Http\Controllers\Portal\PositionController` implementation into `Insite\Modules\Positions\Http\Controllers\Portal\PositionController` without rewriting its operations, validation, access rules, or Inertia page names.
- Update only the module's route import, leaving all existing URIs, HTTP methods, action names, permission middleware, module guards, and route model binding middleware intact.
- Retain the host Portal controller untouched for unselected installations.
- Preserve the `PositionCandidateController` handoff and custom-skill/task controller ownership, already separated by previous milestones.
- Add three regression tests checking ten host actions, ten selected-module actions (including middleware), the candidate exception, and stale-route fail-closed behavior.

## Architecture and limitations

The copied module controller uses the shared `App\Contracts\Positions\PositionOperations` boundary established in Phase 5C.8. Shared app-level models (`Position`, `Person`, `Workflow`, etc.), access services, list engine, custom fields and Inertia views are still intentionally shared. This milestone **does not claim complete standalone module independence**. Both host and module controller implementations must receive future bug fixes while both installation modes are supported.

No database migrations, schema changes, frontend component changes, changes to role or permission names, or API/URI changes.

## Verification

```powershell
php artisan test tests/Feature/Core/PositionsModulePortalControllerExtractionTest.php tests/Feature/Core/PositionsPortalRouteOwnershipTest.php tests/Feature/Portal/PortalPositionTest.php tests/Feature/Core/PositionsOperationsBoundaryTest.php
php artisan test
npm run build
git status --short
```

Expected full count: **356 tests** if the three new tests pass, up from 353. These commands must be run in the user's development environment, because this patch environment does not contain the application's Composer vendor dependencies or configured MySQL test database.

Browser checks: inspect the Portal Positions list, detail, edit, create, save, and change history using the existing unselected-module installation. For selected-module browser coverage, use a dedicated development/test configuration with `INSITE_ENABLED_MODULES=positions`, not a production configuration; verify existing permissions and candidate workflow handoff.
