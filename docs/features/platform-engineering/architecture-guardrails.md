# Architecture Guardrails

**Block:** 6 — Platform Engineering & Modular Expansion  
**Status:** Frozen  
**Date:** 2026-05-20

---

## Overview

This document defines the architecture invariants of Core Platform. These are non-negotiable rules. Violating them creates security defects, data isolation failures, or architectural debt that cannot be repaired without significant rework.

Each guardrail records: the rule, the reason, the risk of violation, and how it was discovered or enforced.

---

## I. Tenant Isolation Invariants

### G-T01 — `BelongsToTenant` on every tenant-owned model

**Rule:** Every Eloquent model that belongs to a tenant must use the `BelongsToTenant` trait.

**What `BelongsToTenant` does:**
1. Registers `TenantScope` as a global Eloquent scope (auto-applies `WHERE tenant_id = ?` to all queries)
2. Listens to the `creating` event to auto-populate `tenant_id` from `TenantContextContract`
3. Declares a `tenant()` `BelongsTo` relationship

**Risk of violation:** Queries against the model return records from all tenants. A user in Tenant A can read, update, or delete Tenant B data.

**Enforced by:** `TenantScopeTest` — proves that queries without resolved context throw, and with context return only scoped records.

---

### G-T02 — `tenant_id` is NEVER accepted from user input

**Rule:** No Form Request class may include `tenant_id` in its `rules()` array (even as `prohibited`).

**Where `tenant_id` comes from:** The `BelongsToTenant` trait's `creating` event reads it from `TenantContextContract`. Controllers and requests have no involvement.

**Risk of violation:** A caller could supply a `tenant_id` in the request body, overriding the resolved context, and create records attributed to a different tenant.

**Enforced by:** `ProjectApiTest` — `it('tenant_id is auto-filled from context, not from request input')`.

---

### G-T03 — `withoutGlobalScopes()` (plural) is FORBIDDEN

**Rule:** Never call `withoutGlobalScopes()` (removes all scopes). Only `withoutGlobalScope(TenantScope::class)` (targeted removal) is permitted, and only in platform infrastructure code — never in module controllers, services, or jobs.

**Permitted use of `withoutGlobalScope(TenantScope::class)`:**
- `TenantScope::apply()` itself (to avoid re-applying to already-scoped queries)
- Platform admin tooling that explicitly needs cross-tenant data

**Risk of violation:** Removes all global scopes including `TenantScope`, causing unscoped queries across all tenants.

---

### G-T04 — No persistent active tenant

**Rule:** Tenant identity must never be stored in session, cookie, JWT claim, or any mechanism that persists across HTTP requests independently of explicit resolution.

**How tenant is established:** The `X-Tenant-Id` request header is resolved fresh by `ResolveTenant` middleware on every request.

**Risk of violation:** If a session stored the active tenant, a user could switch tenants simply by replaying a session — bypassing `ValidateTenantMembership` entirely.

**Enforced by:** `TenancyMiddlewareTest`.

---

### G-T05 — `TenantContextContract` is the only runtime tenant source

**Rule:** All code that needs to know the current tenant reads `TenantContextContract`. No code reads `X-Tenant-Id` directly, no code uses `Auth::user()->tenant_id` (that column does not exist on `users`).

**Risk of violation:** Bypasses the resolved+validated tenant context, allowing code to act on a tenant that was not properly resolved and membership-checked.

---

## II. Routing & Middleware Invariants

### G-R01 — `tenant.resolve` MUST execute before `SubstituteBindings`

**Rule:** For any route with a tenant-owned model parameter, `tenant.resolve` must appear before `SubstituteBindings::class` in the middleware stack.

**Why:** Route model binding (`SubstituteBindings`) resolves `{project}` by executing an Eloquent `find()`. `TenantScope` is applied during that query. If `TenantContextContract` is not populated yet (because `tenant.resolve` hasn't run), `TenantScope::apply()` throws or behaves incorrectly — and in the worst case resolves the model without tenant filtering.

**Enforced by:**
- `TenantRouteMiddleware::STACK` constant encodes the correct order
- `TenantRouteBindingConventionTest` — 5 structural + 2 runtime tests

**Reference:** [ADR-011](../../adr/ADR-011-tenant-route-model-binding.md)

---

### G-R02 — `TenantRouteMiddleware::STACK` is the only valid middleware stack for tenant routes

**Rule:** Never construct an inline middleware array for tenant-owned routes. Always use the constant:

```php
use App\Core\Tenancy\Routing\TenantRouteMiddleware;

Route::middleware(TenantRouteMiddleware::STACK)->group(function (): void {
    // ...
});
```

**Risk of violation:** Manual arrays allow ordering mistakes (G-R01 violation) or missing middleware (auth bypass, membership bypass).

---

### G-R03 — Service-provider routes do NOT inherit the `api` group

**Rule:** Routes registered via `$this->loadRoutesFrom()` in a service provider do NOT automatically receive the `api` middleware group (which normally applies `SubstituteBindings`). All middleware must be applied explicitly.

**Risk of violation:** Believing that `SubstituteBindings` is applied by the framework means a developer might omit it from the explicit stack — causing route model binding to silently fail (parameters resolve to empty model instances instead of throwing 404).

**Discovery:** Found during Block 4 implementation. Documented in ADR-011.

---

## III. Authorization Invariants

### G-A01 — Platform admins receive NO automatic policy bypass

**Rule:** `ProjectPolicy` (and all module policies) must not check `$user->is_platform_admin` and return `true`. Platform admin status does not grant cross-tenant access.

**Why:** Platform admins are still subject to tenant membership checks. Bypassing the policy for admins would allow a platform admin to read, modify, or delete any tenant's data without being a member — violating tenant isolation.

**How platform admins operate on tenant data:** Only after being explicitly added as a member of that tenant.

**Enforced by:** `ProjectApiTest` — `it('platform admin cannot bypass tenant authorization')`.

---

### G-A02 — Every tenant-owned model must have a registered policy

**Rule:** Every model that uses `BelongsToTenant` must have a corresponding `{Model}Policy` registered via `Gate::policy()` in the module provider's `boot()`.

**Risk of violation:** Without a registered policy, `$this->authorize('create', Project::class)` defaults to denying all (which is safe) but the behavior is undefined across Laravel versions. Explicit policies are required.

---

### G-A03 — Authorization happens at the policy layer, not the controller

**Rule:** Controllers call `$this->authorize()` and delegate all access control to the `{Model}Policy`. Controllers do not check `$user->membership_role` directly.

**Risk of violation:** Logic scattered across controllers and policies means authorization rules are duplicated and can drift.

---

## IV. Async / Queue Invariants

### G-Q01 — Jobs that need tenant context must use `HasTenantContext`

**Rule:** Any queued job that queries tenant-owned models must use the `HasTenantContext` trait. This trait captures the current tenant ID at dispatch time and restores it via `RestoreTenantContext` job middleware.

**Risk of violation:** Without `HasTenantContext`, a queued job runs with an empty `TenantContextContract`. Queries against tenant-owned models throw `TenantContextNotResolvedException`.

**Enforced by:** `TenantQueuePropagationTest`.

---

### G-Q02 — `RestoreTenantContext` finally block must never be removed

**Rule:** `RestoreTenantContext::handle()` uses a `finally` block to call `$context->clear()`. This guarantees context cleanup even if the job throws an exception.

**Risk of violation:** Without `finally { $context->clear() }`, a failed job in a long-running worker leaves stale tenant context in the container for subsequent jobs.

---

## V. Module Boundary Invariants

### G-M01 — Domain → Domain direct coupling is FORBIDDEN

**Rule:** A `Domain` module must not import or instantiate another `Domain` module's models, services, or repositories directly.

**Communication allowed:**
- Via dispatched events (`Event::dispatch(new ProjectCreated(...))`)
- Via published contracts (interface in `Contracts/` implemented by the target module)
- Via data transfer objects exchanged through service method calls on contracts

**Risk of violation:** Creates hidden coupling. If `Domain\Billing` imports `Domain\Projects\Models\Project`, removing or renaming `Project` breaks `Billing` invisibly.

---

### G-M02 — `App\Shared` must not contain Eloquent models or HTTP classes

**Rule:** `App\Shared` is reserved for framework-agnostic value objects, type definitions, and utility contracts. No Eloquent, no HTTP, no Laravel-specific infrastructure.

**Risk of violation:** `Shared` becomes a dumping ground, breaking the dependency hierarchy.

---

## VI. Container Invariants

### G-C01 — `scoped()` for per-request state, `singleton()` for stateless infrastructure

**Rule:** See [service-provider-conventions.md](service-provider-conventions.md). Objects that hold per-request state (tenant context, caches keyed to the current request) must be `scoped()`. Objects that are stateless (loggers, cache wrappers without per-request state) may be `singleton()`.

**Risk of violation on Octane / long-running workers:** A `singleton()` that holds per-request state leaks state from one request into the next.

---

## Summary Table

| Code | Rule | Impact of Violation |
|---|---|---|
| G-T01 | `BelongsToTenant` on tenant models | Cross-tenant data access |
| G-T02 | No `tenant_id` from user input | Tenant spoofing |
| G-T03 | No `withoutGlobalScopes()` | Unscoped cross-tenant queries |
| G-T04 | No persistent active tenant | Tenant identity persistence attack |
| G-T05 | `TenantContextContract` is the only tenant source | Bypassed tenant context |
| G-R01 | `tenant.resolve` before `SubstituteBindings` | Cross-tenant model binding (security defect) |
| G-R02 | Always use `TenantRouteMiddleware::STACK` | Middleware ordering mistakes |
| G-R03 | Provider routes don't inherit `api` group | Silent binding failures |
| G-A01 | Platform admin no bypass | Cross-tenant admin access |
| G-A02 | Every model has a registered policy | Undefined authorization behavior |
| G-A03 | Authorization at policy layer | Scattered authorization logic |
| G-Q01 | `HasTenantContext` on tenant jobs | Jobs fail with unresolved context |
| G-Q02 | `finally` block in `RestoreTenantContext` | Stale context in long-running workers |
| G-M01 | No Domain → Domain coupling | Hidden cross-module dependencies |
| G-M02 | No Eloquent in `Shared` | Dependency hierarchy broken |
| G-C01 | `scoped()` for per-request state | State bleed in Octane |

---

## References

- [ADR-011 — Tenant-Safe Route Model Binding](../../adr/ADR-011-tenant-route-model-binding.md)
- [ADR-003 — Shared Database + Tenant ID](../../adr/ADR-003-shared-database-tenant-id.md)
- [ADR-004 — Module-Oriented Architecture](../../adr/ADR-004-module-oriented-architecture.md)
- [Tenancy Architectural Warnings](../tenancy/architectural-warnings.md)
- [future-scaffolding.md](future-scaffolding.md)
