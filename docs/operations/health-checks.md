# Health Checks

**Platform:** Core Platform  
**Status:** Active from Block 9.1

---

## Endpoints

| Endpoint | Purpose | Auth | When to use |
|---|---|---|---|
| `GET /up` | Laravel built-in liveness probe | None | Load balancer health check |
| `GET /health` | Simple platform OK | None | Uptime monitors (Pingdom, etc.) |
| `GET /health/detailed` | Full infrastructure validation | Platform admin (prod) | Deployment validation, debugging |

---

## /health

Returns immediately. No dependency checks. Safe for frequent polling.

```json
{
  "status": "ok",
  "timestamp": "2026-05-25T16:30:00.000Z"
}
```

---

## /health/detailed

Validates all critical infrastructure dependencies.

### Access Control
- **Local/Staging:** Unrestricted
- **Production:** Requires authenticated platform admin (returns 403 otherwise)

### Response (healthy)
```json
{
  "status": "healthy",
  "timestamp": "2026-05-25T16:30:00.000Z",
  "environment": "local",
  "checks": {
    "database": { "status": "ok", "version": "8.0.36" },
    "cache": { "status": "ok", "driver": "file" },
    "queue": { "status": "ok", "driver": "database" },
    "storage": { "status": "ok", "disk": "local" },
    "platform": { "tenants": 2, "users": 5, "failed_jobs": 0 }
  }
}
```

### Response (degraded — HTTP 503)
```json
{
  "status": "degraded",
  "timestamp": "2026-05-25T16:30:00.000Z",
  "environment": "production",
  "checks": {
    "database": { "status": "ok", "version": "8.0.36" },
    "cache": { "status": "fail", "error": "Connection refused" },
    "queue": { "status": "ok", "driver": "redis" },
    "storage": { "status": "ok", "disk": "s3" }
  }
}
```

---

## Usage in Deployment

### Docker healthcheck
```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
  CMD curl -f http://localhost/health || exit 1
```

### CI validation
```bash
# After starting the app
curl -sf http://localhost/health | jq .status | grep -q "ok"
```

### Load balancer
Point the health check to `/up` (fastest, no dependencies).

---

## platform:diagnostics Command

For deeper inspection, use the artisan command:

```bash
php artisan platform:diagnostics
```

This checks everything `/health/detailed` checks, plus:
- Writable directories (storage/logs, bootstrap/cache)
- Tenant bootstrap state (zero tenants = warning)
- Orphan users (users without tenant membership)
- Failed jobs count

Returns exit code `1` on critical failures — suitable for CI gates.

---

## Related Docs

- [Structured Logging](./observability/structured-logging.md)
- [Telescope](./observability/telescope.md)
- [Minimal Production Readiness](./deployment/minimal-production-readiness.md)
