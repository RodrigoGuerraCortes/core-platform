# Tenant-Safe Route Model Binding

## Problem

Laravel's implicit route model binding is processed by the `SubstituteBindings` middleware. Tenant-owned models apply `TenantScope` — a global Eloquent scope that filters all SELECT queries to the current tenant. `TenantScope` depends on `TenantContextContract` being populated before any query executes.

**If `SubstituteBindings` runs before `tenant.resolve`, `TenantContextContract` is empty when route model binding fires.** The result: tenant-owned models can be resolved without tenant isolation, allowing Tenant A to access Tenant B resources via URL manipulation.

This is a **critical security defect** — not a minor misconfiguration.

---

## Required Middleware Order

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

Use `App\Core\Tenancy\Routing\TenantRouteMiddleware::STACK` — the platform constant that encodes this order:

```php
use App\Core\Tenancy\Routing\TenantRouteMiddleware;

Route::middleware(TenantRouteMiddleware::STACK)->group(function (): void {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    // ...
});
```

---

## Why This Order

### Dependency chain

```
X-Tenant-Id header
    → tenant.resolve          populates TenantContextContract
        → TenantScope         reads TenantContextContract in apply()
            → SubstituteBindings  resolves {project} — scope is now active
                → tenant.member   validates user membership in resolved tenant
                    → controller
```

Each step depends on the previous one. Breaking the chain at any point breaks isolation.

### What happens with the wrong order

**Unsafe:**
```
auth:sanctum → SubstituteBindings → tenant.resolve → tenant.member
```

When `SubstituteBindings` fires, `TenantContextContract` is empty. `TenantScope::apply()` throws `TenantContextNotResolvedException` or (in a misconfigured fallback) executes without filtering. Route model binding resolves `Project::find($id)` — any project, any tenant.

**Result:** Tenant A user hits `PATCH /projects/999` where project 999 belongs to Tenant B. The binding succeeds. The policy may allow the action if the user is a member of their own tenant. Cross-tenant mutation occurs.

---

## Forbidden Middleware Order

```php
// FORBIDDEN — DO NOT USE
Route::middleware([
    'auth:sanctum',
    SubstituteBindings::class,  // ← fires before context exists
    'tenant.resolve',
    'tenant.member',
])->group(...)
```

```php
// ALSO FORBIDDEN — SubstituteBindings from api group before explicit tenant.resolve
Route::middleware('api')->group(function () {
    Route::middleware(['tenant.resolve', 'tenant.member'])->group(...)
    // SubstituteBindings is in the 'api' group and runs BEFORE tenant.resolve
});
```

---

## How TenantScope Protects Route Model Binding

When the middleware order is correct:

1. `tenant.resolve` reads `X-Tenant-Id`, fetches `Tenant`, calls `TenantContextContract::setTenant()`
2. `SubstituteBindings` calls `Project::resolveRouteBinding($id)` which runs `Project::where('id', $id)->first()`
3. `TenantScope::apply()` intercepts the query builder and adds `WHERE tenant_id = {resolved_tenant_id}`
4. If the project belongs to a different tenant, the query returns `null` → Laravel returns **404**
5. **The existence of a resource in another tenant is never revealed**

Cross-tenant access via URL manipulation is silently denied with 404, not 403. This is intentional — revealing that a resource exists (even if access is denied) is an information leak.

---

## Why Policies Cannot Substitute for Safe Binding

Policies execute **after** route model binding. If binding resolves the wrong model:

```
SubstituteBindings (unsafe) → policy runs on Tenant B's project → may pass
```

A policy checking `$user` membership in the **current tenant** cannot detect that the **resolved model** belongs to a different tenant — the model is already in memory. Even a correctly-written policy cannot fix an incorrectly-bound model.

Tenant-safe route model binding is **infrastructure responsibility**, not application responsibility.

---

## Platform Convention

Every module with tenant-owned route model binding must:

1. Use `TenantRouteMiddleware::STACK` or verify their custom middleware includes the same order
2. Test cross-tenant access on every `{model}` route param (expects 404)
3. Load routes via a service provider that does NOT wrap them in the `api` middleware group

### Why service-provider routes don't get `SubstituteBindings` automatically

Routes loaded via `ServiceProvider::loadRoutesFrom()` are NOT added to the `api` middleware group. The `api` group (defined in `bootstrap/app.php`) has its own `SubstituteBindings`. Mixing both would apply `SubstituteBindings` twice — once from the group (before `tenant.resolve`) and once explicitly. Always add `SubstituteBindings::class` explicitly and position it correctly.

---

## Testing Requirements

Every tenant-owned module must include tests asserting:

```php
// Required — proves binding respects TenantScope
test('Tenant A cannot retrieve Tenant B {entity}', fn () => ...->assertNotFound());
test('Tenant A cannot update Tenant B {entity}',   fn () => ...->assertNotFound());
test('Tenant A cannot delete Tenant B {entity}',   fn () => ...->assertNotFound());
```

These tests are mandatory. They verify the middleware ordering invariant at runtime.

---

## Platform Routes Exception

Routes that do NOT access tenant-owned models are exempt:

- `/admin` (platform admin panel)
- `/auth/*` (identity — global models)
- `/platform/*` (internal operations)
- Health checks

These routes may use the standard `api` group or no group. They do not need `tenant.resolve` or tenant-scoped `SubstituteBindings`.

---

## Future Improvements

Planned improvements that reduce manual configuration risk:

- **Tenant route registrar** — a service-provider base class that auto-applies `TenantRouteMiddleware::STACK`
- **Static analysis rule** — flag routes with tenant-owned `{model}` params lacking correct middleware order
- **Route macro** — `Route::tenantGroup(fn() => ...)` that enforces the stack automatically

Until these exist, middleware ordering is **explicit and mandatory** in every module's route file.

---

## Related

- [ADR-011 — Tenant-Safe Route Model Binding](../../adr/ADR-011-tenant-route-model-binding.md)
- `app/Core/Tenancy/Routing/TenantRouteMiddleware.php` — platform constant
- `app/Core/Tenancy/Scopes/TenantScope.php`
- `app/Core/Tenancy/Models/Concerns/BelongsToTenant.php`
- `app/Core/Projects/Routes/api.php` — reference implementation
