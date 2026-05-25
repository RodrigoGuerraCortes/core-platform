# Minimal Production Readiness

**Platform:** Core Platform  
**Status:** Active from Block 9.1

---

## Scope

This document defines the MINIMUM required for a reproducible, validatable deployment. It is NOT a full production operations guide — that comes after the first vertical system (CondoFlow Lite) is built.

---

## What We Have

| Component | Status | Notes |
|---|---|---|
| Frontend build | ✅ | `npm run build` → static assets in `dist/` |
| Frontend Dockerfile | ✅ | Multi-stage: node build → nginx serve |
| Backend | Docker Compose (dev) | PHP-FPM + MySQL + Redis |
| CI pipeline | ✅ | GitHub Actions: lint, test, Docker build |
| Health endpoints | ✅ | `/up`, `/health`, `/health/detailed` |
| Diagnostics command | ✅ | `php artisan platform:diagnostics` |
| Structured logging | ✅ | JSON output for production |

---

## CI Pipeline

**File:** `.github/workflows/ci.yml`

### Jobs

| Job | What it validates |
|---|---|
| `frontend` | ESLint (0 warnings), Vitest (all pass) |
| `backend` | PHPUnit/Pest (all pass), MySQL service |
| `docker` | Frontend Dockerfile builds, image has index.html |

### Triggers
- Push to `main` or `develop`
- Pull requests targeting `main` or `develop`

---

## Frontend Production Build

```bash
cd frontend
npm ci
npm run build
# Output: frontend/dist/
```

### Docker Image
```bash
docker build -f infra/docker/frontend.Dockerfile -t core-platform-frontend .
docker run -p 8080:80 core-platform-frontend
```

### Nginx Configuration
- SPA fallback (`try_files $uri $uri/ /index.html`)
- Static asset caching (1 year, immutable)
- Gzip compression
- Security headers (X-Frame-Options, X-Content-Type-Options)

---

## Backend Production Notes

### Required Environment
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=core_platform
DB_USERNAME=...
DB_PASSWORD=...

LOG_CHANNEL=structured
LOG_LEVEL=info

TELESCOPE_ENABLED=false

SANCTUM_STATEFUL_DOMAINS=your-frontend-domain.com
SESSION_DOMAIN=.your-domain.com
```

### Deployment Checklist
```bash
# 1. Install dependencies (no dev)
composer install --no-dev --optimize-autoloader

# 2. Run migrations
php artisan migrate --force

# 3. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Validate
php artisan platform:diagnostics

# 5. Verify health
curl -sf http://localhost/health
```

---

## What Is Intentionally Deferred

| Item | Why deferred |
|---|---|
| Kubernetes / container orchestration | Not needed for MVP |
| Grafana / Prometheus | Structured logs + Telescope sufficient initially |
| Distributed tracing (Jaeger/Zipkin) | Single service, not needed |
| CI/CD deployment automation | Deploy manually until patterns stabilize |
| Blue-green / canary deployments | Premature for MVP |
| CDN configuration | Static assets served by nginx initially |
| SSL/TLS termination | Handled by hosting platform (Railway, Forge, etc.) |
| Auto-scaling | Single instance sufficient initially |
| Backup automation | Manual until data is critical |
| Alerting (PagerDuty, etc.) | Not needed until real users |

---

## Deployment Validation Script

After deployment, run:
```bash
# On the server
php artisan platform:diagnostics --json | jq .healthy
# Expected: true

# From outside
curl -sf https://your-domain.com/health | jq .status
# Expected: "ok"
```

---

## Next Steps (after CondoFlow Lite)

1. Backend Dockerfile + docker-compose.production.yml
2. Database backup script
3. CI deployment trigger (staging auto-deploy on merge to develop)
4. Error alerting (Sentry or similar)
5. Performance baseline metrics

---

## Related Docs

- [Health Checks](../health-checks.md)
- [Observability Rules](../../governance/backend/observability-rules.md)
- [CI Pipeline](../../../.github/workflows/ci.yml)
