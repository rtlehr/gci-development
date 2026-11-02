# Insite Portal — Phase 5B.5: Positions Extraction Readiness

**Type:** Architecture and implementation review only; **no application code, migrations, routes, or permissions changed**.  
**Starting checkpoint:** `a324848` (`architecture/modular-foundation`), as reported and verified by the developer.  
**Reviewed source basis:** Latest provided `5b3.zip` snapshot, supplemented by the delivered Phase 5B.3 and Phase 5B.4 change packages. This is a **reconstructed working snapshot**, not a fresh Git checkout of `a324848`. Validate it against the current repository before moving files.  
**Last developer verification:** 322 passing tests / 1,659 assertions; successful frontend build and browser checks.

## 1. Decision and scope

**Recommendation: proceed to Phase 5C with a Positions module skeleton and startup/route-cache tests, not with bulk file relocation.** The existing boundaries make incremental extraction feasible, but routes, import execution, audit deep links and Recruitment cross-references still couple module code to the host application. Do not treat `INSITE_LEGACY_POSITIONS_ENABLED=false` as proof of a working Core-only deployment: it presently controls transitional adapter/dashboard registration, not all legacy routes, migrations, and services.

The next change should create `modules/positions/` with a manifest, provider, and **non-conflicting** registration, leaving existing Positions routes, models, Inertia pages and historical migrations untouched. Initially avoid registering any duplicate business URLs. Use the existing module catalog, dependency resolution, activation, cache and migration-preflight mechanisms from Phase 4.

## 2. Confirmed progress from 5B.1–5B.4

| Boundary | Current mechanism | Limits |
| --- | --- | --- |
| Core `Person` → Positions | Core contract, legacy relationship adapter | Public `Person` relationship methods remain; some callers may still assume Positions exists |
| Optional legacy relationship binding | `LegacyPositionsBinding` via `AppServiceProvider`; `config/insite_modules.php` | Transitional toggle does not deactivate routes, import processors or other direct references |
| Candidate authorization | Recruitment service plus compatibility methods in `PortalWorkforceAccessService` | Legacy services and controllers still share Position/Candidate models |
| Dashboard service | `WorkforceDashboardProvider` interface, `ProjectManagerDashboardService` and `EmptyWorkforceDashboardProvider` | Workforce service itself still combines position staffing and candidate metrics; contract handles a limited Core dashboard entry point |

## 3. Remaining blockers observed in source

| Priority | Coupling / evidence | Owner / required boundary | Suggested future checkpoint |
| --- | --- | --- | --- |
| P0 | `routes/web.php` unconditionally requires `position.php`, `PositionAssignments.php`, `Candidate.php`, `Workflow.php`, `jobTitles.php`, and `resumes.php` | Move registration by domain **without changing URI, name, methods, middleware or order-sensitive behavior**; gate by actual module activation only after separate tests | 5C skeleton, then 5E route transfer |
| P0 | `routes/portal.php` interleaves `/positions`, `/candidates`, `/job-title-requirements`, position candidate actions, and custom skill/task endpoints | Split route ownership carefully; Position-specific candidate endpoint requires a Recruitment integration policy | 5E |
| P0 | `app/Services/DataImport/ImportExecutionService.php` directly imports and executes `Position`, `Candidate`, `PositionAssignment`, `JobTitle`, `Workflow`; `ImportMappingRegistry` constructs position/person/candidate/assignment destinations | Shared import orchestration stays Core; add module-owned mapping/validation/matching/execution/rollback contributors with explicit compatibility and audit contracts | 5F |
| P0 | Historical migrations in `database/migrations` establish Position, Assignment, Job Title, Skill/Task, Candidate and Workflow tables | **Never move/re-run already-executed migrations without a recorded migration-history conversion**. New module schema changes can use module-owned migration paths | 5G |
| P1 | `app/Services/UserEventLinkResolver.php` queries `Position`, `Candidate`, `Workflow`, `JobTitle` unconditionally; builds direct `/portal/...` destinations | Core audit resolver with optional module contributors; preserve text for historical events with disabled modules, but do not produce unusable URLs | 5F |
| P1 | `app/Services/ProjectManagerDashboardService.php` queries Positions and Candidates and derives staffing summaries and workflow data | Keep legacy provider until tested replacement; eventually distinct Positions/Recruitment dashboard contributions | 5D/5F |
| P1 | `app/Models/Position.php` references `Candidate` relationships; `app/Http/Controllers/PositionCandidateController.php` acts across domains | Recruitment-owned candidate extension; Positions works without Recruitment; explicit authorization boundary | 5F |
| P1 | `app/Http/Controllers/Portal/PeopleController.php` imports Resume/ResumeFormat; People screens may offer module tabs | Core People must remain usable without Resumes/Positions; optional tabs and server props need availability-aware handling | 5F |
| P1 | `app/Services/PortalWorkforceAccessService.php` and other legacy callers still mix workforce and recruitment concerns | Preserve existing permission semantics; make domain service calls explicit and ensure old API wrappers can eventually be removed safely | 5D/5F |
| P2 | `app/Support/ListDefinitions/PositionsDefinition.php`, Position/JobTitle controllers, and `resources/js/pages/Portal/Positions/*` remain in host namespaces/layout | Move only after route and Inertia component name resolution is validated; avoid changing public page names silently | 5D/5E |

This is an ownership inventory, **not** a claim that every reference is wrong or must move into the first skeleton patch.

## 4. Recommended ownership and dependency graph

- **Core:** People, Organizations, identity, authorization engine, shared UI, module catalog/activation, generic import orchestration, generic audit/event infrastructure, general dashboards.
- **Positions:** Positions, assignments, job titles and requirements, staffing calculations, Position-specific import contributor, Position audit links, Position routes and UI.
- **Recruitment:** Candidates, candidate steps and selection workflows (subject to final confirmation of any non-Recruitment workflow consumers), candidate-specific import contributor and audit links, candidate routes and UI.
- **Resumes:** Resume storage, parsing, indexing, person resume UI and optional matching integration.

**Proposed dependency direction:** Core is required; Positions depends on Core; Recruitment depends on Core **and Positions** for candidate-to-position workflows as currently implemented; Resumes depends on Core, with optional Positions/Recruitment integrations. Do not declare these manifest dependencies until the module catalog and required-feature behavior are tested. Avoid any hard Core → optional module dependency.

## 5. Exact extraction sequence

1. **5C.1 — Manifest and provider skeleton:** Add `modules/positions/module.json` and module provider with no ownership of legacy routes, migrations or business services yet. Use the same namespace/provider conventions as `modules/demo/`. Verify enable/disable, dependency errors, status, route cache and startup. Ensure the module is not enabled by default in existing installs.
2. **5C.2 — Route/permission baseline lock:** Capture `php artisan route:list --json` before moving any routes. Record route name, URI, HTTP methods, middleware and related permissions; test existing access control. Pay attention to portal-prefixed route names versus similarly named legacy routes.
3. **5D — Read/assignment service extraction:** Move Position read operations behind a module service/contract while leaving compatible host-facing controllers/models in place initially. Check core People and both dashboard provider modes.
4. **5E — Route/controller ownership transfer:** Transfer one route cluster at a time without duplicate registration, preserving its exact contract and Inertia page name. Compare route snapshot each time, including cached-route behavior.
5. **5F — Integrations:** Separate Recruitment candidate links, Resumes UI/matching, import mapping/execution/rollback contributors and audit link resolvers; verify missing-module and permission-denied states explicitly.
6. **5G — Migration/data lifecycle:** Keep existing migrations in historical path unless a rigorously tested migration history compatibility plan is approved. Add only genuinely new schema migrations under module paths; test fresh and upgraded installations on isolated test DBs.

## 6. Installation scenarios to prove

| Scenario | Expected behavior and constraints |
| --- | --- |
| Core only | Login/identity, People, Organizations, permissions, generic dashboard, audit and shared services operate without requiring business-model tables. No Positions/Recruitment routes should leak (once route extraction is complete). |
| Core + Positions | Positions, assignments and job titles are accessible to permitted users; no Recruitment functionality or data dependency is required. |
| Core + Positions + Recruitment | Existing candidates, workflows, authorization scopes, dashboard content and integrations preserved. |
| Core + Positions + Recruitment + Resumes | Full current functionality, with resume links/search/matching preserved. |
| Disabled after prior activation | Data remains stored, historical audit labels survive, inaccessible module routes remain guarded even with stale route caches. |

Phase 5B.5 does **not** establish that any of these deployment matrices already pass. They are exit criteria for later phases.

## 7. Non-negotiable regression gates

- `php artisan test` (last verified developer baseline **322 / 1,659**), plus newly added installation-matrix tests per checkpoint.
- `npm run build` and smoke tests for People, Positions index/detail/edit, staffing matrix, CSV, PMO/project-manager dashboard, Candidates and Resumes.
- Capture and compare route snapshots before and after each route move; `php artisan route:cache`, `php artisan route:clear`, and `php artisan route:list --json` (use PowerShell file redirection to save baselines).
- Run existing `insite:modules:status` and `insite:modules:preflight` commands and test known disabled-module behavior.
- Confirm PMO versus project-manager versus candidate self-access and explicit denials. Permission checks must remain enforced regardless of module activation.
- Verify no schema deletion or historical migration re-execution; use only the isolated `irad_testing` database for migration lifecycle tests.
- Test Windows and case-sensitive path/autoload assumptions before deploying modules outside the current development environment.

### Suggested baseline capture for the next code checkpoint (PowerShell)

```powershell
php artisan route:list --json > routes-before-5c.json
php artisan insite:modules:status
php artisan insite:modules:preflight
php artisan test
npm run build
```

Store the route snapshot in a safe local review location; decide separately whether it should be committed as a baseline fixture (avoid committing machine-specific/generated secrets). Commands shown are read-only or test/build operations, except for application caches/build outputs ordinarily managed by the project.

## 8. Open decisions before the first move

1. Confirm the exact legacy route inventory from the current checked-out commit and which routes are aliases versus distinct endpoints.
2. Confirm that Workflow/WorkflowStep entities have no important consumers outside Recruitment before assigning ownership permanently.
3. Determine whether Position-specific skills/tasks and Job Titles always belong to Positions; treat current recommendation as provisional until confirmed by functional scope.
4. Specify the disabled-module UX for optional People/Resume/Positions tabs and audit links.
5. Preserve the existing custom permissions and role scopes without requiring business modules to register privileges on Core-only installations.
6. Choose an explicit module state transition process for existing customer installations that have legacy Positions data; **do not infer installation state just from whether tables exist**.

## 9. Deliverable and acceptance

This checkpoint ships **one new Markdown document only**, under `docs/architecture/`. The developer can review and commit it without rerunning PHP tests or a Vite build because no code or configuration was modified. Once approved and committed, Phase 5C.1 should be a small **module skeleton** patch with automated tests and no live route or migration takeover.
