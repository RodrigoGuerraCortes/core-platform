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

## ⚠️ Async Warning

`TenantContext` and `TenantScope` are **request-scoped**. Queue workers and console commands do NOT automatically inherit tenant context.

Never dispatch a queued job that queries tenant-owned models without explicitly serializing the tenant ID into the job and re-initializing the context in `handle()`.

This will be addressed in Block 3 — Queue Propagation.

---

## Block 3 TODOs

- Queue tenant propagation (serialize tenant ID into jobs, re-initialize context in workers)
- Cache key isolation helpers (`tenant-{id}:cache-key` prefix strategy)
- Logging enrichment (add tenant ID to log context automatically)
- RBAC — role-based access within tenant using `membership_role`

- Queue tenant propagation
- Cache key isolation helpers
- Subdomain/path resolution strategies
- Role-based access within tenant (RBAC)
