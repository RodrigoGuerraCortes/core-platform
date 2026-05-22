# Runtime Infrastructure — Local Development Environment

**Block:** 7.3.2  
**Status:** Active  
**Scope:** Local development runtime only — not production.

---

## Overview

The Core Platform runs as a multi-container Docker Compose stack for local development. A single `docker compose up` starts all services with hot reload enabled.

```
Browser
  │
  ├─ http://localhost:5173  →  frontend  (Vite dev server, HMR)
  │                                │
  │                                └─ /api proxy  →  nginx:80
  │
  └─ http://localhost:8010  →  nginx  (direct backend access, e.g. curl)
                                  │
                                  └─ fastcgi  →  app:9000 (php-fpm)
                                                    │
                                                    ├─ postgres:5432
                                                    └─ redis:6379
```

---

## Services

| Service    | Image / Build             | Host Port | Internal Port | Purpose                      |
|------------|---------------------------|-----------|---------------|------------------------------|
| `app`      | `infra/docker/backend/`   | —         | 9000          | PHP-FPM — Laravel application |
| `nginx`    | `nginx:alpine`            | 8010      | 80            | HTTP → fastcgi proxy for PHP |
| `frontend` | `frontend/Dockerfile`     | 5173      | 5173          | Vite dev server + HMR        |
| `postgres` | `postgres:16-alpine`      | 5433      | 5432          | PostgreSQL 16                |
| `redis`    | `redis:7-alpine`          | 6380      | 6379          | Redis 7                      |

---

## Environment Variables

### Frontend container (`docker-compose.yml`)

| Variable       | Value (in container)    | Purpose                                              |
|----------------|-------------------------|------------------------------------------------------|
| `BACKEND_URL`  | `http://nginx:80`       | Vite proxy target for `/api` calls inside Docker     |

When running **outside Docker** (host-side `npm run dev`), `BACKEND_URL` is unset and the Vite proxy falls back to `http://localhost:8010`.

### Backend (`.env`)

Laravel reads from `backend/.env`. The default `.env.example` uses SQLite for quick local use. For the full Docker stack, override with:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=core_platform
DB_USERNAME=app
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
```

### CORS override (optional)

| Variable               | Example                          | Purpose                      |
|------------------------|----------------------------------|------------------------------|
| `CORS_ALLOWED_ORIGIN`  | `https://staging.example.com`    | Additional origin for staging |

---

## Networking

All services share the `core-network` Docker bridge network. Service names resolve as hostnames inside the network:

- `app` — PHP-FPM on port 9000
- `nginx` — HTTP server on port 80
- `postgres` — PostgreSQL on port 5432
- `redis` — Redis on port 6379
- `frontend` — Vite dev server on port 5173

The **frontend never talks to `app:9000` directly** — all API traffic goes through `nginx:80` which handles PHP FastCGI routing.

### API request flow (development)

```
Browser → localhost:5173/api/... 
  → Vite proxy (BACKEND_URL=http://nginx:80)
    → nginx:80/api/...
      → fastcgi app:9000
        → Laravel controller
```

This means the browser always sees same-origin requests (`localhost:5173`). CORS headers are not required for this proxy-based flow.

CORS is still configured (`backend/config/cors.php`) for:
- Direct backend calls (e.g., integration tests, `curl`, Postman)
- Future staging environments where the frontend is on a different origin

---

## Hot Module Replacement (HMR)

Vite HMR works over a WebSocket. The configuration in `frontend/vite.config.ts`:

```ts
server: {
  host: '0.0.0.0',   // container binds to all interfaces
  hmr: {
    host: 'localhost', // browser WebSocket connects to host machine
  },
}
```

- `host: '0.0.0.0'` — required for Docker; Vite must listen on all interfaces, not just `127.0.0.1`
- `hmr.host: 'localhost'` — the browser opens the WebSocket; it must connect to the host machine's address, not the container's internal name

---

## CORS Configuration

`backend/config/cors.php` explicitly allows:

- **Origins:** `http://localhost:5173` + optional `CORS_ALLOWED_ORIGIN` env override
- **Headers:** `Accept`, `Authorization`, `Content-Type`, `X-Requested-With`, `X-Tenant-Id`
- **Methods:** `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`
- **Credentials:** `false` — update to `true` when Sanctum cookie auth is introduced

> **Note:** `X-Tenant-Id` is explicitly listed so browser preflight requests (`OPTIONS`) succeed for tenant-scoped API calls.

---

## Common Commands

```bash
# Bootstrap everything (first run)
./scripts/setup.sh

# Start all services
docker compose up

# Start in background
docker compose up -d

# View frontend logs (Vite output, HMR events)
docker compose logs -f frontend

# View backend logs
docker compose logs -f app nginx

# Rebuild frontend image (after package.json changes)
docker compose build frontend

# Run backend tests inside container
docker compose exec app php artisan test --filter=DynamicForms

# Run frontend tests on host
cd frontend && npm test

# Stop all services
docker compose down
```

---

## Deferred / Production Concerns

The following are explicitly NOT handled here:

| Concern                      | Reason deferred                              |
|------------------------------|----------------------------------------------|
| Production Nginx optimization | Local DX only; prod uses different topology |
| TLS / HTTPS                  | Not needed locally                           |
| Container health checks      | Covered by pg_isready in setup.sh           |
| Multi-tenant domain routing  | Handled at app level, not infra level        |
| Sanctum cookie auth          | `supports_credentials: false` for now       |
| Frontend SSR                 | Explicitly out of scope for V1              |
| Production image (multi-stage) | Frontend Dockerfile is dev-only           |
| CDN / asset serving          | Vite serves assets locally                  |

---

## File Map

| File                                  | Purpose                              |
|---------------------------------------|--------------------------------------|
| `docker-compose.yml`                  | All service definitions              |
| `frontend/Dockerfile`                 | Vite dev server container            |
| `frontend/.dockerignore`              | Exclude node_modules from build      |
| `frontend/vite.config.ts`            | Vite config — host, HMR, proxy       |
| `infra/docker/backend/Dockerfile`     | PHP-FPM container                    |
| `infra/docker/nginx/default.conf`     | Nginx → PHP-FPM routing              |
| `backend/config/cors.php`            | Laravel CORS policy                  |
| `scripts/setup.sh`                   | One-command bootstrap                |
