#!/bin/bash
set -e

APP_UID=${APP_UID:-1000}
APP_GID=${APP_GID:-1000}

if ! getent group "$APP_GID" >/dev/null 2>&1; then
    groupadd -g "$APP_GID" appgroup 2>/dev/null || true
fi

if ! getent passwd "$APP_UID" >/dev/null 2>&1; then
    useradd -u "$APP_UID" -g "$APP_GID" -m -s /bin/bash appuser 2>/dev/null || true
fi

mkdir -p /var/www/src/storage/logs /var/www/src/bootstrap/cache
chown -R "$APP_UID:$APP_GID" /var/www/src/storage /var/www/src/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/src/storage /var/www/src/bootstrap/cache 2>/dev/null || true

cd /var/www/src
exec php artisan serve --host=0.0.0.0 --port=8000
