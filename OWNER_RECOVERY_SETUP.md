# Insite Portal Owner Recovery Access

Owner Recovery Access is an emergency local login for the designated Insite Owner when ADFS authenticates a network user but Insite cannot map that identity to a valid application user.

Normal Insite users still do **not** have local passwords. ADFS remains the normal and authoritative authentication method.

## Enable the feature

Add these values to `.env`:

```env
IRAD_OWNER_RECOVERY_ENABLED=true
IRAD_OWNER_RECOVERY_PERSON_CODE=1111111
IRAD_OWNER_RECOVERY_SESSION_MINUTES=30
```

Then clear cached configuration:

```powershell
php artisan optimize:clear
```

## Set or rotate the Owner recovery password

Run:

```powershell
php artisan app:owner-recovery-password
```

The command uses the Insite user linked to `person_code=1111111` and requires that user to have the `owner` role.

The password is stored only as a one-way Laravel password hash in the database. Do **not** place the plaintext password in `.env`.

To update the Owner email used as the recovery username at the same time:

```powershell
php artisan app:owner-recovery-password --email=owner@example.com
```

Store the plaintext recovery password in the organization's approved password vault.

## What the user sees

When ADFS does not provide a usable Insite identity, a protected request is redirected to:

```text
/owner-recovery
```

The page displays **You do not have access** and, when Owner Recovery Access is enabled, shows the Owner email/password form.

A successful recovery login redirects the Owner to the Insite user-access administration page so users, roles, and permissions can be corrected.

## Security behavior

- Only the configured Owner `person_code` may use recovery credentials.
- The user must also have the Insite `owner` role.
- Recovery login attempts are rate limited.
- Successful and failed attempts are written to the application log.
- Recovery sessions expire after the configured number of minutes.
- A red **Owner Recovery Session** banner is displayed while recovery access is active.
- A valid mapped ADFS identity immediately overrides and clears a recovery session.
- Normal users never authenticate with Insite passwords.

## Exit recovery mode

Use **Exit recovery session** in the red banner. The local Owner session is logged out and the browser returns to the access screen.
