# Insite Portal Authentication Gateway — Production Deployment

## Final architecture

```text
Browser
  -> HTTPS Insite Authentication Gateway
  -> AD FS / enterprise identity
  -> signed WS-Federation token
  -> required person-code claim
  -> gateway strips inbound PERSON_CODE and injects trusted PERSON_CODE
  -> loopback/private Laravel backend
  -> HTTP_PERSON_CODE
  -> people.person_code
  -> Insite User / Roles / Permissions
```

The customer identity team owns AD/ADFS. The Insite installer does not create or administer the customer's directory or federation farm.

## Customer identity team handoff

Provide these four installation-specific values:

1. AD FS federation metadata URL.
2. Insite relying-party realm/identifier, e.g. `https://insite.example.org/`.
3. WS-Federation reply URL, normally `https://insite.example.org/signin-wsfed`.
4. Claim URI that carries the Insite person code, e.g. `https://insite.example.org/identity/claims/person_code`.

Their claim rule should map the authoritative directory identifier (for example `employeeID`) to that URI claim. A bare claim name such as `person_code` is not valid for this WS-Federation setup; use a URI-style claim type.

## Insite Portal production .env

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://insite.example.org
ASSET_URL=https://insite.example.org

IRAD_IDENTITY_DRIVER=adfs
DEV_USER_ENABLED=false
IRAD_ADFS_PERSON_CODE_SOURCE=HTTP_PERSON_CODE

# If gateway and Laravel are on the same machine:
IRAD_TRUSTED_PROXIES=127.0.0.1,::1

IRAD_ENFORCE_HTTPS=true
IRAD_HSTS_ENABLED=true
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

Never set `IRAD_TRUSTED_PROXIES=*` on an Internet-facing system.

After editing `.env`:

```powershell
php artisan optimize:clear
php artisan insite:identity-check
```

Validate a known mapping during installation:

```powershell
php artisan insite:identity-check --person-code=<known-person-code>
```

## Backend isolation

The PHP/Laravel backend must not be directly reachable by normal clients. Bind it to loopback (`127.0.0.1`) when the gateway is on the same computer, or to a private application network/firewall rule when the gateway is separate.

This is mandatory because Laravel trusts the identity header only after it has passed through the gateway.

## Gateway security defaults

The packaged gateway:

- requires a valid authenticated AD FS session;
- requires the configured person-code claim before proxying;
- removes any inbound `PERSON_CODE` value;
- writes its own `PERSON_CODE` from the validated claim;
- replaces spoofable client `X-Forwarded-*` headers through YARP's default proxy behavior;
- preserves the public Host and supplies forwarded scheme/host information to Laravel;
- disables `/gateway/whoami` diagnostics by default in production;
- exposes only `/gateway/health` without authentication.

## Production certificate requirements

Use a normal enterprise/public TLS certificate for the Insite hostname. Do not use the development self-signed `insite.local` certificate in production.

The gateway machine must trust the AD FS HTTPS certificate chain. If the customer uses an internal enterprise CA, install the CA chain into the Windows Local Computer trusted stores.

## Time synchronization

AD FS token validation is time-sensitive. Production domain members should use the customer's normal AD time hierarchy. Do not disable token lifetime validation or enlarge clock-skew settings to hide a clock problem.

## Smoke test

1. `https://<insite-host>/gateway/health` returns `status=ok`.
2. Browse to the Insite URL and authenticate through the customer's AD FS.
3. Confirm the expected Insite user/role is shown.
4. Test a second AD account mapped to a different `people.person_code`.
5. Test an AD account with no matching Insite Person and confirm access is rejected.
6. Confirm the backend port cannot be reached from a normal client workstation.
7. Confirm generated asset/route URLs use the public HTTPS Insite hostname.
