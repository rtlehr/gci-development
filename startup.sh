#!/usr/bin/env bash
set -euo pipefail

APP_ROOT=/home/site/wwwroot
NGINX_SITE=/etc/nginx/sites-available/default

if [ ! -f "$APP_ROOT/public/index.php" ]; then
    echo 'ERROR: Laravel public/index.php not found. Deployment package is incomplete.' >&2
    exit 1
fi

if [ ! -f "$APP_ROOT/insite-nginx.conf" ]; then
    echo 'ERROR: insite-nginx.conf missing from deployment package.' >&2
    exit 1
fi

cp "$APP_ROOT/insite-nginx.conf" "$NGINX_SITE"
nginx -t
service nginx reload

echo 'Insite Portal Nginx configuration loaded. Public root: /home/site/wwwroot/public'
