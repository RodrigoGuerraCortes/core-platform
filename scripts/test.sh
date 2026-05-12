#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------------
# test.sh — Unified testing entrypoint
# ------------------------------------------------------------------

log() { echo "[test] $*"; }

cd backend

log "Running Pest tests..."
php artisan test --testsuite=Feature --stop-on-failure "$@"

log "All tests passed."
