#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------------
# dev.sh — Start local development environment
# ------------------------------------------------------------------

log() { echo "[dev] $*"; }

log "Ensuring Docker is running..."
docker info >/dev/null 2>&1 || { echo "[dev] Docker is not running. Please start Docker."; exit 1; }

log "Starting containers (if not already up)..."
docker compose up -d

log "Starting backend dev server..."
cd backend
php artisan serve --host=0.0.0.0 --port=8000 &
BACKEND_PID=$!
cd ..

log "Starting frontend dev server..."
cd frontend
npm run dev &
FRONTEND_PID=$!
cd ..

log "Optionally start queue worker? (y/N)"
read -r START_QUEUE
if [[ "$START_QUEUE" =~ ^[Yy]$ ]]; then
    cd backend
    php artisan queue:work &
    QUEUE_PID=$!
    cd ..
    log "Queue worker started (PID $QUEUE_PID)."
fi

log "============================================"
log "  Development servers running."
log "  Backend : http://localhost:8000"
log "  Frontend: http://localhost:5173"
log "  Press Ctrl+C to stop."
log "============================================"

trap "kill $BACKEND_PID $FRONTEND_PID ${QUEUE_PID:-} 2>/dev/null; exit" INT TERM
wait
