# Structured Logging

**Platform:** Core Platform  
**Status:** Active from Block 9.1

---

## Overview

All log entries in the Core Platform automatically include structured context:

```json
{
  "message": "Form published",
  "level": "info",
  "channel": "structured",
  "datetime": "2026-05-25T16:30:00+00:00",
  "context": {
    "request_id": "a3f7b9c1-4e2d-4f8a-9b1c-2d3e4f5a6b7c",
    "tenant_slug": "acme",
    "tenant_id": 1,
    "user_id": 42,
    "http_method": "POST",
    "http_path": "api/forms/5/versions",
    "ip": "192.168.1.100",
    "form_id": 5,
    "version": 3
  }
}
```

---

## Architecture

```
┌─────────────────┐
│  HTTP Request   │
└───────┬─────────┘
        ▼
┌─────────────────┐    Generates/preserves UUID
│ InjectRequestId │────────────────────────────────► X-Request-ID header
└───────┬─────────┘
        ▼
┌─────────────────┐    Pushes: request_id, tenant_slug, user_id, http_*
│ PushLogContext  │────────────────────────────────► Log::shareContext()
└───────┬─────────┘
        ▼
┌─────────────────┐
│ Module Logic    │──── Log::info('action', ['domain_context'])
└───────┬─────────┘
        ▼
┌─────────────────┐    Merged: global context + domain context
│  Log Output     │────────────────────────────────► storage/logs/structured.log
└─────────────────┘
```

---

## Canonical Log Schema

Every log entry has this structure:

| Field | Type | Source | Required |
|---|---|---|---|
| `message` | string | Caller | ✅ |
| `level` | string | Log method | ✅ |
| `datetime` | ISO 8601 | Monolog | ✅ |
| `request_id` | UUID | InjectRequestId middleware | ✅ |
| `tenant_slug` | string | PushLogContext middleware | When resolved |
| `tenant_id` | int | PushLogContext middleware | When resolved |
| `user_id` | int | PushLogContext middleware | When authenticated |
| `http_method` | string | PushLogContext middleware | ✅ |
| `http_path` | string | PushLogContext middleware | ✅ |
| `ip` | string | PushLogContext middleware | ✅ |
| Domain fields | varies | Caller context array | Varies |

---

## How to Log

### In controllers / actions / services:

```php
use Illuminate\Support\Facades\Log;

// Just log the event + domain context. Global context is already there.
Log::info('User invited to workspace', [
    'invited_email' => $email,
    'role' => $role,
]);
```

### In queued jobs:

Jobs that use `HasTenantContext` trait have tenant context restored by `RestoreTenantContext` middleware. The `request_id` from the original request is carried via a custom job property:

```php
class ProcessReport implements ShouldQueue
{
    use HasTenantContext;

    public ?string $requestId;

    public function __construct()
    {
        $this->captureTenantContext();
        $this->requestId = app()->bound('request.id') ? app('request.id') : null;
    }

    public function handle(): void
    {
        // Restore log context for the job
        Log::shareContext(array_filter([
            'request_id' => $this->requestId,
            'tenant_id' => app(TenantContextContract::class)->tenantId(),
            'job' => static::class,
        ]));

        Log::info('Report processing started', ['report_id' => $this->reportId]);
    }
}
```

---

## Configuration

### Development (default)
```env
LOG_CHANNEL=single
LOG_LEVEL=debug
```

### Production
```env
LOG_CHANNEL=structured
LOG_LEVEL=info
```

### Combined (both human-readable and JSON)
```env
LOG_CHANNEL=stack
LOG_STACK=single,structured
```

---

## Searching Logs

With JSON structured logging, use standard tools:

```bash
# Find all logs for a specific request
cat storage/logs/structured.log | jq 'select(.context.request_id == "abc-123")'

# Find all errors for a tenant
cat storage/logs/structured.log | jq 'select(.level_name == "ERROR" and .context.tenant_slug == "acme")'

# Find slow queries (if logged)
cat storage/logs/structured.log | jq 'select(.context.query_time_ms > 100)'
```

---

## Related Docs

- [Observability Rules](../../governance/backend/observability-rules.md)
- [Telescope](./telescope.md)
- [Health Checks](../health-checks.md)
