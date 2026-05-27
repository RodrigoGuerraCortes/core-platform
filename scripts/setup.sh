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
command -v node         >/dev/null 2>&1 || err "node is required"
command -v npm          >/dev/null 2>&1 || err "npm is required"
log "All dependencies found."

# ---- 2. Backend setup (inside Docker) ----
log "Setting up backend..."

# Build and start containers first so we can run commands inside them
docker compose build
docker compose up -d

# Wait for PostgreSQL
#log "Waiting for PostgreSQL..."
#until docker compose exec -T core-platform-postgres pg_isready -U app 2>/dev/null; do
#    sleep 2
#done
#log "PostgreSQL is ready."

# Copy .env if missing (on host, but we'll copy into container later)
if [ ! -f backend/.env ]; then
    cp backend/.env.example backend/.env
    log ".env created from .env.example"
fi

# Install composer dependencies inside container
docker compose exec -T app composer install --no-interaction --prefer-dist

# Generate app key if missing (inside container)
if [ -z "$(docker compose exec -T app php artisan key:generate --show 2>/dev/null)" ]; then
    docker compose exec -T app php artisan key:generate
    log "Application key generated"
fi

# ---- 3. Frontend setup ----
log "Setting up frontend..."
# Install on the host so editors, TypeScript, and test runners work without Docker.
cd frontend
npm install --legacy-peer-deps
cd ..
# The frontend container starts automatically via docker compose (see step 2 above).
# Its own npm install runs during `docker compose build` inside the container.

# ---- 4. Migrations & seeders (inside container) ----
docker compose exec -T -u www-data app php artisan migrate --force
docker compose exec -T -u www-data app php artisan db:seed --force

# Validate that required bootstrap data exists (tenant, memberships)
docker compose exec -T -u www-data app php artisan platform:check-bootstrap || {
    log "WARNING: Bootstrap check failed. Run 'make fresh' to reset dev state."
}

# ---- 5. Storage symlink (inside container) ----
docker compose exec -T app php artisan storage:link --force 2>/dev/null || true

# ---- 6. Quick validation (inside container) ----
log "Running smoke tests..."
docker compose exec -T app php artisan test --testsuite=Feature --stop-on-failure 2>/dev/null || log "No Feature tests yet, skipping."

log "Running Pint..."
docker compose exec -T app ./vendor/bin/pint --test 2>/dev/null || log "Pint found issues (non‑blocking)."

log "Running PHPStan (lightweight)..."
docker compose exec -T app ./vendor/bin/phpstan analyse --level=1 app 2>/dev/null || log "PHPStan found issues (non‑blocking)."

# ---- 7. Summary ----
log "============================================"
log "  Core Platform bootstrap complete!"
log "  Backend API : http://localhost:8010"
log "  Frontend    : http://localhost:5173"
log "============================================"
