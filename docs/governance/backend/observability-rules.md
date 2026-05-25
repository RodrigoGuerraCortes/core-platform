# Observability Rules

**Governance document — Core Platform Backend**  
**Status:** Active from Block 9.1

---

## Purpose

Establish uniform logging, tracing, and monitoring conventions across all modules to enable:
- Rapid production debugging
- Tenant-scoped investigation
- AI-native log analysis
- Compliance with data protection policies

---

## 1. Logging Conventions

### Canonical Log Context (automatic)

Every log entry AUTOMATICALLY includes (via `PushLogContext` middleware):

| Field | Source | Example |
|---|---|---|
| `request_id` | `X-Request-ID` header (or generated UUID) | `a3f7b9c1-...` |
| `tenant_slug` | TenantContext | `acme` |
| `tenant_id` | TenantContext | `1` |
| `user_id` | Auth | `42` |
| `http_method` | Request | `POST` |
| `http_path` | Request | `api/forms` |
| `ip` | Request | `192.168.1.1` |

**Do NOT manually attach these fields.** They are injected globally.

### Module-Specific Context

When logging within a module action, add module-specific context:

```php
// ✅ Correct — add only domain-specific context
Log::info('Form published', [
    'form_id' => $form->id,
    'version' => $version->version_number,
    'field_count' => count($schema['fields']),
]);

// ❌ Forbidden — do NOT re-attach global context
Log::info('Form published', [
    'request_id' => $request->attributes->get('request_id'), // Already there!
    'tenant_id' => $tenantContext->tenantId(),                // Already there!
    'form_id' => $form->id,
]);
```

### Log Levels

| Level | Use when | Example |
|---|---|---|
| `emergency` | System is unusable | Database unreachable |
| `critical` | Immediate action required | Failed payment processing |
| `error` | Runtime error, operation failed | Unhandled exception |
| `warning` | Exceptional but recoverable | Deprecated API call |
| `info` | Significant business events | User created, form published |
| `debug` | Diagnostic detail (dev only) | Query parameters, cache hits |

### Structured Output

In production, use `LOG_CHANNEL=structured` (JSON formatter).  
In development, use `LOG_CHANNEL=single` (human-readable).

---

## 2. Forbidden Logging Patterns

These patterns are **BANNED** and will be flagged in code review:

### ❌ Logging secrets
```php
Log::info('Login attempt', ['password' => $password]); // BANNED
Log::info('Token issued', ['token' => $token->plainTextToken]); // BANNED
Log::debug('Request', ['headers' => $request->headers->all()]); // BANNED (may contain auth)
```

### ❌ Logging full request payloads
```php
Log::info('Request received', $request->all()); // BANNED — may contain PII
```

### ❌ Ad-hoc noise logging
```php
Log::info('here'); // BANNED
Log::info('reached this point'); // BANNED
Log::info('debugging...'); // BANNED
```

### ❌ Logging without action context
```php
Log::info('something happened'); // BANNED — what? where? why?
```

### ✅ Required format
```php
Log::info('Action description in past tense', [
    'entity_type' => 'form',
    'entity_id' => $id,
    'result' => 'published',
]);
```

---

## 3. Request ID Rules

1. Every HTTP response MUST include `X-Request-ID` header
2. The middleware generates a UUID if no incoming header exists
3. Load balancers / API gateways MAY set `X-Request-ID` — it will be preserved
4. Queued jobs inherit the `request_id` from their dispatch context
5. Logs include `request_id` automatically — do NOT manually attach it
6. When reporting errors to users, include the request ID for support correlation

```php
// In error responses:
return response()->json([
    'message' => 'Something went wrong.',
    'request_id' => request()->attributes->get('request_id'),
], 500);
```

---

## 4. Exception Handling Conventions

### Report everything, handle gracefully

```php
// ✅ Let exceptions propagate — Laravel's handler logs them automatically
public function handle(): void
{
    $form = Form::findOrFail($this->formId); // Throws 404 — correct
}

// ❌ Do NOT silently swallow exceptions
try {
    $result = $this->process();
} catch (\Exception $e) {
    // Silent — lost forever. BANNED.
}
```

### Custom exception context

```php
// ✅ Add context to exceptions for debugging
throw new FormValidationException(
    message: "Schema validation failed for form {$form->id}",
    context: ['form_id' => $form->id, 'errors' => $errors],
);
```

---

## 5. Tenant Context Propagation Rules

1. `PushLogContext` middleware adds `tenant_slug` and `tenant_id` to all logs within a tenant-scoped request
2. Queued jobs using `HasTenantContext` restore tenant context via `RestoreTenantContext` middleware
3. Console commands operating on all tenants MUST log which tenant they're currently processing
4. **Never** log a tenant's data without the tenant context attached — it makes investigation impossible

```php
// ✅ Console command iterating tenants
Tenant::all()->each(function (Tenant $tenant) {
    Log::withContext(['tenant_slug' => $tenant->slug, 'tenant_id' => $tenant->id]);
    Log::info('Processing tenant reports');
    // ... process
});
```

---

## 6. Telescope Usage Rules

1. Telescope is enabled in `local` and `staging` environments only
2. In production, only exceptions, failed requests, failed jobs, and scheduled tasks are recorded
3. Telescope access is gated to `is_platform_admin` users only
4. Do NOT rely on Telescope for production monitoring — use structured logs
5. Telescope data is ephemeral — it may be pruned at any time

---

## 7. Sensitive Data Policy

### Never log:
- Passwords (plain or hashed)
- API tokens / Bearer tokens
- Session IDs
- Credit card numbers
- SSN / national IDs
- Medical records (PII)
- Full request bodies (may contain any of the above)

### Safe to log:
- User IDs (integer)
- Tenant slugs
- Entity IDs
- Action names
- Timestamps
- Status codes
- Error messages (sanitized)
- Request paths (without query strings containing tokens)

---

## 8. Production Logging Checklist

Before deploying to production:
- [ ] `LOG_CHANNEL=structured` (JSON output)
- [ ] `LOG_LEVEL=info` (not debug)
- [ ] `TELESCOPE_ENABLED=false`
- [ ] Verify no `dd()`, `dump()`, or `Log::debug()` in committed code
- [ ] Verify no plain-text secrets in `.env` examples
- [ ] Health endpoint returns 200
- [ ] `php artisan platform:diagnostics` passes
