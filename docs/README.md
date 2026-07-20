# IRAD-008-003 Hotfix 001

## Purpose

Updates the two outdated guest-redirect tests so they match IRAD's current identity architecture.

The `/` and `/dashboard` routes intentionally resolve their user through `UserResolver`; they are not protected by Laravel's standard `auth` middleware because local development relies on the configured development identity.

## Replaced files

- `tests/Feature/DashboardTest.php`
- `tests/Feature/ExampleTest.php`

## Coverage

The replacement tests verify that:

- an explicitly authenticated test user can open `/dashboard`;
- an explicitly authenticated test user can open `/`;
- both routes render the `Dashboard` Inertia component;
- both responses provide `alerts` and `assignedTickets` props.

Existing `TestEnvironmentIsolationTest.php` continues to verify that `actingAs()` takes precedence over development identity configuration and that tests do not auto-authenticate guests.

## Install

Extract this ZIP into the IRAD project root and allow the two existing test files to be replaced.

Then run:

```powershell
php artisan optimize:clear
php artisan test
```
