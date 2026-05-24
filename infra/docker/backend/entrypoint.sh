#!/bin/sh
# ------------------------------------------------------------------
# Core Platform — PHP-FPM entrypoint
#
# Runs before PHP-FPM starts on every container boot.
#
# Why this is necessary:
#   PHP-FPM runs as www-data (uid=33). The application files are
#   volume-mounted from the host where the developer's UID (typically
#   1000) owns storage/ and bootstrap/cache/. Artisan commands run via
#   "docker compose exec" execute as root, further mixing ownership.
#
#   Laravel needs write access to these directories for:
#     - Compiled Blade views     (storage/framework/views/)
#     - File-based cache         (storage/framework/cache/)
#     - Session files            (storage/framework/sessions/) [if file driver]
#     - Application logs         (storage/logs/)
#     - Bootstrap package cache  (bootstrap/cache/)
#
#   Without this fix, tempnam() falls back to /tmp, emitting an
#   E_WARNING that Laravel's HandleExceptions converts to an
#   ErrorException (HTTP 500).
# ------------------------------------------------------------------

set -e

APP_DIR=/var/www/html

# Fix ownership so PHP-FPM (www-data) can write to all runtime dirs.
# This is idempotent and fast — only touches metadata, not file content.
chown -R www-data:www-data \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

# Ensure directories are group-readable and writable.
# The 'X' flag applies execute only to directories, not plain files.
chmod -R ug+rwX \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

# Hand off to the CMD (php-fpm by default).
exec "$@"
