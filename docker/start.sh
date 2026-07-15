#!/usr/bin/env sh
set -e

cd /app

# Cache framework config/routes/views at boot (runtime), so real env vars are
# present. env() calls outside config files are null once config is cached —
# app code has been fixed to read from config() instead.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Sessions, cache, and queue all use database drivers, so the schema must exist
# before the first request. Safe/idempotent on subsequent boots.
php artisan migrate --force

# Hand off to supervisord: web server + queue worker + scheduler.
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
