#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------------
# dev.sh — Start local development environment
#
# Backend runs inside Docker (nginx + php-fpm).
# Frontend Vite dev server runs on host.
# ------------------------------------------------------------------

log() { echo "[dev] $*"; }

log "Ensuring Docker is running..."
docker info >/dev/null 2>&1 || { echo "[dev] Docker is not running. Please start Docker."; exit 1; }

log "Starting containers (if not already up)..."
docker compose up -d

log "Starting frontend dev server..."
cd frontend
npm run dev &
FRONTEND_PID=$!
cd ..

log "Optionally start queue worker? (y/N)"
read -r START_QUEUE
if [[ "$START_QUEUE" =~ ^[Yy]$ ]]; then
    docker compose exec -T app php artisan queue:work &
    QUEUE_PID=$!
    log "Queue worker started (PID $QUEUE_PID)."
fi

log "============================================"
log "  Development servers running."
log "  Backend : http://localhost:8000"
log "  Frontend: http://localhost:5173"
log "  Press Ctrl+C to stop."
log "============================================"

trap "kill $FRONTEND_PID ${QUEUE_PID:-} 2>/dev/null; exit" INT TERM
wait
