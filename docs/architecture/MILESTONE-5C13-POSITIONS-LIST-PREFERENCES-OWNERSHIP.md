# Phase 5C.13 — Positions module list preference ownership

## Scope

The activated Positions module now owns the save/reset implementation for its legacy and Portal list column preferences via `Insite\Modules\Positions\Services\PositionListPreferences`.

- Both module-owned controllers delegate persistence and deletion to the same service.
- Column keys continue to be derived from the module-owned Positions list definition augmented with custom fields.
- Original validation (`visible_columns` and `column_order` arrays of strings) is retained. Unknown column keys are filtered out, and supplied ordering is preserved.
- Preferences remain stored in the existing `user_list_preferences` table, keyed by user and `positions` list key.
- Existing route names, redirects, success messages, list rendering, and CSV exports remain unchanged.
- Host controllers and host fallback remain untouched. This is **not** a schema migration or a redesign of the shared list-preferences subsystem.

## Verification

Run `php artisan test tests/Feature/Core/PositionsModuleListPreferencesTest.php tests/Feature/Core/PositionsModuleListDefinitionOwnershipTest.php tests/Feature/Portal/PortalPositionTest.php`, then `php artisan test` and `npm run build`.

In a module-enabled installation, verify Column Settings save/reopen/reset on both legacy Positions and Portal Positions lists. Confirm unknown/stale keys cannot become saved preferences. Also verify the standard host fallback without `positions` enabled.

## Remaining work

The module still integrates with Core models, list engine, custom-field augmentation, and user resolution. These are deliberate integration dependencies to review in later milestones; this extraction does not move those shared services.
