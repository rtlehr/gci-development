# Insite Portal Enterprise Identity Integration

## Purpose

Insite Portal does not authenticate enterprise users with application passwords. In normal production operation, the organization's trusted identity infrastructure authenticates the user and supplies one authoritative identifier to Insite Portal.

Insite Portal matches that identifier to:

```text
people.person_code
```

The application then loads the linked User and applies the User's Insite roles and permissions.

## Supported deployment contract

The production identity path is intentionally web-server neutral:

```text
Enterprise identity provider
        ↓
ADFS / SAML / OIDC / Windows Authentication / approved gateway
        ↓
Trusted web server or reverse proxy
        ↓
HTTP_PERSON_CODE=<authenticated identifier>
        ↓
Insite Portal
        ↓
people.person_code
        ↓
User → Roles → Permissions
```

The customer's identity team may source the value from any approved directory attribute. In the Insite development ADFS lab, Active Directory `employeeID` is used and mapped to the Insite `person_code` claim.

## Required Insite configuration

Production `.env`:

```env
IRAD_IDENTITY_DRIVER=adfs
IRAD_ADFS_PERSON_CODE_SOURCE=HTTP_PERSON_CODE
DEV_USER_ENABLED=false
```

`IRAD_ADFS_PERSON_CODE_SOURCE` is the exact PHP/server variable that Insite will trust. Insite deliberately does not fall back through alternate identity headers.

## Security requirement

The browser must not be allowed to choose or override the trusted identity value.

The web server or reverse proxy must:

1. Authenticate the user before the request reaches Insite Portal.
2. Remove any inbound client-supplied header/value that could map to the configured server variable.
3. Inject the authenticated `person_code` itself.
4. Prevent direct network access to an application backend that bypasses that trusted authentication layer.

Do not expose a deployment where a client can send `PERSON_CODE: 1111111` and have that value trusted by PHP.

## ADFS example

A typical ADFS deployment can use this mapping:

```text
Active Directory employeeID
        ↓
ADFS outgoing claim: person_code
        ↓
Trusted web-server integration
        ↓
HTTP_PERSON_CODE
        ↓
Insite Portal people.person_code
```

The exact ADFS relying-party identifier, callback URL, and claim transport are owned by the customer's authentication/web-server integration and may differ between installations. Insite's stable contract is the trusted server variable, not a specific federation protocol.

## Development mode

Normal local development can continue to use:

```env
IRAD_IDENTITY_DRIVER=development
DEV_USER_ENABLED=true
DEV_PERSON_CODE=1111111
```

This bypasses enterprise federation while exercising the same Person/User/Role model.

For full integration testing, use:

```env
IRAD_IDENTITY_DRIVER=adfs
DEV_USER_ENABLED=false
IRAD_ADFS_PERSON_CODE_SOURCE=HTTP_PERSON_CODE
```

and place the local application behind the same type of trusted authentication gateway that will be used in production.

## Deployment diagnostics

Run:

```bash
php artisan insite:identity-check
```

This validates the configured identity driver and trusted source.

To validate database mapping for a known enterprise identifier:

```bash
php artisan insite:identity-check --person-code=1111111
```

The command masks the identifier in its output by default. For a controlled local troubleshooting session only, use:

```bash
php artisan insite:identity-check --person-code=1111111 --show-code
```

### What the CLI command cannot test

A CLI process does not receive the web server's live authenticated request variables. Therefore the command cannot prove that ADFS/IIS/reverse-proxy claim injection is working.

The final production acceptance test must be made through the browser/web-server path:

```text
Browser → enterprise authentication → trusted gateway → Insite Portal
```

The authenticated user's directory identifier should resolve to the expected Insite Person/User and role set.

## Production acceptance checklist

- `IRAD_IDENTITY_DRIVER=adfs`
- `DEV_USER_ENABLED=false`
- `IRAD_ADFS_PERSON_CODE_SOURCE` matches the web server's trusted server variable
- The authentication gateway strips client-supplied copies of the identity header/value
- The application backend cannot be reached by bypassing the gateway
- A valid enterprise person code resolves to the correct Insite user
- An unknown person code is denied
- A missing person code is denied
- A Person without a linked User is denied
- Switching enterprise users switches the Insite authenticated user
- A stale Laravel session cannot override the upstream identity
- Bootstrap Owner login has been disabled/completed after initial installation

## Application behavior

In ADFS mode the upstream identity is authoritative on every protected request. If the identity disappears, Insite does not allow an existing Laravel session to remain authenticated. If the upstream identity changes, Insite switches the Laravel-authenticated user to the newly resolved Person/User.
