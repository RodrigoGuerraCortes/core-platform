# ADR-011 — Tenant-Safe Route Model Binding

**Status:** Accepted  
**Date:** 2026-05-20  
**Context:** Block 4 — Domain Adoption & Authorization Foundation

---

## Context

Tenant-owned domain models use `BelongsToTenant`, which registers `TenantScope` as a global Eloquent scope. `TenantScope` depends on `TenantContextContract` being resolved before any SELECT query executes.

Implicit route model binding (`{project}`, `{resource}`, etc.) is processed by the `SubstituteBindings` middleware. If `SubstituteBindings` executes before `TenantContextContract` is populated, route model binding may resolve models without tenant isolation — resulting in cross-tenant entity access.

This was discovered during Block 4 implementation: service-provider-loaded routes do **not** automatically receive the `api` middleware group, so `SubstituteBindings` must be added explicitly. Its position in the middleware chain is security-critical.

---

## Decision

The following middleware ordering is **required** for all tenant-owned API routes:

```php
Route::middleware([
    'auth:sanctum',
    'tenant.resolve',
    SubstituteBindings::class,
    'tenant.member',
])->group(function (): void {
    // tenant-safe routes
});
```

### Invariant

```text
tenant.resolve MUST execute before SubstituteBindings
```

This is a **platform-level security invariant**. It must never be violated in any module that uses route model binding on tenant-owned models.

---

## Why This Order

### Dependency chain

```text
TenantContextContract  →  TenantScope  →  SubstituteBindings (route model binding)
                                          ↓
                                     tenant.member (validates resolved user)
```

Each step depends on the previous one:

1. **`auth:sanctum`** — establishes authenticated user identity
2. **`tenant.resolve`** — reads `X-Tenant-Id`, resolves `Tenant`, populates `TenantContextContract`
3. **`SubstituteBindings`** — resolves `{project}` via `Project::resolveRouteBinding()` with `TenantScope` active; cross-tenant models return `null` → 404
4. **`tenant.member`** — validates the authenticated user belongs to the resolved tenant

### What happens without correct ordering

If `SubstituteBindings` runs before `tenant.resolve`:

- `TenantContextContract` is empty
- `TenantScope::apply()` throws `TenantContextNotResolvedException` (or behaves incorrectly depending on implementation)
- In the worst case: model binding resolves without tenant filtering

**Consequence:** Tenant A user could resolve a Tenant B resource via route parameter. `ProjectPolicy` cannot prevent this — policies run after model binding.

---

## Consequences

### Safe

- Route model binding for `{project}` (and future `{resource}` params) always applies `TenantScope`
- Cross-tenant access via URL manipulation returns **404** — resource existence is not revealed
- Policies are a second authorization layer, not the primary isolation mechanism

### Required in every module

Every module that:
- Uses route model binding on a model with `BelongsToTenant`
- Loads routes via a service provider (not `routes/api.php`)

**must** explicitly include `SubstituteBindings::class` after `tenant.resolve` in its route middleware chain.

### Testing requirement

Every tenant-owned module with route model binding must include tests asserting:

- `Tenant A cannot resolve Tenant B {entity}` (expects 404)
- `Tenant A cannot update Tenant B {entity}` (expects 404)
- `Tenant A cannot delete Tenant B {entity}` (expects 404)

These tests are **mandatory** — they verify that the middleware ordering invariant is being respected at runtime.

---

## Forbidden Ordering

```php
// FORBIDDEN — SubstituteBindings before tenant.resolve
Route::middleware([
    'auth:sanctum',
    SubstituteBindings::class,
    'tenant.resolve',
    'tenant.member',
])->group(...)
```

This ordering is a **critical security defect**. It must be caught in code review.

---

## Platform Routes Exception

Platform-level routes (`/admin`, `/platform/*`, health checks, console commands) do not resolve tenant-owned models and are exempt from this requirement.

---

## Future Considerations

A future platform improvement may introduce:

- A tenant route registrar that enforces the correct middleware order automatically
- Middleware preset helpers or tenant-safe route macros
- Static analysis rules to catch incorrect ordering

Until then, middleware ordering is **explicit and mandatory** at each module's route file.

---

## Related

- ADR-003 — Shared database + tenant_id isolation strategy
- ADR-004 — Module-oriented architecture
- `app/Core/Tenancy/Scopes/TenantScope.php`
- `app/Core/Tenancy/Models/Concerns/BelongsToTenant.php`
- `app/Core/Projects/Routes/api.php` — reference implementation
