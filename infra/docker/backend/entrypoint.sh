#!/bin/bash
# ------------------------------------------------------------------
# Core Platform — PHP-FPM entrypoint
#
# Runs as root before PHP-FPM drops privileges to www-data.
# Fails fast and loudly on any misconfiguration so developers see
# the problem immediately instead of getting a silent 502.
# ------------------------------------------------------------------

set -euo pipefail

APP_DIR=/var/www/html

log()  { echo "[entrypoint] $*"; }
fail() { echo "[entrypoint] FATAL: $*" >&2; exit 1; }

# ── 1. Validate .env ─────────────────────────────────────────────
if [ ! -f "${APP_DIR}/.env" ]; then
    fail ".env not found. Run: cp backend/.env.example backend/.env && php artisan key:generate"
fi

# ── 2. Validate vendor ───────────────────────────────────────────
if [ ! -f "${APP_DIR}/vendor/autoload.php" ]; then
    fail "vendor/autoload.php not found. Run: docker compose exec app composer install"
fi

# ── 3. Ensure required Laravel directories exist ─────────────────
# These must exist before PHP-FPM starts or Laravel will throw
# on the very first request (views, cache, sessions, logs).
mkdir -p \
    "${APP_DIR}/storage/app/public" \
    "${APP_DIR}/storage/framework/cache/data" \
    "${APP_DIR}/storage/framework/sessions" \
    "${APP_DIR}/storage/framework/testing" \
    "${APP_DIR}/storage/framework/views" \
    "${APP_DIR}/storage/logs" \
    "${APP_DIR}/bootstrap/cache"

# ── 4. Fix ownership ─────────────────────────────────────────────
# PHP-FPM runs as www-data (uid=33). Application files may be
# owned by the host developer (uid=1000) or by root after
# "docker compose exec" commands run without -u www-data.
# This chown is idempotent — it only touches metadata.
chown -R www-data:www-data \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

# Apply permissions: directories get rwx, files get rw (X flag).
chmod -R ug+rwX \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

log "Runtime directories ready, ownership set to www-data."

# ── 5. Hand off to CMD (php-fpm) ─────────────────────────────────
exec "$@"
