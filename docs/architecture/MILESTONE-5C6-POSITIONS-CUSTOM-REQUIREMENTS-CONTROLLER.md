# Milestone 5C.6 — Module-owned Positions custom requirement controller

## Starting checkpoint

`42025e0` — Phase 5C.5 committed; 338 passing tests (1,855 assertions).

## Scope

This first controller extraction moves execution of the four custom position skill/task actions into `Insite\Modules\Positions\Http\Controllers\PositionCustomSkillTaskController` **when Positions is selected**. The module route declarations now point to that controller. The original `App\Http\Controllers\PositionCustomSkillTaskController` remains in place as a *transitional host fallback* when the module is unselected.

All four existing URL patterns, route names, verbs, required `permission:update_positions` check, `auth` and `permission:access_portal` checks, and disabled-module guard are unchanged. Route parameter names remain `position`, `skill`, and `task`; Eloquent model binding and cross-position deletion checks are preserved. No migrations, tables, models, Vue files, or Composer configuration are changed.

## Why these actions first

They form a small self-contained CRUD boundary. Changing route targets only for selected-module deployments avoids making Core import classes from a disabled module. The host controller is intentionally *not* deleted or made to extend the module controller, to avoid coupling Core-only deployments to optional code. Until legacy fallback retirement there are two equivalent implementations to maintain; keep both aligned when making fixes.

## Files

- **New:** `modules/positions/Http/Controllers/PositionCustomSkillTaskController.php`
- **Modified:** `modules/positions/routes/web.php`
- **New:** `tests/Feature/Core/PositionsModuleCustomRequirementsControllerTest.php`
- **New:** `docs/architecture/MILESTONE-5C6-POSITIONS-CUSTOM-REQUIREMENTS-CONTROLLER.md`

## Validation

```powershell
php artisan test tests/Feature/Core/PositionsModuleCustomRequirementsControllerTest.php tests/Feature/Core/PositionsPortalRouteOwnershipTest.php tests/Feature/Portal/PortalPositionTest.php
php artisan test
npm run build
php artisan route:list --name=positions
```

The new tests inspect controller dispatch in selected and unselected modes, exercise create/delete of both skill and task through module routes, and verify cross-position deletion returns 404. Expected full suite: **342 tests** if no other tests have been added.

Manual UI: unchanged environment, open a Position edit page and add/remove a custom skill and task; confirm both are saved and deleted. This exercises the **host fallback** in the usual unselected configuration. The focused automated tests exercise selected-module behavior.

## Next

Incrementally extract other Positions actions/services, then address remaining Core references, dashboard integration, imports, and legacy fallback retirement; protect route and permission compatibility at every step.
