# Local Environment Debugging Guide

Quick-reference for diagnosing the most common local-environment failures.

---

## Container status

```bash
docker compose ps                     # overview: healthy / unhealthy / exited
docker compose logs -f app            # php-fpm live logs
docker compose logs -f nginx          # nginx live logs
docker compose logs -f postgres       # postgres logs
docker compose logs --tail=50 app     # last 50 lines
```

---

## 502 Bad Gateway — Diagnosis

A 502 means nginx cannot reach PHP-FPM. Work through this checklist in order:

### Step 1 — Is the app container running and healthy?

```bash
docker compose ps app
```

Expected: `healthy`. If `unhealthy` or `exited`:

```bash
docker compose logs app
```

Common causes logged there:
- `.env not found` → `cp backend/.env.example backend/.env`
- `vendor/autoload.php not found` → run Composer (step 2 below)
- Permission denied on `storage/` or `bootstrap/cache/` → `make fix-permissions`

### Step 2 — Does vendor/ exist?

```bash
ls backend/vendor/autoload.php 2>/dev/null && echo OK || echo MISSING
```

Fix:
```bash
docker compose run --rm -w /var/www/html app composer install --no-interaction
```

### Step 3 — Is PHP-FPM accepting connections on port 9000?

```bash
docker compose exec app nc -z 127.0.0.1 9000 && echo OK || echo NOT_LISTENING
```

If not listening, PHP-FPM crashed. Check logs:
```bash
docker compose logs app
```

### Step 4 — Can nginx reach the app container?

```bash
docker compose exec nginx nc -z app 9000 && echo OK || echo UNREACHABLE
```

If unreachable, check both containers are on `core-network`:
```bash
docker network inspect core-platform_core-network
```

### Step 5 — Verify nginx config syntax

```bash
docker compose exec nginx nginx -t
```

### Step 6 — Test FastCGI directly

```bash
# Confirm nginx passes the right SCRIPT_FILENAME
docker compose exec nginx cat /etc/nginx/conf.d/default.conf
```

---

## 500 Internal Server Error — Diagnosis

500 means PHP-FPM responded but Laravel threw an exception.

```bash
# Show last Laravel log entry
docker compose exec app tail -50 storage/logs/laravel.log
```

Common causes:

| Symptom in log | Fix |
|---|---|
| `No application encryption key` | `docker compose exec app php artisan key:generate` |
| `SQLSTATE: connection refused` | Check `DB_HOST=postgres` in `.env`, ensure postgres is healthy |
| `Connection refused 127.0.0.1:6379` | Check `REDIS_HOST=redis` in `.env` |
| `Class not found` / autoload error | Re-run `composer install` and `composer dump-autoload` |
| `View [x] not found` | `docker compose exec app php artisan view:clear` |
| `file_put_contents: failed` | `make fix-permissions` |

---

## Permission Issues — Diagnosis

Laravel needs write access to `storage/` and `bootstrap/cache/` as `www-data`.

```bash
# Check ownership inside the container
docker compose exec app ls -la storage/
docker compose exec app ls -la bootstrap/cache/

# Repair (idempotent, safe to run anytime)
make fix-permissions

# Verify php-fpm can write
docker compose exec -u www-data app touch storage/logs/test.log && echo OK
```

If you run artisan commands without `-u www-data`, they create files as `root`,
which www-data cannot overwrite. Always use:
```bash
make artisan CMD="migrate"
# or
docker compose exec -u www-data app php artisan <command>
```

---

## Volume / Mount Issues — Diagnosis

```bash
# Confirm backend source is mounted at the expected path
docker compose exec app ls /var/www/html/public/index.php && echo OK || echo MISSING

# List all mounts for the app container
docker inspect core-platform-app | grep -A5 '"Mounts"'

# If files exist on host but not in container, the volume path may be wrong
ls backend/public/index.php
```

Common fix: ensure `docker-compose.yml` maps `./backend:/var/www/html` (not `./`).

---

## PHP-FPM — Diagnosis

```bash
# Check php-fpm process is running
docker compose exec app pgrep -a php-fpm

# Check php-fpm configuration is valid
docker compose exec app php-fpm -t

# Check installed extensions
docker compose exec app php -m

# Check key extensions are present
docker compose exec app php -m | grep -E 'pdo_pgsql|redis|bcmath|opcache|intl|gd'

# PHP info (dump to file to avoid garbled output)
docker compose exec app php -r "phpinfo();" > /tmp/phpinfo.txt 2>&1
```

---

## PostgreSQL — Diagnosis

```bash
# Check postgres is accepting connections
docker compose exec postgres pg_isready -U app -d core_platform

# Connect to psql
docker compose exec postgres psql -U app -d core_platform

# List tables
docker compose exec postgres psql -U app -d core_platform -c '\dt'

# Test connection from the app container
docker compose exec app php artisan db:show

# Run migrations manually
make migrate

# Wipe and re-seed (destructive)
make fresh
```

Check `.env` values:
```
DB_HOST=postgres          ← must be the service name, not localhost or container name
DB_PORT=5432
DB_DATABASE=core_platform
DB_USERNAME=app
DB_PASSWORD=secret
```

---

## Redis — Diagnosis

```bash
# Ping Redis
docker compose exec redis redis-cli ping   # expects: PONG

# Test connection from the app container
docker compose exec app php artisan tinker --execute="Redis::ping();"

# Check Redis keys
docker compose exec redis redis-cli keys '*'
```

Check `.env` values:
```
REDIS_HOST=redis          ← must be the service name, not 127.0.0.1
REDIS_PORT=6379
REDIS_CLIENT=phpredis     ← requires the phpredis PHP extension (installed in Dockerfile)
```

---

## Frontend (Vite / npm) — Diagnosis

```bash
# Check frontend container is running
docker compose ps frontend

# Check Vite dev server logs
docker compose logs -f frontend

# If the container exits immediately, check the build
docker compose run --rm frontend npm run build 2>&1 | tail -30

# Reinstall node_modules on host (editor tooling / TypeScript)
cd frontend && rm -rf node_modules && npm install --legacy-peer-deps

# Verify Vite proxy hits nginx
curl -I http://localhost:5173/api/health 2>/dev/null | head -5
```

---

## Quick Full Reset

```bash
# Stop everything, remove containers and volumes, start fresh
docker compose down -v
./scripts/setup.sh
```

> **Warning:** `docker compose down -v` removes the postgres_data volume — all local database data is lost.

---

## Smoke-Test Cheatsheet

```bash
# 1. All containers healthy
docker compose ps

# 2. Backend returns 200/302
curl -sS -o /dev/null -w "%{http_code}\n" http://localhost:8010

# 3. Laravel responds (not just nginx static)
curl -sS http://localhost:8010 | head -5

# 4. Database connection works
docker compose exec -u www-data app php artisan db:show

# 5. Redis works
docker compose exec app php artisan tinker --execute="echo Cache::store('redis')->get('test') ?? 'redis ok';"

# 6. PHP has all required extensions
docker compose exec app php -m | grep -E 'pdo_pgsql|redis|bcmath|opcache|intl|gd|pcntl|zip'
```
