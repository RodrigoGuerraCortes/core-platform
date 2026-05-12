#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------------
# lint.sh — Unified code quality entrypoint
#
# Runs inside Docker container.
# Fails on any linting error.
# ------------------------------------------------------------------

log() { echo "[lint] $*"; }

log "Running Pint..."
docker compose exec -T app ./vendor/bin/pint --test "$@"

log "Running PHPStan..."
docker compose exec -T app ./vendor/bin/phpstan analyse --level=1 app "$@"

log "Lint checks passed."
