# Insite Portal — QA Identity and Initial Owner Bootstrap

## Normal identity model

Insite Portal does not use passwords for normal application access.

Development identity is selected through `people.person_code`:

```env
IRAD_IDENTITY_DRIVER=development
DEV_USER_ENABLED=true
DEV_PERSON_CODE=1111111
```

`1111111` is the default Owner person code.

The QA profile also uses deterministic person codes. Use the local development user switcher or change `DEV_PERSON_CODE` to exercise a different QA identity.

## Reset profiles

Development:

```powershell
php artisan app:reset-development
```

QA/UAT:

```powershell
php artisan app:reset-qa
```

The QA reset creates identity-based users. There is no shared QA login password.

## Initial production installation

Bootstrap password login is a first-installation mechanism only. It is not an ADFS fallback.

Add/verify these settings for the initial installation:

```env
IRAD_IDENTITY_DRIVER=adfs
IRAD_BOOTSTRAP_LOGIN_ENABLED=true
IRAD_BOOTSTRAP_OWNER_PERSON_CODE=1111111
```

Run migrations and the application's required baseline seeders, then prepare the Owner bootstrap password:

```powershell
php artisan app:bootstrap-owner
```

The command prompts for a temporary password. If person code `1111111` does not yet have a linked User, the command can create the Owner User/Person after the Owner role has been seeded.

When a protected page is requested and ADFS supplies no person code, Insite Portal will redirect to `/login` only while all of the following are true:

1. `IRAD_BOOTSTRAP_LOGIN_ENABLED=true`.
2. Initial setup has not been completed.
3. The login belongs to person code `1111111`.
4. That user has the Owner role.

After login, the Owner is sent to `/setup`. The Owner can open Administration and Site Settings and configure the installation.

When configuration and enterprise identity are ready, choose **Complete Initial Setup**.

Completing setup records a permanent installation-complete timestamp, logs out the bootstrap Owner, and disables password bootstrap authentication. Leaving `IRAD_BOOTSTRAP_LOGIN_ENABLED=true` by mistake does not reopen it.

After completion, set:

```env
IRAD_BOOTSTRAP_LOGIN_ENABLED=false
```

From that point forward, a missing ADFS identity produces the normal identity error and never falls back to password authentication.

## QA person codes

| Role/Purpose | Person Code |
|---|---:|
| Owner | `1111111` |
| Admin | `9000002` |
| Developer | `9000003` |
| COTR | `9000004` |
| PMO | `9000005` |
| Project Manager 1 | `9000006` |
| Project Manager 2 | `9000007` |
| Candidate | `9000008` |
| Restricted QA user | `9000009` |
| Empty-scope Project Manager | `9000010` |
| Intentionally unlinked Person | `9000999` |

## Security behavior

- Seeded development and QA accounts receive random unusable password hashes.
- Normal users authenticate only through the configured identity driver.
- Only the designated Owner may use bootstrap password authentication.
- A real ADFS identity always overrides a bootstrap session.
- Bootstrap login is disabled permanently after the installation-complete state is recorded.
- The bootstrap command refuses to reopen setup after completion.
