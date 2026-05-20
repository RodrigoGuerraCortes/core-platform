# Middleware Strategy

## Purpose

This document defines the official middleware architecture for the Tenancy module.

The goal is to ensure that organizational context is resolved consistently and deterministically before any tenant-aware business logic executes.

Middleware is responsible for:

* tenant resolution
* tenant validation
* membership validation
* tenant context initialization
* platform route bypass handling

Middleware is NOT responsible for:

* business authorization
* feature permissions
* domain rules
* application workflows

---

# Core Principle

Tenant resolution is infrastructure responsibility.

Organizational context must exist before:

* controllers
* actions
* queries
* policies
* global scopes
* cache access
* queue dispatching

execute.

---

# Official Middleware Flow

Recommended runtime order:

```text id="c0zhr9"
1. Global middleware
2. Session middleware
3. ResolveTenant
4. Authentication
5. ValidateTenantMembership
6. Authorization
7. Controllers / Actions / Queries
```

---

# Middleware Responsibilities

## ResolveTenant

Primary responsibility:

* resolve tenant context
* validate tenant existence
* initialize TenantContext
* fail fast on invalid context

Responsibilities include:

* reading tenant identifier
* resolving tenant entity
* validating tenant status
* rejecting invalid tenants

Example sources:

```http id="umjlwm"
X-Tenant-Id
```

Future:

* subdomain
* path
* gateway metadata

---

## ValidateTenantMembership

Primary responsibility:

validate that the authenticated user belongs to the resolved tenant.

Example:

```text id="j4bwh6"
User authenticated
Tenant resolved
User not member of tenant
→ reject request
```

Expected response:

```http id="m4lzt7"
403 Forbidden
```

---

# Middleware Separation

The platform intentionally separates:

```text id="lcbqzi"
ResolveTenant
```

from:

```text id="2d88r3"
ValidateTenantMembership
```

Reasons:

* clearer responsibilities
* simpler testing
* future platform bypass flexibility
* operational tooling support
* easier observability

---

# TenantContext Initialization

The ResolveTenant middleware is responsible for initializing:

```php id="wn1x1v"
app(TenantContext::class)
```

Example:

```php id="89ruq0"
$context->setTenant($tenant);
```

After initialization:

all downstream code must consume tenant information exclusively through TenantContext.

---

# Forbidden Middleware Patterns

The following patterns are forbidden:

---

## Domain-Level Tenant Resolution

Forbidden:

```php id="p74vbn"
$request->header('X-Tenant-Id')
```

inside:

* controllers
* services
* actions
* repositories
* policies

---

## Business Logic in Middleware

Middleware must never contain:

* business rules
* domain workflows
* feature authorization
* application decisions

Middleware only prepares runtime infrastructure context.

---

## Persistent Active Tenant State

The platform forbids:

* session-based active tenant
* cached active tenant state
* implicit organization switching

Tenant context must always be explicit per request.

---

# Platform Route Bypass

Some routes operate globally and may bypass tenant resolution.

Examples:

```text id="4p0cc8"
/admin
/platform/*
/health
/metrics
/observability
```

These routes operate under:

```text id="cstlmy"
platform context
```

instead of:

```text id="s1q0x0"
tenant context
```

---

# Platform Middleware Groups

Recommended future middleware groups:

---

## Platform Routes

```php id="n2u65w"
['web', 'auth']
```

No tenant resolution required.

---

## Tenant Web Routes

```php id="3n1ljm"
[
  'web',
  ResolveTenant::class,
  Authenticate::class,
  ValidateTenantMembership::class,
]
```

---

## Tenant API Routes

```php id="d39m0g"
[
  'api',
  ResolveTenant::class,
  'auth:sanctum',
  ValidateTenantMembership::class,
]
```

---

# Middleware Failure Strategy

The platform uses fail-fast behavior.

---

## Missing Tenant Header

Example:

```http id="b2qjlwm"
400 Bad Request
```

---

## Invalid Tenant

Example:

```http id="xt6fwk"
404 Not Found
```

or:

```http id="djj8p4"
422 Unprocessable Entity
```

depending on implementation strategy.

---

## Disabled Tenant

Example:

```http id="h8utx5"
403 Forbidden
```

---

## Missing Membership

Example:

```http id="0dy38n"
403 Forbidden
```

---

# Middleware and Global Scopes

Middleware prepares the runtime context required by tenant-aware global scopes.

Example:

```php id="w0vz7v"
TenantScope
```

Global scopes depend on:

```php id="6b3c0n"
TenantContext
```

Therefore:

TenantContext must exist before any scoped query executes.

---

# Queue and Async Strategy

Queued jobs must restore tenant context before execution.

Recommended future flow:

```text id="7eh1ka"
Dispatch Job
→ Serialize tenant_id
→ Worker restores TenantContext
→ Execute job
```

Tenant restoration is infrastructure responsibility.

---

# Observability Integration

Middleware should eventually enrich:

* logs
* traces
* metrics
* exception reporting

with tenant metadata.

Example:

```json id="p42txq"
{
  "tenant_id": "tenant_123",
  "tenant_slug": "nativeit"
}
```

This improves:

* debugging
* operational support
* auditability
* distributed tracing

---

# Testing Strategy

Middleware tests must validate:

* valid tenant resolution
* invalid tenant rejection
* membership rejection
* platform bypass behavior
* TenantContext initialization
* fail-fast behavior

Cross-tenant leakage tests are mandatory.

---

# Long-Term Goals

The middleware architecture is designed to support:

* SaaS applications
* modular monoliths
* distributed systems
* AI agents
* async workers
* future gateway architectures

without changing the tenant model.

---

# Final Principle

Middleware prepares organizational runtime context.

Business domains consume organizational runtime context.

These responsibilities must remain permanently separated.
