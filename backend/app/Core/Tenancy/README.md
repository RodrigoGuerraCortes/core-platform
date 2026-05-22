# Tenancy Module

## Purpose

The Tenancy module provides organizational isolation infrastructure for the Core Platform.

Tenancy is **infrastructure**, not business logic.

## Core Principles

- Users are **global identities** — never add `tenant_id` to the `users` table
- Tenant context is **request-scoped** — no persistent active tenant session
- `TenantContextContract` is the **only** runtime tenant provider in domain logic
- Authentication = WHO, Tenancy = WHERE, Authorization = WHAT

## Structure

```
Core/Tenancy/
├── Context/              # TenantContext — request-scoped runtime state
├── Contracts/            # TenantContextContract — domain injection point
├── Middleware/           # ResolveTenant, ValidateTenantMembership
├── Models/
│   ├── Tenant.php
│   └── Concerns/
│       └── BelongsToTenant.php  # Trait for tenant-owned domain models
├── Scopes/               # TenantScope — global Eloquent isolation scope
├── Exceptions/           # TenantNotFoundException, TenantContextNotResolvedException
├── Jobs/
│   ├── Concerns/
│   │   └── HasTenantContext.php    # Trait for tenant-aware queued jobs
│   └── Middleware/
│       └── RestoreTenantContext.php # Job middleware — restores context in workers
├── Support/
│   ├── TenantCache.php   # Tenant-isolated cache key helper
│   └── TenantLogger.php  # Tenant log context provider
├── Providers/            # TenancyServiceProvider
└── README.md
```

## Resolution Strategy (Phase 1)

Tenant is resolved from the `X-Tenant-Id` request header.

```http
GET /projects
Authorization: Bearer xxx
X-Tenant-Id: 1
```

## Middleware Aliases

- `tenant.resolve` → `ResolveTenant` — resolves and validates tenant from header
- `tenant.member` → `ValidateTenantMembership` — ensures user belongs to tenant

## Usage — Tenant Context

Access tenant context inside controllers/actions (always via the contract):

```php
$context = app(TenantContextContract::class);
$tenant  = $context->tenant();
$id      = $context->tenantId();
```

---

## Block 2 — Tenant Isolation Primitives

### BelongsToTenant Trait

Add to any domain model that is tenant-owned:

```php
class Project extends Model
{
    use BelongsToTenant;
    // tenant_id column required in the table
}
```

Effects:
1. Registers `TenantScope` — all SELECT queries automatically include `WHERE tenant_id = <current>`
2. Auto-fills `tenant_id` on model creation when `TenantContext` is resolved
3. Exposes `tenant()` BelongsTo relationship

Requirements:
- Table must have a `tenant_id` column (`BIGINT`, FK to `tenants`)
- Route must apply `tenant.resolve` middleware before querying the model

### TenantScope

`TenantScope` reads from `TenantContextContract` and applies `WHERE tenant_id = ?`.

**Failure behavior:** If no tenant context is resolved and a tenant-owned model is queried, the scope throws `TenantContextNotResolvedException`. This is intentional — silent all-row returns would be a cross-tenant data leak.

### Bypass Strategy

For platform/internal operations that legitimately need cross-tenant access:

```php
// EXPLICIT BYPASS — must be visible at the call site
// Reserved for: platform tooling, support operations, data migrations
Project::withoutGlobalScope(TenantScope::class)->get();
```

Rules:
- **Never** use `withoutGlobalScopes()` (plural) — it silently removes all scopes
- **Never** bypass automatically for platform admins — `is_platform_admin` does NOT grant scope bypass
- Bypass must always be explicit and visible in code review

### Auto-fill vs Explicit Assignment

| Scenario | Behavior |
|---|---|
| `tenant_id` not set + context resolved | Auto-filled from `TenantContextContract` |
| `tenant_id` already set | Never overwritten |
| `tenant_id` not set + no context | Insert proceeds; DB FK constraint may reject it |

---

## Block 3 — Async & Operational Infrastructure

### Queue Tenant Propagation

Use `HasTenantContext` on any queued job that needs tenant isolation:

```php
class ProcessTenantReport implements ShouldQueue
{
    use HasTenantContext;

    public function __construct()
    {
        $this->captureTenantContext(); // Serializes tenant_id at dispatch time
    }

    public function handle(): void
    {
        // TenantContextContract is restored by RestoreTenantContext middleware
        $tenantId = app(TenantContextContract::class)->tenantId();
    }
}
```

**Lifecycle guarantee:**

| Stage | Context state |
|---|---|
| `dispatch()` called in HTTP request | `captureTenantContext()` serializes `tenant_id` |
| Job picked up by worker | `RestoreTenantContext` fetches Tenant by ID, calls `setTenant()` |
| `handle()` runs | `TenantContextContract` is resolved |
| After `handle()` (success or failure) | `context->clear()` called in `finally` — worker is clean |

**Worker safety:** The `finally` block in `RestoreTenantContext` always clears context after each job — even on exceptions. This prevents stale context from leaking into the next job processed by the same worker.

**If a job needs additional middleware:**

```php
public function middleware(): array
{
    return [...$this->tenantContextMiddleware(), new RateLimited('reports')];
}
```

**Failure when tenant deleted:** If the tenant is soft-deleted after the job was dispatched, `RestoreTenantContext` throws a `RuntimeException` — the job fails explicitly rather than running with a stale or null context.

---

### Tenant-Aware Cache

Use `TenantCache` for any cache value that should be isolated per tenant:

```php
$cache = app(TenantCache::class);

$cache->put('settings', $data, now()->addHour()); // → tenant:{id}:settings
$cache->get('settings');
$cache->forget('settings');
$cache->remember('report', 3600, fn () => buildReport());
```

Key format: `tenant:{tenantId}:{key}`

Throws `TenantContextNotResolvedException` when no context is resolved.

For **global (platform-wide) cache**, use the `Cache` facade directly — no prefix:

```php
Cache::put('platform:config', $data); // Global, not tenant-scoped
```

---

### Logging Enrichment

`TenantLogger` provides tenant metadata for structured log enrichment:

```php
// Automatic in HTTP context: ResolveTenant middleware calls this after resolution.
Log::withContext(app(TenantLogger::class)->context());
// → adds tenant_id and tenant_slug to all subsequent log entries in the request

// In queue workers: call manually at the start of handle()
public function handle(): void
{
    Log::withContext(app(TenantLogger::class)->context());
    // ... job logic
}
```

Returns `[]` when no context is resolved (safe for platform-level code — never throws).

---

## ⚠️ Async Warning

`TenantContext` is **request-scoped**. Queue workers and console commands start with an empty context. The `HasTenantContext` + `RestoreTenantContext` pair is the ONLY sanctioned mechanism to propagate tenant context into async execution.

Never rely on session, auth state, or global variables for tenant propagation in workers.

---

## Block 4 TODOs

- RBAC — role-based access within tenant using `membership_role` from `tenant_user`
- Tenant-scoped Sanctum token policies (optional, not auth-coupled)
- Scheduled command tenant propagation (console kernel tenant isolation)
- Multi-tenant audit logging (tenant-scoped event trail)

- Queue tenant propagation
- Cache key isolation helpers
- Subdomain/path resolution strategies
- Role-based access within tenant (RBAC)
