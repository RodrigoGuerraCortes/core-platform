# ADR-012 — Sanctum SPA Middleware Ordering in Module Routes

**Status:** Accepted  
**Date:** 2026-05-24  
**Context:** Block 8.1 — SPA Authentication Foundation

---

## Context

Core Platform uses a modular monolith where each domain module registers its own
routes through a `ServiceProvider`. The base class `CoreModuleServiceProvider`
centralises route registration:

```php
// Before this ADR
private function bootRoutes(): void
{
    Route::prefix('api')->group($path);
}
```

This correctly prefixes all module routes under `/api` but applies **no middleware group**.

### What `Route::prefix('api')` does NOT do

`Route::prefix('api')` applies only a URL path prefix. It does not:

- Apply the Laravel `api` middleware group
- Start the session via `EnsureFrontendRequestsAreStateful`
- Apply `SubstituteBindings`
- Apply `ThrottleRequests`

This is a common misunderstanding and was the direct source of the two failures
documented in this ADR.

---

### Failure 1 — Session not started on API routes

After introducing `$middleware->statefulApi()` in `bootstrap/app.php`, the
`EnsureFrontendRequestsAreStateful` middleware was added to the Laravel `api`
middleware group. Because module routes bypassed that group entirely,
`EnsureFrontendRequestsAreStateful` never ran on module route requests.

**Symptom:** `POST /api/auth/login` returned HTTP 500:

```
RuntimeException: Session store not set on request.
```

`AuthController::login()` calls `$request->session()->regenerate()` after
successful authentication. Without the session middleware in the stack, no
session was started and the request crashed.

---

### Failure 2 — `SubstituteBindings` before `ResolveTenant` (security invariant)

When we attempted to fix Failure 1 by applying the full `api` middleware group
globally to all module routes:

```php
// Attempted fix — caused critical ordering violation
Route::middleware('api')->prefix('api')->group($path);
```

The Laravel `api` group applies `SubstituteBindings` as its final middleware.
Module routes for tenant-owned resources use `TenantRouteRegistrar`, which
enforces `TenantRouteMiddleware::STACK`:

```text
auth:sanctum → tenant.resolve → SubstituteBindings → tenant.member
```

Applying the outer `api` group caused `SubstituteBindings` to execute **twice**:
once at the outer `api` group level (before any tenant context existed), and once
inside the tenant stack. The first execution was the problem.

**Symptom:** `GET /api/forms/{form}` returned HTTP 500:

```
TenantContextNotResolvedException: TenantScope: A tenant-owned model was queried
without an active tenant context.
```

**Why this is a security issue, not just a crash:**

ADR-011 establishes as a platform security invariant:

```text
tenant.resolve MUST execute before SubstituteBindings
```

If `SubstituteBindings` runs first and `TenantScope` does not throw (e.g. if
the scope is misconfigured or bypassed), route model binding may resolve a
tenant-owned entity without applying the tenant filter. This would allow a
Tenant A session to resolve a Tenant B resource by guessing its route ID — a
direct cross-tenant data leakage vector. Policies run **after** model binding
and cannot correct this.

The outer `api` group therefore cannot be globally applied to any module that
routes tenant-owned models, regardless of framework version.

---

### Constraint matrix

| Need | Solution |
|---|---|
| Session started for Sanctum SPA requests | `EnsureFrontendRequestsAreStateful` |
| Tenant context resolved first | `tenant.resolve` inside `TenantRouteRegistrar` |
| Route model binding after tenant context | `SubstituteBindings` inside `TenantRouteRegistrar` |
| Full `api` group on module routes | **Forbidden** — violates tenant ordering invariant |

The two concerns — session startup and tenant route binding — are **independent**.
They must be applied at different points in the middleware chain and must not be
conflated.

---

## Decision

Module routes are registered with `EnsureFrontendRequestsAreStateful` as the
only globally-applied middleware. `SubstituteBindings` and tenant resolution
remain inside `TenantRouteRegistrar`, where ordering is explicitly controlled.

### `CoreModuleServiceProvider::bootRoutes()`

```php
private function bootRoutes(): void
{
    $path = $this->routesPath();

    if ($path !== null) {
        // Apply EnsureFrontendRequestsAreStateful so the session is started
        // for Sanctum SPA requests arriving from stateful domains.
        //
        // The full 'api' middleware group is NOT applied here because its
        // SubstituteBindings would execute before ResolveTenant, violating
        // the tenant-safe route binding invariant (ADR-011).
        //
        // SubstituteBindings is applied inside TenantRouteRegistrar, after
        // tenant.resolve, for all routes that require route model binding.
        Route::middleware([
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ])->prefix('api')->group($path);
    }
}
```

The same pattern applies to `IdentityAuthServiceProvider`, which does not extend
`CoreModuleServiceProvider` but manages the same class of routes.

### Complete middleware chain for tenant-owned API routes

```text
[GLOBAL — applied by CoreModuleServiceProvider]
EnsureFrontendRequestsAreStateful
  └── starts session for stateful Sanctum SPA clients

[TENANT STACK — applied by TenantRouteRegistrar inside each module's route file]
auth:sanctum
  └── authenticates user via session cookie or bearer token
tenant.resolve
  └── reads X-Tenant-Id, resolves Tenant model, populates TenantContextContract
SubstituteBindings
  └── resolves {form}, {project} etc. with TenantScope active
tenant.member
  └── validates authenticated user is a member of the resolved tenant
```

### What modules may NOT do

```php
// FORBIDDEN — applies SubstituteBindings before tenant.resolve
Route::middleware('api')->prefix('api')->group($routes);

// FORBIDDEN — mixing api group with TenantRouteRegistrar
Route::middleware(['api', 'tenant.resolve', ...])->group($routes);
```

### Auth routes (no route model binding)

`POST /api/auth/login` and `POST /api/auth/logout` do not use route model
binding. `EnsureFrontendRequestsAreStateful` is sufficient for them to work
correctly. The absence of `SubstituteBindings` at the outer layer is
intentional and safe for these routes.

---

## Consequences

### Positive

- Sanctum SPA session authentication works correctly end-to-end
- Tenant-safe route model binding is preserved (ADR-011 invariant maintained)
- Middleware ordering is explicit and auditable at the provider level
- Module route files remain isolated — they declare routes, not global middleware
- `CoreModuleServiceProvider` is a single point of change for all module routes
- Future modules inherit correct behaviour automatically

### Negative

- Developers must understand that `Route::prefix('api')` ≠ `Route::middleware('api')`
- The Laravel `api` group may not be applied globally to module routes; each
  middleware from that group must be evaluated individually before adding
- Any future global middleware added to the `api` group must be evaluated for
  tenant ordering compatibility before being adopted here
- Modules that need rate limiting must apply it explicitly inside their route
  files, not rely on the global provider

---

## Guardrails

These rules are permanent platform architecture constraints. Violations must be
caught in code review before merge.

### Rule 1 — Never globally apply `middleware('api')` to module routes

The Laravel `api` middleware group includes `SubstituteBindings`. Applying it
before `TenantRouteRegistrar` violates the tenant ordering invariant (ADR-011).
There is no safe shortcut.

### Rule 2 — Session startup and tenant resolution are independent concerns

`EnsureFrontendRequestsAreStateful` starts the session.
`ResolveTenant` establishes tenant context.
They run at different layers and must not be conflated.

### Rule 3 — `SubstituteBindings` for tenant-owned models is always inside `TenantRouteRegistrar`

No route that resolves a tenant-owned model via route parameter may have
`SubstituteBindings` applied before `tenant.resolve`.

### Rule 4 — New global middleware must be evaluated for tenant ordering compatibility

Before adding any new middleware to the global registration in
`CoreModuleServiceProvider`, the author must confirm it does not perform model
resolution or session operations that conflict with the middleware ordering
defined in ADR-011.

---

## Verification

These ordering invariants are tested at the integration level:

- `TenantRouteBindingConventionTest` — asserts `STACK` middleware order
- `FormApiTest` — asserts cross-tenant 404 via route model binding
- `ProjectApiTest` — asserts cross-tenant 404 via route model binding
- `TokenAuthenticationTest` / `SessionAuthenticationTest` — asserts session-based
  auth works correctly on the API routes

These tests must remain green. A failure in any cross-tenant 404 assertion is a
security regression, not a functional regression.

---

## Related

- ADR-003 — Shared database + tenant_id isolation strategy
- ADR-011 — Tenant-safe route model binding
- `app/Core/Shared/Providers/CoreModuleServiceProvider.php`
- `app/Core/Tenancy/Routing/TenantRouteRegistrar.php`
- `app/Core/IdentityAuth/IdentityAuthServiceProvider.php`
- `docs/features/platform-engineering/route-conventions.md`
- `docs/features/platform-engineering/docker-runtime-ownership.md`
