# IRAD Encryption Production Deployment

IRAD uses field-level application encryption for selected database values and HTTPS/TLS for decrypted data sent from Laravel to an authorized browser.

## Required production settings

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-irad-host.example

IRAD_ENCRYPTION_DRIVER=laravel
IRAD_ENCRYPTION_KEY_VERSION=1
IRAD_ENCRYPTION_LOOKUP_KEY=<dedicated-random-secret>
IRAD_ENCRYPTION_ALLOW_PLAINTEXT_FALLBACK=false

IRAD_ENFORCE_HTTPS=true
IRAD_HSTS_ENABLED=true
IRAD_HSTS_MAX_AGE=31536000
IRAD_HSTS_INCLUDE_SUBDOMAINS=true
IRAD_HSTS_PRELOAD=false

SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

`IRAD_ENCRYPTION_LOOKUP_KEY` must be stored and backed up with the same care as `APP_KEY`. It should be separate from `APP_KEY` so application-key rotation does not invalidate deterministic lookup hashes.

## Validate before go-live

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan irad:encryption-health --strict
php artisan test
```

The health check should exit successfully before production traffic is enabled.

## TLS termination / reverse proxy

If HTTPS terminates at a load balancer or reverse proxy, ensure Laravel receives trusted forwarded-protocol information so `$request->isSecure()` is true for HTTPS clients. Do not disable `IRAD_ENFORCE_HTTPS` to work around incorrect proxy configuration.

## Attachments

New Person attachments are stored on Laravel's private `local` disk and are served only through an authenticated, authorized IRAD route. Browser payloads do not expose filesystem disk names, stored filenames, or server paths.

Older attachments that were historically written to the public disk should be moved to private storage before production if any exist.

## Key backup

Loss of `APP_KEY` can make Laravel-encrypted data unrecoverable. Loss of `IRAD_ENCRYPTION_LOOKUP_KEY` prevents exact lookup of encrypted searchable identifiers until lookup hashes are rebuilt from decrypted values. Back up both secrets using the organization's approved secrets-management process; do not store them in source control or database backups.
