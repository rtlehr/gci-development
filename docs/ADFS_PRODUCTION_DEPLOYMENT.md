# IRAD ADFS Production Deployment

## Purpose

IRAD does not use a traditional sign-in page. In production, ADFS/web-server authentication must identify the current person and expose the user's IRAD `person_code` to PHP as one trusted server variable.

IRAD then resolves:

`ADFS claim -> people.person_code -> Person.user_id -> User -> Laravel Auth / CurrentUserContext`

`people.person_code` remains the authoritative identity key in both development and production.

## Required production environment

```env
APP_ENV=production
APP_DEBUG=false

IRAD_IDENTITY_DRIVER=adfs
IRAD_ADFS_PERSON_CODE_SOURCE=HTTP_PERSON_CODE
```

Replace `HTTP_PERSON_CODE` with the exact server-variable name agreed with the ADFS/web-server team.

Do **not** use `IRAD_IDENTITY_DRIVER=development` in production. IRAD deliberately refuses development identity on protected production routes.

## Information required from the ADFS / web-server team

Before deployment, confirm all of the following:

1. ADFS emits a claim whose value exactly matches IRAD `people.person_code`.
2. The web server maps that claim to a PHP server variable.
3. The exact PHP server-variable name is known.
4. The value is available on every protected IRAD request.
5. Anonymous/untrusted clients cannot supply or override that value.
6. If an HTTP header is used internally, the trusted web server or reverse proxy removes any incoming client copy before injecting the authenticated claim.
7. IRAD is served over HTTPS.

IRAD does not need to know which AD attribute or ADFS claim rule produced the value. It only requires the final value to match `people.person_code`.

## Identity outcomes

### Claim is present and configured

IRAD finds the matching Person, loads the linked User, and establishes Laravel authentication for the request.

### Claim is missing

Protected requests return HTTP 401 with:

`Unable to identify your network account`

IRAD does not fall back to a development user or an old Laravel session.

### Claim does not match a Person

Protected requests return HTTP 403 with:

`Your account is not configured in IRAD`

### Person exists but has no linked User

Protected requests return HTTP 403 with:

`Your IRAD account is incomplete`

### Identity configuration is invalid

Protected requests return HTTP 500 with:

`IRAD identity configuration error`

## Pre-production validation

First verify the IRAD database contains the expected relationship:

```text
people.person_code = <ADFS claim value>
people.user_id      = <valid users.id>
```

On the server, set:

```env
IRAD_IDENTITY_DRIVER=adfs
IRAD_ADFS_PERSON_CODE_SOURCE=<actual server variable>
```

Then clear cached Laravel configuration:

```powershell
php artisan optimize:clear
```

Run the identity tests before deployment:

```powershell
php artisan test tests/Feature/AuthenticationContext
```

## Acceptance test with ADFS

Use at least three real test accounts.

### Known authorized user

Expected:
- IRAD opens without a login screen.
- The correct Person/User is resolved.
- Portal permissions match that user's IRAD permissions.
- User Event Log entries are attributed to that user.

### Known person without IRAD access

Expected:
- ADFS authenticates the person.
- IRAD returns the controlled account-configuration error.
- IRAD does not substitute another user's session.

### Missing/removed claim test

Expected:
- Protected IRAD routes return the controlled 401 identity error.
- A previously authenticated Laravel session cannot bypass the missing ADFS claim.

## Troubleshooting

IRAD writes structured identity failures to the normal Laravel log without writing the raw unknown `person_code`. Unknown codes are represented by a SHA-256 hash for correlation.

Check:

```text
storage/logs/laravel.log
```

Useful messages include:

- `IRAD did not receive a person_code from ADFS.`
- `ADFS supplied a person_code that is not configured in IRAD.`
- `ADFS person_code resolved to a Person without a linked User.`
- `IRAD could not read the configured ADFS person_code source.`

## Development remains unchanged

Local development continues to use:

```env
IRAD_IDENTITY_DRIVER=development
DEV_USER_ENABLED=true
DEV_PERSON_CODE=<developer person_code>
```

The local development user switcher remains available. Production ADFS configuration does not change the development workflow.
