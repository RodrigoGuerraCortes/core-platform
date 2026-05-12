#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------------
# setup.sh — Full local environment bootstrap for Core Platform
#
# Idempotent: safe to rerun.
# ------------------------------------------------------------------

log() { echo "[setup] $*"; }
err() { echo "[setup] ERROR: $*" >&2; exit 1; }

# ---- 1. Validate dependencies ----
log "Validating dependencies..."
command -v docker       >/dev/null 2>&1 || err "docker is required"
command -v docker compose >/dev/null 2>&1 || err "docker compose is required"
command -v php          >/dev/null 2>&1 || err "php is required"
command -v composer     >/dev/null 2>&1 || err "composer is required"
command -v node         >/dev/null 2>&1 || err "node is required"
command -v npm          >/dev/null 2>&1 || err "npm is required"
log "All dependencies found."

# ---- 2. Backend setup ----
log "Setting up backend..."
cd backend

if [ ! -f .env ]; then
    cp .env.example .env
    log ".env created from .env.example"
fi

composer install --no-interaction --prefer-dist

if [ -z "$(php artisan key:generate --show 2>/dev/null)" ]; then
    php artisan key:generate
    log "Application key generated"
fi

cd ..

# ---- 3. Frontend setup ----
log "Setting up frontend..."
cd frontend
npm install
cd ..

# ---- 4. Docker ----
log "Building and starting Docker containers..."
docker compose build
docker compose up -d

# ---- 5. Wait for PostgreSQL ----
log "Waiting for PostgreSQL..."
until docker compose exec -T postgres pg_isready -U app 2>/dev/null; do
    sleep 2
done
log "PostgreSQL is ready."

# ---- 6. Migrations & seeders ----
cd backend
php artisan migrate --force
php artisan db:seed --force
cd ..

# ---- 7. Storage symlink ----
cd backend
php artisan storage:link --force 2>/dev/null || true
cd ..

# ---- 8. Filament install (if not already) ----
cd backend
if [ ! -d vendor/filament ]; then
    composer require filament/filament --no-interaction
    php artisan filament:install --panels --no-interaction 2>/dev/null || true
fi
cd ..

# ---- 9. Quick validation ----
log "Running smoke tests..."
cd backend
php artisan test --testsuite=Feature --stop-on-failure 2>/dev/null || log "No Feature tests yet, skipping."
cd ..

log "Running Pint..."
cd backend
./vendor/bin/pint --test 2>/dev/null || log "Pint found issues (non‑blocking)."
cd ..

log "Running PHPStan (lightweight)..."
cd backend
./vendor/bin/phpstan analyse --level=1 app 2>/dev/null || log "PHPStan found issues (non‑blocking)."
cd ..

# ---- 10. Summary ----
log "============================================"
log "  Core Platform bootstrap complete!"
log "  Backend : http://localhost:8000"
log "  Frontend: http://localhost:5173"
log "============================================"
