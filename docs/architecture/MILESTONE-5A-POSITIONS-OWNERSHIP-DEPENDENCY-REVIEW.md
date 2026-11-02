# Insite Portal — Milestone 5A: Positions Ownership and Dependency Review

**Status:** Proposed architecture checkpoint; analysis only — no application code changes.  
**Source reviewed:** `newTest.zip`, package manifest `IRAD`, branch `architecture/modular-foundation`, commit `4c0e0a578a78`, working tree `modified`, packaged 2026-11-02.  
**Prior verified baseline reported by developer:** 308 passing tests / 1,608 assertions at commit `922f6d5` before subsequent packaging-script commits. **Do not assume** the supplied snapshot has the same Git revision or test outcome until checked locally.

## 1. Objective and non-goals

Define a safe extraction boundary for Positions without changing URLs, route names, identity, role/permission behavior, existing database tables, data import or UI. This checkpoint is **documentation only**. No migrations are moved or re-run. No modules are enabled or disabled. No operational changes are authorized by this document.

## 2. Proposed module ownership

| Area | Proposed owner | Concrete source examples | Boundary notes |
| --- | --- | --- | --- |
| Position records, staffing status, history | Positions | `app/Models/Position.php`, `PositionActivity.php`, `app/Services/PositionService.php` | Preserve existing tables, IDs, relationships, and event behavior. |
| Assignments of People to Positions | Positions | `app/Models/PositionAssignment.php`, `app/Http/Controllers/PositionAssignmentsController.php` | References Core `Person` and `User` by ID; do not move People. |
| Job titles, requirements, tasks, skills | Positions | `JobTitle.php`, `JobTitleSkill.php`, `JobTitleTask.php`, `PositionCustomSkill.php`, `PositionCustomTask.php` and associated controllers/routes/pages | Used in position creation/editing and matching; publish read-only contracts for other modules. |
| Position UI and lists | Positions | `resources/js/pages/Portal/Positions/*`, `resources/js/pages/Positions/*`, `resources/js/components/positions/PositionFormFields.vue`, `app/Support/ListDefinitions/PositionsDefinition.php` | Preserve existing Inertia page names until routes and asset resolution are validated. |
| Candidates and selection processes | Recruitment | `app/Models/Candidate.php`, `CandidateStepEvent.php`, `app/Http/Controllers/Portal/CandidateController.php`, `routes/Candidate.php`, `resources/js/pages/Portal/Candidates/*` | Recruitment depends on Positions; avoid loading candidate code when Recruitment disabled. |
| Candidate workflows | Recruitment (provisional) | `app/Models/Workflow.php`, `WorkflowStep.php`, `WorkflowStepStatus.php`, `routes/Workflow.php` | Confirm whether workflows have non-Recruitment consumers before finalizing owner. |
| Resumes and parsing | Resumes | `Resume.php`, `ResumeEntry.php`, `ResumeFormat.php`, `app/Services/Resumes/*`, `routes/resumes.php` | Resume service should function without Positions/Recruitment; matching integration optional. |
| Person identity and contact information | Core | `app/Models/Person.php`, `User.php` | Core must function without Positions; remove hard dependencies through contracts when implementing extraction. |
| Organizations, authentication, permissions registry and UI shell | Core | Existing Core models, middleware, providers, navigation and shared components | Module-specific permissions registered through Core facilities, but authorization stays enforced. |
| Shared importing, exporting, event logging and base dashboard infrastructure | Core platform services | `app/Services/DataImport/*`, `app/Services/UserEventLinkResolver.php`, `app/Http/Controllers/Portal/DashboardController.php` | Business-specific handlers move behind module adapters/contributors; do not move the entire shared engine. |
| Workforce dashboard data providers | Positions, with Recruitment contribution | `app/Services/ProjectManagerDashboardService.php`, `resources/js/components/dashboard/*Positions*`, `CandidateOpportunitiesCard.vue` | Split staffing metrics from candidate progress; keep dashboard shell in Core. |
| Support, CMS and Page Help | Independent optional modules | Existing Ticket, ContentPage, Page Help controllers/routes/pages | Out of scope for 5A; ensure no unnecessary Positions dependency. |

## 3. Direct dependencies found in the supplied source

1. **Core Person → Positions:** `app/Models/Person.php` imports `PositionAssignment` and `Position`, and exposes `assignments()` and `positions()`. A Core-only install must not have required code paths pointing into an absent Positions module. Plan a module-aware relation extension or separate query interface; preserve existing API during migration.
2. **Positions ↔ Recruitment:** `app/Models/Position.php` defines `candidates()` and `app/Services/PortalWorkforceAccessService.php` imports both `Position` and `Candidate`. The service implements `scopePositions`, `scopeCandidates`, `authorizePositionView`, and `authorizeCandidateView`. Split by domain while preserving *all* permission and record-scope decisions, including candidate personal access.
3. **Dashboard mixes domains:** `app/Services/ProjectManagerDashboardService.php` provides `positionsFor`, `allPositionsForPmo`, and `candidateOpportunitiesFor`. `app/Http/Controllers/Portal/DashboardController.php` selects among staffing and candidate opportunities. Move contributions behind optional module interfaces; dashboard remains available with no business modules installed.
4. **Imports use business model classes directly:** `app/Services/DataImport/ImportExecutionService.php`, `ImportRollbackService.php`, `ImportRecordMatcher.php`, and `ImportValidationService.php` reference Position/Candidate/PositionAssignment. Preserve workflow and rollback semantics through module-provided handlers, rather than moving the entire Core import engine.
5. **Audit deep links:** `app/Services/UserEventLinkResolver.php` directly imports Position/Candidate. Use optional registered entity-link resolvers; historic audit entries should remain readable with disabled modules and avoid broken code paths.
6. **Position-related route registration is distributed:** `routes/portal.php` contains Portal Positions, Job Titles, candidate actions, and staffing endpoints; `routes/position.php`, `routes/PositionAssignments.php`, `routes/jobTitles.php` contain non-portal and legacy compatibility URLs. `routes/web.php` currently requires these files. Keep URL paths, names, authorization middleware and legacy redirects stable during extraction.
7. **Duplicate-looking route names need audit:** `routes/portal.php` and `routes/jobTitles.php` both declare names ending in `positions.custom-skills.store` and related actions, but the Portal route group adds the `portal.` prefix. These names are **not necessarily actual collisions**; confirm using `php artisan route:list --json` before changing anything. Avoid introducing collisions during consolidation.
8. **Existing migrations must remain in recorded history:** Core migration directory includes `280_create_positions_table.php`, `290_create_position_assignments_table.php`, `300_create_candidates_table.php`, `310_create_candidate_step_events_table.php`, `330_create_position_activities_table.php`, `360_create_position_custom_skills_table.php`, `370_create_position_custom_tasks_table.php`, and more historical schema edits. **Do not physically move previously executed migrations to module paths without an explicit migration-history transition plan.** New module migrations may be module-owned under 4C.4/4C.5 framework.

## 4. Module contracts to establish before moving business code

| Contract | Owner / consumer | Requirement |
| --- | --- | --- |
| `PositionLookup` / `PositionReadService` (name provisional) | Positions provides; Recruitment, Resumes, dashboards consume | Query by position ID/code, summary, status and permitted visibility; no external Eloquent model dependency. |
| `PositionAssignmentReader` | Positions provides; Core People UI optional consumer | List assignments for a Person ID; absent module returns empty/unavailable state, not an exception. |
| `RecruitmentPositionExtension` | Recruitment provides; Positions UI consumes optionally | Candidate count/progress and candidate actions only when enabled and authorized. |
| `DashboardContribution` | Positions and Recruitment provide; Core aggregates | Optional widgets and counts without static business class imports in the Core dashboard. |
| `ImportWorkflowHandler` | Core defines; business modules provide | Map/validate/execute/rollback handlers, preserving the current exact audit and rollback behavior. |
| `AuditSubjectLinkResolver` | Core defines; business modules provide | Safe link resolution for installed modules; disabled module remains represented without linking to unavailable routes. |
| Permission and navigation registration | Core registry, module contributions | Existing permission names and URLs remain stable; no permission bypass on module disable. |

These interface names are design proposals, **not classes that already exist**.

## 5. Recommended safe extraction sequence

**5A — This checkpoint: Inventory and architecture decisions.** Commit only this document after review. Snapshot route names, route middleware, database table names, source-owner map, and regression tests before functional edits.

**5B — Introduce extension contracts in Core, no feature move.** Add read-only module contribution interfaces/registrations and unit tests with zero enabled modules. Keep legacy code active; prove identical output when demo/Positions module disabled.

**5C — Establish Positions module skeleton.** Add manifest/provider/permissions/routes placeholder in `modules/positions/`; do **not** register duplicate routes or migrate existing data. Prove Core-only startup and route caching.

**5D — Move Position read services behind contracts.** Keep legacy wrappers as compatibility adapters; preserve permissions and dashboard output. Tests must cover People linkages and staffing visibility.

**5E — Move Positions UI/controller route ownership incrementally.** Preserve route names, URLs, middleware, Inertia component names, and legacy redirect semantics. Resolve exact route duplication risks before enabling module routes.

**5F — Isolate integration points.** Move or adapt candidate links, shared import handlers, audit links, resume matching and dashboard contributions. Validate Positions only, Positions + Recruitment, and Core only.

**5G — Database ownership transition plan.** Historical migrations remain tracked and executable on existing installs; any new migrations belong to module. Test against both existing upgraded databases and clean isolated installs, with backup and rollback plans.

## 6. Regression gates for each code-changing checkpoint

- `php artisan test` — must preserve baseline and pass new targeted tests.
- `npm run build` — Vite build and Inertia resolution.
- `php artisan route:list --json` — compare names, methods, URIs, and middleware with the saved baseline.
- `php artisan route:cache` plus `php artisan route:clear` — no duplicate or missing routes.
- `php artisan insite:modules:preflight` and `php artisan insite:modules:status` — known configuration.
- Browser smoke test: Portal Dashboard, People, Positions index/detail/edit, Job Titles/requirements, Position Assignments, Candidates and Resumes.
- Permission smoke tests: PMO/all positions, project-manager assigned positions, candidate-own opportunities; unauthorized requests denied.
- Disabling a module must not drop tables, run migrations, delete records, or expose old cached routes.

**Do not run** any potentially destructive migrations against the regular development database as part of extraction tests; use `irad_testing` and verify the connection before execution.

## 7. Follow-up decisions / risks

1. Confirm Job Title scope: currently Jobs Titles are tightly used with Positions; provisional owner Positions.
2. Confirm workflow scope before assigning all workflow models to Recruitment; inspect external callers.
3. Decide whether Core People screens should show optional Positions/Resumes tabs or omit them when modules disabled.
4. Decide the precise installation matrix and permissions for Core-only, Positions-only, and Positions+Recruitment+Resumes.
5. Source package manifest states a **modified working tree** and commit `4c0e0a578a78`; obtain a fresh clean snapshot before first file move if local repository has advanced.
6. Verify Composer namespace/directory casing for module providers on case-sensitive environments before production deployment.

## 8. Files changed by this deliverable

Only this Markdown document is new. No project code, configuration, database schema, module manifest, or automated tests are changed.

### Suggested review / commit

```powershell
git status --short
# Place this document in docs/architecture/ and review it.
git add docs/architecture/MILESTONE-5A-POSITIONS-OWNERSHIP-DEPENDENCY-REVIEW.md
git commit -m "Milestone 5A: Document Positions module ownership and dependencies"
git push
```

Commit **only after** the proposed boundaries and decisions have been reviewed.
