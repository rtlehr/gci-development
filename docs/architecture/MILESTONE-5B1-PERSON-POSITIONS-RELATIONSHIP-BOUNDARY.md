# Insite Portal — Milestone 5B.1: Person / Positions Relationship Boundary

## Scope

The Core `Person` model previously declared both Positions relationships using direct references to `Position` and `PositionAssignment`. This patch introduces a Core-owned `PersonPositionRelationships` contract and a temporary Positions-owned implementation at `app/Services/Positions/LegacyPersonPositionRelationships.php`.

Existing `Person::assignments()` and `Person::positions()` methods **remain available**, preserving direct calls, eager loading, related model classes, pivot metadata, and timestamps. The binding currently lives in `AppServiceProvider`, and will move to the Positions module's service provider during extraction.

## Important limitations

This is an **incremental compatibility boundary**, not proof that Core runs without Positions installed. The temporary provider binding and downstream controllers still depend on legacy Positions classes. Core-only deployments must not be claimed supported yet. Subsequent milestones will move the adapter, isolate routes, remove direct dependencies from controllers and shared import services, and test a real Core-only boot.

No database migrations, route changes, or Vue files are included.

## Tests (Windows PowerShell)

1. Extract ZIP at `C:\Development\Vue\IRAD`, preserving relative paths.
2. `php artisan test tests/Feature/Core/PersonPositionsContractTest.php`
3. `php artisan test`
4. `npm run build`
5. Verify the People detail/edit pages, Position list/detail, and a test person's position assignments in the browser.
6. `git status --short` and review changed files before committing.

Expected focused tests: **3**. Expected full baseline after patch: **311** (previously 308). No destructive commands are required.

## Subsequent extraction

1. Move adapter implementation into `modules/positions` under its own namespace.
2. Bind the Core contract only when the Positions module is selected.
3. Rework People deletion protection and import validation to depend on optional interfaces, not Positions models.
4. Add true Core-only configuration and boot tests with Positions absent.
5. Preserve historical migrations and existing permission and route names.
