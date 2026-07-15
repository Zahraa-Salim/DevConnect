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

# One-time demo data seeding, toggled from the dashboard via RUN_SEED=true.
# Off by default so it never re-runs on ordinary deploys. Set RUN_SEED=true and
# redeploy to load sample data, then unset it and redeploy again. Non-fatal: a
# seeder error is logged but must not take down the web service.
if [ "$RUN_SEED" = "true" ]; then
    echo ">> RUN_SEED=true: seeding database..."
    php artisan db:seed --force || echo ">> WARNING: db:seed failed (continuing boot)"
fi

# Hand off to supervisord: web server + queue worker + scheduler.
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
