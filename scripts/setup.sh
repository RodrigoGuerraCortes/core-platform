#!/usr/bin/env bash
# ------------------------------------------------------------------
# setup.sh — Core Platform local environment bootstrap
#
# Usage:
#   ./setup.sh           — full setup (idempotent, safe to rerun)
#   ./setup.sh --no-frontend  — skip frontend npm install
#
# Requirements:
#   docker (daemon running)
#   docker compose v2
#   node / npm  (only needed without --no-frontend)
# ------------------------------------------------------------------
set -euo pipefail

# ── Terminal colors ───────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# ── Logging helpers ───────────────────────────────────────────────
log()     { echo -e "${GREEN}[setup]${RESET} $*"; }
info()    { echo -e "${BLUE}[setup]${RESET} $*"; }
warn()    { echo -e "${YELLOW}[setup] WARN:${RESET} $*"; }
step()    { echo -e "\n${BOLD}${CYAN}▶ $*${RESET}"; }
success() { echo -e "  ${GREEN}✓${RESET} $*"; }
err()     { echo -e "\n${RED}[setup] ERROR:${RESET} $*" >&2
            echo -e "${YELLOW}  → Run 'docker compose logs' to diagnose.${RESET}" >&2
            exit 1; }

# ── Flags ─────────────────────────────────────────────────────────
SKIP_FRONTEND=false
for arg in "$@"; do
    case "$arg" in
        --no-frontend) SKIP_FRONTEND=true ;;
    esac
done

cd "${ROOT_DIR}"

# ═══════════════════════════════════════════════════════════════════
# 1. Prerequisites
# ═══════════════════════════════════════════════════════════════════
step "Validating prerequisites"

command -v docker >/dev/null 2>&1 || err "docker is not installed. Install from https://docs.docker.com/get-docker/"
docker info >/dev/null 2>&1       || err "Docker daemon is not running. Start Docker Desktop or 'sudo systemctl start docker'."
docker compose version >/dev/null 2>&1 || err "docker compose v2 plugin is required. See https://docs.docker.com/compose/install/"

if [ "${SKIP_FRONTEND}" = "false" ]; then
    command -v node >/dev/null 2>&1 || err "node is not installed. Install via https://nodejs.org or nvm."
    command -v npm  >/dev/null 2>&1 || err "npm is not installed."
fi

# Warn on port conflicts (non-fatal — developer may have overrides)
for port_pair in "8010:Backend(nginx)" "5433:PostgreSQL" "6380:Redis" "5173:Frontend"; do
    port="${port_pair%%:*}"
    label="${port_pair##*:}"
    if ss -tlnp 2>/dev/null | grep -q ":${port} " \
        || netstat -tlnp 2>/dev/null | grep -q ":${port} " 2>/dev/null; then
        warn "Port ${port} (${label}) is already in use — check for conflicts."
    fi
done

success "Prerequisites OK"

# ═══════════════════════════════════════════════════════════════════
# 2. Environment file
# ═══════════════════════════════════════════════════════════════════
step "Configuring backend .env"

if [ ! -f backend/.env ]; then
    [ -f backend/.env.example ] || err "backend/.env.example is missing. Repository may be corrupted."
    cp backend/.env.example backend/.env
    success ".env created from .env.example"
else
    info ".env already exists — skipping copy."
fi

# ═══════════════════════════════════════════════════════════════════
# 3. Build Docker images
# ═══════════════════════════════════════════════════════════════════
step "Building Docker images"
docker compose build
success "Images built"

# ═══════════════════════════════════════════════════════════════════
# 4. Start infrastructure and wait for health
# ═══════════════════════════════════════════════════════════════════
step "Starting infrastructure services (postgres, redis)"
docker compose up -d postgres redis

# Polls docker compose until the container is 'healthy'.
# Args: service  max_wait_seconds
wait_healthy() {
    local service="$1"
    local max_seconds="${2:-120}"
    local elapsed=0
    local interval=3

    info "Waiting for ${service} to be healthy (max ${max_seconds}s)..."
    while true; do
        status=$(docker compose ps --format json "${service}" 2>/dev/null \
            | grep -o '"Health":"[^"]*"' | head -1 | cut -d'"' -f4 || true)

        # Fallback for older compose versions that don't emit Health in JSON
        if [ -z "${status}" ]; then
            status=$(docker compose ps "${service}" 2>/dev/null \
                | grep -oE '\(healthy\)|\(unhealthy\)|Exit' | head -1 || true)
            case "${status}" in
                "(healthy)") status="healthy" ;;
                "(unhealthy)"|"Exit") status="unhealthy" ;;
            esac
        fi

        case "${status}" in
            healthy)
                success "${service} is healthy"
                return 0
                ;;
            unhealthy)
                err "${service} is unhealthy. Diagnose with: docker compose logs ${service}"
                ;;
        esac

        [ "${elapsed}" -ge "${max_seconds}" ] && \
            err "${service} did not become healthy after ${max_seconds}s. Diagnose: docker compose logs ${service}"

        sleep "${interval}"
        elapsed=$((elapsed + interval))
    done
}

wait_healthy postgres 120
wait_healthy redis    60

# ═══════════════════════════════════════════════════════════════════
# 5. Install Composer dependencies
# ═══════════════════════════════════════════════════════════════════
step "Installing Composer dependencies"

# Run composer install inside a throwaway container so vendor/ is
# populated before the long-running app container starts. This avoids
# the entrypoint failing with 'vendor/autoload.php not found'.
docker compose run --rm \
    -w /var/www/html \
    app \
    composer install --no-interaction --prefer-dist --optimize-autoloader

success "Composer dependencies installed"

# ═══════════════════════════════════════════════════════════════════
# 6. Start app and wait for health
# ═══════════════════════════════════════════════════════════════════
step "Starting app container (php-fpm)"
docker compose up -d app
wait_healthy app 120

# ═══════════════════════════════════════════════════════════════════
# 7. Generate APP_KEY if missing
# ═══════════════════════════════════════════════════════════════════
step "Checking APP_KEY"

# Extract current key value from the running container's effective env
current_key=$(docker compose exec -T app php artisan key:show 2>/dev/null \
    | grep -oE 'base64:[A-Za-z0-9+/=]+' | head -1 || true)

if [ -z "${current_key}" ]; then
    docker compose exec -T app php artisan key:generate --force
    success "APP_KEY generated"
else
    info "APP_KEY already set."
fi

# ═══════════════════════════════════════════════════════════════════
# 8. Clear stale caches
# ═══════════════════════════════════════════════════════════════════
step "Clearing application caches"
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan view:clear
success "Caches cleared"

# ═══════════════════════════════════════════════════════════════════
# 9. Start nginx and wait for health
# ═══════════════════════════════════════════════════════════════════
step "Starting nginx"
docker compose up -d nginx
wait_healthy nginx 60

# ═══════════════════════════════════════════════════════════════════
# 10. Run migrations
# ═══════════════════════════════════════════════════════════════════
step "Running database migrations"
docker compose exec -T -u www-data app php artisan migrate --force
success "Migrations complete"

# ═══════════════════════════════════════════════════════════════════
# 11. Create storage symlink
# ═══════════════════════════════════════════════════════════════════
step "Creating storage symlink"
docker compose exec -T -u www-data app php artisan storage:link --force 2>/dev/null || true
success "Storage symlink ready"

# ═══════════════════════════════════════════════════════════════════
# 12. Optional: bootstrap check
# ═══════════════════════════════════════════════════════════════════
step "Running bootstrap validation"
docker compose exec -T -u www-data app php artisan platform:check-bootstrap 2>/dev/null \
    || warn "Bootstrap check failed — run 'make fresh' to reset dev state."

# ═══════════════════════════════════════════════════════════════════
# 13. Frontend
# ═══════════════════════════════════════════════════════════════════
if [ "${SKIP_FRONTEND}" = "false" ]; then
    step "Installing frontend dependencies (host)"
    # Install on the host so editors, TypeScript tooling, and test runners
    # work without Docker. The container already has its own node_modules.
    (cd frontend && npm install --legacy-peer-deps)
    success "Frontend node_modules ready"

    step "Starting frontend container"
    docker compose up -d frontend
    success "Frontend container started"
fi

# ═══════════════════════════════════════════════════════════════════
# 14. Smoke tests
# ═══════════════════════════════════════════════════════════════════
step "Smoke testing backend (HTTP)"

attempt=0
max_attempts=15
until curl -sf --max-time 5 http://localhost:8010 >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    [ "${attempt}" -ge "${max_attempts}" ] && \
        err "Backend did not respond after $((max_attempts * 2))s. Diagnose:\n  docker compose logs nginx\n  docker compose logs app"
    sleep 2
done

http_code=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 5 http://localhost:8010)
case "${http_code}" in
    200|301|302) success "Backend responded: HTTP ${http_code}" ;;
    500) err "Backend returned HTTP 500. Check:\n  docker compose logs app\n  docker compose exec app php artisan config:show" ;;
    *)   warn "Backend returned HTTP ${http_code} — investigate if unexpected." ;;
esac

# ── Container status summary ──────────────────────────────────────
step "Container status"
docker compose ps

# ═══════════════════════════════════════════════════════════════════
# 15. Summary
# ═══════════════════════════════════════════════════════════════════
echo ""
echo -e "${BOLD}${GREEN}════════════════════════════════════════════${RESET}"
echo -e "${BOLD}${GREEN}  Core Platform — Ready!${RESET}"
echo -e "${BOLD}${GREEN}════════════════════════════════════════════${RESET}"
echo ""
echo -e "  Backend API   ${CYAN}http://localhost:8010${RESET}"
[ "${SKIP_FRONTEND}" = "false" ] && \
echo -e "  Frontend      ${CYAN}http://localhost:5173${RESET}"
echo -e "  PostgreSQL    ${CYAN}localhost:5433${RESET}  (user: app / db: core_platform)"
echo -e "  Redis         ${CYAN}localhost:6380${RESET}"
echo ""
echo -e "  ${BOLD}Useful commands:${RESET}"
echo -e "    ${YELLOW}docker compose ps${RESET}                — container status"
echo -e "    ${YELLOW}docker compose logs -f app${RESET}       — php-fpm logs"
echo -e "    ${YELLOW}docker compose logs -f nginx${RESET}     — nginx logs"
echo -e "    ${YELLOW}make artisan CMD=\"migrate\"${RESET}      — run artisan commands"
echo -e "    ${YELLOW}make fresh${RESET}                       — wipe DB and re-seed"
echo -e "    ${YELLOW}docker compose down -v${RESET}           — destroy everything"
echo ""
