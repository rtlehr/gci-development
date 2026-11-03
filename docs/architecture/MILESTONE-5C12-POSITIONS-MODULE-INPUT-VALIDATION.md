# Phase 5C.12 — Module-owned Positions Input Validation

## Starting point

Continue after 5C.11 has been installed and validated. Confirm its Git commit and clean working tree before committing 5C.12. Baseline target: **359 tests** after the 5C.11 additions.

## Objective

Avoid duplicating the Positions create/update validation rules in both extracted module controllers. Make the module own one source of truth for input validation while retaining the different legacy and Portal requirements.

## Changes

- Add `Insite\Modules\Positions\Services\PositionInputValidator` with the existing complete validation rules and the existing `PositionOperations::assertEligibleProjectManager` check.
- Switch module-owned `PositionsController` to call `PositionInputValidator::validate(..., false)`; legacy validation **does not** apply custom-field rules.
- Switch module-owned `Portal\PositionController` to call `PositionInputValidator::validate(..., true)`; this preserves the existing `CustomFieldService::rules('position')` behavior for Portal.
- Remove duplicated private validation rule arrays from the extracted controllers; preserve their private `validatePosition` methods as wrappers to minimize change.
- Preserve host-owned controllers and their validators untouched for unselected installations.

## Unchanged

Routes, middleware, permissions, data models, database schema, migrations, Vue screens, and both service implementations remain unchanged. This is an internal module refactor. The validator intentionally continues consuming shared Core models, custom fields and the `PositionOperations` interface; module independence is not yet complete.

## Tests

Adds three Pest regression tests for controller delegation, existing field validation, and Portal-only custom field validation. The complete test suite target is **362 tests** (359 + 3) if the previous milestones are installed as expected.

```powershell
php artisan test tests/Feature/Core/PositionsModuleInputValidationTest.php tests/Feature/Core/PositionsModulePortalControllerExtractionTest.php tests/Feature/Core/PositionsModuleLegacyControllerExtractionTest.php tests/Feature/Portal/PortalPositionTest.php
php artisan test
npm run build
git status --short
```

Manually verify a legacy Position form and Portal Position form, especially required fields, project manager eligibility, and Portal custom fields, with appropriate test identities. Do not commit until local tests and browser verification pass.
