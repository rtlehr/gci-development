# Milestone 4B.2 — Controlled Module Activation

## Scope

Introduces trusted local module providers and route loading. Existing business routes remain unchanged. Demo is **disabled by default**. The `module.json` `provider` field identifies an explicitly autoloaded class extending Laravel `ServiceProvider`.

## Security and lifecycle

- Dependencies and module provider classes are validated before registering any provider.
- Only modules listed in `config/insite_modules.php` are activated.
- Module routes **must** use `insite-module:<id>` as defense-in-depth for stale/cached routes, in addition to their actual authorization rules (`auth`, permissions, etc.). The alias alone does **not** authorize a user.
- A disabled module is not dynamically unregistered from a running PHP process. Deployments must rebuild route/config caches and restart long-lived workers after changes.
- Module service providers execute trusted installed PHP code. Installing unreviewed modules is equivalent to installing executable application code.
- No database migrations, user interface, or customer business module was introduced.
- Optional AI can be packaged as an independent module later.

## Verification

1. Extract patch preserving paths. Run `composer dump-autoload` (new PSR-4 namespace).
2. `php artisan test tests/Feature/Core/ModuleActivationTest.php`
3. `php artisan test` and `npm run build`.
4. Default `config/insite_modules.php` selection remains empty. Optional local demonstration: temporarily set `enabled` to `['demo']`, clear config and route caches, and visit `/_insite/modules/demo/health` (public, non-sensitive test endpoint only). Restore the empty list afterward.
5. Run `git status --short`; do not commit before validation.

**Do not enable the demo module on a production customer installation.** This is infrastructure testing, not a production module-management feature.
