# Tenant Resolution Strategy

## Purpose

This document defines the official tenant resolution lifecycle for the Core Platform.

Tenant resolution is responsible for determining the organizational context of the current request and making that context available to the entire application runtime.

The goal is to ensure deterministic, explicit, and safe organizational isolation across all modules and applications.

---

# Core Principle

Tenant context is request-scoped infrastructure state.

The platform does not support persistent active tenant state.

Each tenant-aware request must explicitly provide tenant context.

Authentication identifies WHO the user is.

Tenant resolution identifies WHERE the operation occurs.

Authorization defines WHAT the authenticated user can do inside the resolved tenant context.

---

# Official Resolution Strategy

## Phase 1 Strategy

The official resolution mechanism is:

```http
X-Tenant-Id
```

Example:

```http
GET /projects
Authorization: Bearer xxx
X-Tenant-Id: tenant_123
```

This strategy is intentionally:

* API-first
* deterministic
* stateless
* request-scoped
* worker-friendly
* AI-agent-friendly

---

# Why Header-Based Resolution

The platform intentionally avoids persistent tenant session state.

Reasons:

* reduced session complexity
* easier observability
* deterministic debugging
* simpler SPA architecture
* simpler mobile integration
* easier worker propagation
* easier async processing
* fewer multi-tab inconsistencies
* easier testing

---

# Future Resolution Strategies

The platform may eventually support additional resolution mechanisms.

## Subdomain Resolution

Example:

```text
acme.platform.com
```

## Path Resolution

Example:

```text
/platform/acme
```

---

# Canonical Resolution Rule

Regardless of how tenant context is resolved:

* header
* subdomain
* path
* gateway
* proxy

all strategies must eventually resolve into:

```php
TenantContext
```

Business logic must never depend directly on resolution source.

---

# Resolution Lifecycle

## Step 1 — Incoming Request

A request enters the application.

Example:

```http
GET /api/projects
Authorization: Bearer xxx
X-Tenant-Id: tenant_123
```

---

## Step 2 — Tenant Resolution Middleware

The `ResolveTenant` middleware executes early in the middleware pipeline.

Responsibilities:

* read raw tenant identifier
* validate format
* locate tenant entity
* validate tenant status
* validate tenant is active
* initialize TenantContext

The middleware is the ONLY place allowed to resolve raw tenant input.

---

## Step 3 — TenantContext Initialization

The middleware initializes request-scoped tenant context.

Example:

```php
app(TenantContext::class)->setTenant($tenant);
```

From this point forward:

* controllers
* actions
* queries
* services
* jobs
* notifications
* policies

must consume tenant information only through TenantContext.

---

## Step 4 — Request Execution

The application executes using resolved organizational context.

Examples:

* tenant-scoped queries
* tenant-scoped authorization
* tenant-scoped cache keys
* tenant-scoped events
* tenant-scoped jobs

---

# TenantContext

## Purpose

TenantContext is the canonical organizational context provider for the current request lifecycle.

Example:

```php
app(TenantContext::class)
```

---

## Responsibilities

TenantContext is responsible for exposing:

* tenant identifier
* tenant entity
* tenant metadata
* organizational runtime context

---

## Forbidden Access Patterns

The following patterns are forbidden inside domain logic:

### Direct Header Access

```php
$request->header('X-Tenant-Id')
```

### Direct Route Parsing

```php
request()->route()
```

### Direct Subdomain Parsing

```php
request()->getHost()
```

These are infrastructure concerns, not domain concerns.

---

# Middleware Strategy

## Middleware Ordering

Recommended order:

```text
1. global middleware
2. session middleware
3. ResolveTenant
4. authentication
5. authorization
6. controllers/actions
```

---

# Why Tenant Resolution Happens Early

Tenant context must exist before:

* authorization
* policies
* global scopes
* cache usage
* event dispatching
* queue dispatching

This guarantees deterministic tenant isolation.

---

# Fail-Fast Strategy

## Missing Tenant Context

Requests without valid tenant context must fail immediately.

Example response:

```http
400 Bad Request
```

or

```http
422 Unprocessable Entity
```

depending on implementation strategy.

---

## Invalid Tenant

Invalid tenants must fail immediately.

Examples:

* tenant does not exist
* tenant is disabled
* tenant is soft deleted
* malformed tenant identifier

---

## Unauthorized Membership

If the authenticated user does not belong to the resolved tenant:

```http
403 Forbidden
```

must be returned.

---

# Platform Route Exceptions

Certain routes operate outside tenant context.

Examples:

```text
/admin
/platform/*
/health
/metrics
/observability
```

These routes may bypass tenant resolution entirely.

---

# Platform Administrators

Platform administrators may:

* manage tenants
* access operational tooling
* perform support operations

However:

platform administrators do NOT automatically bypass tenant isolation for tenant-owned data.

Tenant-owned operations still require explicit tenant context.

---

# Tenant Membership Validation

Tenant resolution alone is insufficient.

After tenant resolution:

membership validation must occur.

Example:

```text
User belongs to Tenant A
Request resolves Tenant B
→ reject request
```

Tenant membership validation is mandatory for all tenant-owned operations.

---

# Tenant Resolution and Sanctum

Sanctum authentication remains identity-focused.

Tokens identify the user.

Tokens do not resolve tenant context automatically.

Tenant resolution remains explicit and request-scoped.

This separation prevents hidden organizational state inside authentication flows.

---

# Queue and Async Propagation

Tenant context must propagate to:

* queued jobs
* retries
* notifications
* scheduled tasks
* async operations

Queued jobs must serialize tenant context explicitly.

Example:

```php
$job->tenantId
```

Queue workers must restore TenantContext before execution.

---

# Cache Resolution Rules

Tenant-owned cache entries must include tenant namespace.

Example:

```text
tenant:{tenantId}:settings
```

Global cache entries must be explicitly marked:

```text
global:feature-flags
```

---

# Observability and Logging

Tenant context should eventually be attached to:

* logs
* traces
* metrics
* audit events
* exception reporting

Examples:

```json
{
  "tenant_id": "tenant_123",
  "user_id": 5
}
```

This improves:

* debugging
* operational support
* auditability
* tracing

---

# Security Principles

## Explicit Organizational Context

Tenant context must always be explicit.

Hidden or implicit organizational state is forbidden.

---

## Deterministic Isolation

Every tenant-aware request must deterministically resolve organization context before business logic executes.

---

## Infrastructure Ownership

Tenant resolution is infrastructure responsibility.

Applications and business domains must never implement their own tenant resolution logic.

---

# Long-Term Goals

The resolution architecture is designed to support:

* multi-tenant SaaS applications
* internal enterprise platforms
* AI agents
* async workers
* distributed services
* future package extraction
* future gateway-based routing

without changing the organizational model.

---

# Final Principle

Tenant resolution must remain:

* explicit
* deterministic
* stateless
* request-scoped
* infrastructure-driven
* isolated from domain logic

The platform must always know exactly which organization owns the current runtime context before business execution begins.
