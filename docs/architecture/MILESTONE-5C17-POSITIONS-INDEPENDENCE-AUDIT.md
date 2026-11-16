# Insite Portal — Phase 5C.17: Positions Independence Audit and Exit Criteria

**Baseline:** `architecture/modular-foundation` at `0b1e2b4` (Phase 5C.16).  **Reported application verification:** 374 passing tests.  **This milestone:** assessment only; no production runtime changes.

## Scope and source basis

Inspected the user-provided `5c-14.zip` application snapshot, then overlaid the Phase 5C.14, 5C.15, and 5C.16 changed-file packages, including the Phase 5C.16 test correction. The resulting reconstructed source is intended to match the committed `0b1e2b4` milestone for those changed files; it is **not** a fresh Git checkout and should be compared with the repository before implementation. No tests were executed in this inspection environment.

## Current ownership — already extracted

- `modules/positions/module.json`, `PositionsServiceProvider.php`, `routes/web.php`: selected module registration, services, legacy `/positions` routes and modern `/portal/positions` routes.
- `modules/positions/Http/Controllers/PositionsController.php` and `Portal/PositionController.php`: module-selected legacy and Portal Positions actions.
- `modules/positions/Http/Controllers/PositionCustomSkillTaskController.php`: selected custom skill/task actions.
- `modules/positions/Services/ModulePositionService.php`: position create/update and activity business logic via `App\Contracts\Positions\PositionOperations`.
- `ModulePersonPositionRelationships.php`: module-owned implementation of `App\Contracts\Workforce\PersonPositionRelationships`.
- `PositionInputValidator.php`, `PositionDeletionService.php`, `PositionListConfiguration.php`, `PositionListPreferences.php`, `PositionCandidateEditOptions.php`, and `Support/ListDefinitions/PositionsDefinition.php`: module-owned validation, deletion, list/prefs and candidate edit option behavior.

## Remaining coupling inventory

| ID | Evidence in reconstructed source | Implication / proposed ownership | Priority |
|---|---|---|---|
| P1 | `modules/positions/routes/web.php` imports `App\Http\Controllers\PositionCandidateController` for both legacy and Portal candidate-add routes. | A selected Positions route still invokes a Core business controller. Move the route handler to module ownership, **retaining the same candidate authorization, validation, redirects and workflow behavior**. Candidate lifecycle boundaries should be decided before moving broader recruitment features. | High |
| P2 | `modules/positions/PositionsServiceProvider.php` binds `WorkforceDashboardProvider` to `App\Services\ProjectManagerDashboardService`; that service imports `App\Models\Position`. | Module activation still relies on a host dashboard implementation. Introduce a module-owned dashboard provider or facade implementing the existing dashboard contract, with compatible output, and preserve Core-only empty provider behavior. | High |
| P3 | `app/Models/Position.php`, `PositionAssignment.php`, `PositionActivity.php`, `PositionCustomSkill.php`, `PositionCustomTask.php` remain in `App\Models`; module controllers and services import them directly. | The position aggregate is still host-defined. First map all consumers and cross-module API contracts; **do not rename/move models yet** because Eloquent relationships, factories, imports and tests reference these classes. | High, later |
| P4 | `database/migrations/280_create_positions_table.php`, `290_create_position_assignments_table.php`, `330_create_position_activities_table.php`, `360_create_position_custom_skills_table.php`, `370_create_position_custom_tasks_table.php` remain host migrations. | Database table ownership is not yet module-local. Design additive migration ownership and upgrade safety. Do not move already-applied migration files without a stable migration tracking and rollout plan. | High, later |
| P5 | `app/Models/Position.php` links Person/assignments, Candidate, JobTitle and User. `app/Services/ProjectManagerDashboardService.php`, `app/Services/DataImport/*`, `app/Http/Controllers/PositionAssignmentsController.php`, and `app/Http/Controllers/PositionCandidateController.php` reference Positions concepts. | Cross-domain integrations need explicit contracts: Workforce assignments, recruitment/candidates, data import/export, staffing dashboard, and user-event linking. Decide contracts before isolating models. | High |
| P6 | `app/Providers/AppServiceProvider.php` registers legacy bindings and an optional dashboard fallback; `config/insite_modules.php` defaults `legacy_positions_enabled` to true. Host controllers and `routes/portal.php` still provide non-selected fallback endpoints. | The legacy compatibility path remains intentional. Prove selected, unselected+legacy, and Core-only configurations work separately before deprecating anything. Do not remove fallback early. | High |
| P7 | Module controllers depend on shared services including `CustomFieldService`, `CustomFieldListService`, `ListEngine`, `ListExportService`, `PortalWorkforceAccessService`, and `UserEventLogger`. | These can remain **Core shared infrastructure** if they do not import Positions internals. Review inward dependency direction and create narrow contracts only where necessary; do not mechanically copy generic services. | Medium |
| P8 | `modules/positions/routes/web.php` registers selected Positions routes with `insite-module:positions`; host routes conditionally own non-selected paths. | Route and permission parity requires regression tests and route-cache smoke tests for each deployment mode. | Medium |

## Recommended sequence from 5C.18 onward

1. **5C.18 — Candidate-add route handler ownership (P1).** Move only `PositionCandidateController`'s selected-route behavior to module-owned controller/service, preserve host fallback; write feature tests asserting exact route targets and candidate workflow semantics. This is a small, immediately actionable boundary.
2. **5C.19 — Dashboard provider boundary (P2).** Move selected Positions dashboard implementation behind `WorkforceDashboardProvider` into the module; verify existing UI payloads and role gates.
3. **5C.20 — Cross-domain consumer map / contracts (P5/P7).** Audit assignments, Candidate/Recruitment, Data Import, exports, and user-event link resolution. Establish host-facing contracts only for needed operations; test module-disabled handling.
4. **5C.21 — Model ownership design (P3).** Document all model references/relationships/factories and choose compatibility approach (adapter, alias, or migration). Introduce change only with broad tests.
5. **5C.22 — Migration ownership and installation safety (P4).** Distinguish new install, existing install, selected/unselected and cached routes; validate migration history and safe rollback/upgrade.
6. **5C.23 — Independence verification (P6/P8).** Run installation-mode matrix and deployment preflight. Only then consider removing transitional host behavior.

These are *proposed milestones*, not delivery dates or promises; reassess after each test cycle.

## Positions independence exit criteria

- [ ] When selected, all Positions-specific route actions resolve to module-owned controllers or explicitly documented shared interfaces, not Core Positions business controllers.
- [ ] When selected, Positions-specific dashboard implementation is module-owned and Core sees only `WorkforceDashboardProvider`.
- [ ] Clear and tested ownership decisions for Position, PositionAssignment, PositionActivity, custom skills/tasks, Candidate interfaces and associated migrations.
- [ ] Shared infrastructure and cross-module consumers reference stable host interfaces rather than module implementation classes.
- [ ] Existing module-unselected installations retain functional legacy Positions workflows as required; Core-only mode has no unexpected Positions dependency.
- [ ] Routes, permissions, authorization, model binding, role-based views, CSV export, list preferences, candidate editing, assignment history and audit logging preserve current behavior.
- [ ] Verified with focused + complete Pest suites, `npm run build`, browser smoke tests, and route/config cache validation for selected, unselected+legacy, and Core-only configurations.
- [ ] A clean working tree and Git baseline are recorded at the final independent-module checkpoint.

## Installation, validation and Git workflow

This Phase 5C.17 package intentionally adds **only this document**; application source code is unchanged. Extract to the repository root, review the inventory against the actual current checkout, then run:

```powershell
cd C:\Development\Vue\IRAD
git status --short
php artisan test
npm run build
```

No new tests are introduced in 5C.17; the prior reported full-suite baseline is **374 passing tests**. If the review is accepted, commit this planning artifact:

```powershell
git add docs/architecture/MILESTONE-5C17-POSITIONS-INDEPENDENCE-AUDIT.md
git commit -m "Phase 5C.17: Audit Positions module independence and exit criteria"
git push
git status
```

**Risk note:** Do not move `App\Models\Position` or alter migration paths solely because they appear in the inventory. Existing consumers and migration history make that a high-risk change needing a separate design and tests.
