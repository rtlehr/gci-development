# Insite Portal — Milestone 4B.1: Module Configuration and Dependency Resolution

## Scope and safety

This incremental patch extends the passive Module Catalog from Milestone 4A. **It never executes a module service provider, loads routes, runs migrations, or turns on a feature.** All existing business functionality remains unchanged. Module enablement here is *selection for validation*, not runtime activation or access authorization.

## Configuration

`config/insite_modules.php` provides a `enabled` list. It is empty by default, so existing installations behave exactly as before. No environment-variable parsing is used here to avoid ambiguous or unsafe configurations. Once module lifecycle support is introduced, deployment tooling can manage customer-specific selection.

## Manifest format

Modules will be stored in `modules/<id>/module.json`:

```json
{
  "id": "finance",
  "name": "Finance",
  "version": "1.0.0",
  "requires": { "positions": "^1.0.0" }
}
```

`requires` is optional and defaults to `{}`. Dependency IDs must match module IDs and cannot refer to themselves. Only stable `major.minor.patch` versions are accepted in this milestone. Supported version constraint formats are exact (`1.2.3` or `=1.2.3`), comparison (`>=1.2.0`, `>`, `<=`, `<`), caret (`^1.2.0`) and tilde (`~1.2.0`). Compound expressions, prerelease versions, wildcards, and OR are **not** supported yet; they are rejected, not silently misinterpreted. The caret handles `0.x` versions conservatively.

## Resolver rules

- All discovered manifests are validated before resolving the selected modules.
- Enabled identifiers must be unique and reference installed modules.
- An enabled module's required dependencies must be installed, enabled, and satisfy the version constraint.
- Circular dependencies are rejected.
- Successful resolution returns module IDs in deterministic dependency-first order.
- An installed but disabled module can exist without its dependencies being installed. Its manifest must still be valid.
- The resolver never loads executable code, grants permissions, or changes routes.

## Files

- `app/Core/Modules/ModuleCatalog.php` (modified): validate stable versions and optional dependency metadata.
- `app/Core/Modules/ModuleVersion.php` (new): limited version constraint evaluator.
- `app/Core/Modules/ModuleDependencyResolver.php` (new): deterministic dependency resolution.
- `app/Providers/ModuleCatalogServiceProvider.php` (modified): DI binding.
- `config/insite_modules.php` (new): default empty selection.
- `tests/Unit/Core/ModuleDependencyResolverTest.php` (new): focused safety and behavior tests.

## Installation and verification (PowerShell)

1. Confirm that you're on `architecture/modular-foundation` at the committed 4A checkpoint and your working tree is clean.
2. Extract this ZIP into `C:\Development\Vue\IRAD`, preserving relative directories.
3. Run:

```powershell
php artisan test tests/Unit/Core/ModuleCatalogTest.php tests/Unit/Core/ModuleDependencyResolverTest.php
php artisan test
npm run build
git status --short
```

The target is **282 tests** (269 baseline plus 13 new tests), assuming no unexpected environmental failures. No database migrations or Composer/npm installs are required.

## Manual frontend smoke test

Visit the normal Insite Portal dashboard, the People directory, a Position detail screen and a Candidate detail screen. Confirm navigation and saving still work, and no module-management screen has appeared. Nothing should be visibly different in this checkpoint.

## Next: 4B.2 (not implemented)

Provider, route and lifecycle registration; module access protection; and rollback-safe deployment validation. Separate **installed**, **enabled**, and **authorized** states will remain mandatory. AI integration stays an optional future module and is not a Core dependency.
