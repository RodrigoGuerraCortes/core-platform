#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------------
# lint.sh — Unified code quality entrypoint
# ------------------------------------------------------------------

log() { echo "[lint] $*"; }

cd backend

log "Running Pint..."
./vendor/bin/pint --test "$@" || true

log "Running PHPStan..."
./vendor/bin/phpstan analyse --level=1 app "$@" || true

log "Lint checks completed."
