# Tenancy Overview

## Purpose

The Tenancy module is responsible for organizational isolation inside the Core Platform.

Tenancy is infrastructure, not business logic.

The module defines how organizational context is resolved, propagated, enforced, and isolated across all platform applications and modules.

The primary goal of Tenancy is to ensure that organizational data, permissions, operations, and runtime context remain isolated between organizations while still supporting globally shared user identities.

---

# Core Principles

## 1. Global Identities

Users are global platform identities.

A user may belong to multiple organizations simultaneously.

The `users` table must never contain a `tenant_id` column.

Correct relationship:

```text
users
  ↕
tenant_user
  ↕
tenants
```

Incorrect relationship:

```text
users.tenant_id
```

This architecture enables:

- multi-organization membership
- centralized authentication
- platform-wide support operations
- future cross-organization collaboration
- invitation flows
- global auditing
- reusable identity lifecycle management

---

## 2. Tenant = Organization

Internally, the system uses the term:

```text
Tenant
```

Externally, applications may expose different terminology depending on product context:

- Organization
- Workspace
- Company
- Hospital
- Community

The internal architecture must always remain tenant-oriented regardless of UI naming.

---

## 3. Request-Scoped Tenant Context

The platform does not support persistent “active tenant” state.

Tenant context exists only within the current request lifecycle.

Each tenant-aware request must explicitly provide tenant context.

Current strategy:

```http
X-Tenant-Id
```

Authentication identifies WHO the user is.

Tenancy identifies WHERE the operation occurs.

Authorization defines WHAT the user can do inside that tenant context.

---

# Separation of Concerns

## Identity/Auth

Responsible for:

- authentication
- sessions
- Sanctum tokens
- password reset
- email verification
- global user identity

Identity/Auth is NOT responsible for:

- tenant resolution
- tenant membership
- tenant isolation
- tenant permissions
- tenant switching

---

## Tenancy

Responsible for:

- tenant resolution
- tenant membership
- tenant isolation
- tenant context propagation
- tenant-aware infrastructure concerns
- tenant middleware
- tenant-aware scoping

Tenancy is NOT responsible for:

- authentication
- business permissions
- feature authorization
- RBAC policies
- billing
- onboarding
- subscription management

---

## Authorization

Authorization is a separate module.

Authorization will eventually define:

- roles
- permissions
- policies
- tenant-specific capabilities

Authorization depends on Tenancy context but is not part of Tenancy itself.

---

# Tenant Resolution Strategy

## Current Official Strategy

Phase 1 resolution strategy:

```http
X-Tenant-Id
```

This strategy is intentionally API-first and request-scoped.

Reasons:

- simpler observability
- simpler debugging
- deterministic behavior
- easier testing
- easier mobile integration
- easier worker propagation
- easier AI-agent compatibility
- reduced session complexity

---

## Future Strategies

Future resolution mechanisms may include:

### Subdomain Resolution

```text
acme.platform.com
```

### Path Resolution

```text
/platform/acme
```

Regardless of resolution strategy, all approaches must eventually resolve into:

```php
TenantContext
```

No business code may depend directly on:

- headers
- subdomains
- route paths
- request payloads

---

# TenantContext

The platform uses a request-scoped `TenantContext` singleton.

Example:

```php
app(TenantContext::class)
```

Tenant-aware services must obtain tenant information only through TenantContext.

Never:

```php
$request->header('X-Tenant-Id')
```

inside business logic.

Never:

```php
request()->route()
```

for tenant resolution inside domain code.

TenantContext becomes the canonical source of organizational context.

---

# Tenant Isolation

Tenant isolation is mandatory.

By default:

- every tenant-aware request must resolve tenant context
- every tenant-owned entity must be scoped by tenant
- every cache key must include tenant namespace
- every queued job must propagate tenant context

Requests without valid tenant context must fail immediately unless explicitly categorized as platform operations.

---

# Platform Context vs Tenant Context

The platform distinguishes between:

## Platform Context

Global operational scope.

Examples:

- platform administration
- observability
- support tooling
- tenant provisioning
- operational analytics

Examples of platform routes:

```text
/admin
/platform/*
```

Platform routes may bypass tenant resolution.

---

## Tenant Context

Organizational scope.

Examples:

- tenant data
- tenant operations
- tenant dashboards
- tenant-owned resources
- tenant-scoped permissions

Tenant routes require explicit tenant context.

---

# Platform Administrators

Platform administrators are global operational users.

They may:

- manage tenants
- perform operational support
- access global tooling
- observe platform health

They must NOT automatically bypass tenant isolation for tenant-owned data.

Tenant-owned data still requires explicit tenant context.

---

# Tenant Membership

Users may belong to multiple tenants.

Membership is modeled using:

```text
tenant_user
```

The membership layer will eventually support:

- ownership
- administration
- invitations
- role assignment
- future RBAC integration

Initial membership roles:

- owner
- admin
- member

These are operational membership types, not full RBAC permissions.

---

# Tenant Metadata

Tenants support flexible metadata storage from the beginning.

Recommended structure:

```sql
metadata jsonb
settings jsonb
```

Examples:

- branding
- localization
- timezone
- feature toggles
- operational preferences

This prevents future schema explosion for organization configuration.

---

# Soft Deletes

Tenants support soft deletes.

Reasons:

- auditability
- recovery
- operational safety
- future billing workflows
- historical traceability

Tenant deletion must never immediately destroy organizational data.

---

# Global Scopes

Tenant-owned entities will eventually use tenant-aware global scopes.

Example:

```php
TenantScope
```

Global scopes are responsible for preventing accidental cross-tenant access.

Bypassing tenant scopes must:

- be explicit
- be auditable
- be operationally restricted

---

# Queue and Async Propagation

All queued jobs must propagate tenant context.

Tenant context must survive:

- queued jobs
- retries
- notifications
- events
- scheduled operations

Tenant propagation is infrastructure responsibility, not domain responsibility.

---

# Cache Strategy

All tenant-owned cache entries must include tenant namespace.

Example:

```text
tenant:{tenantId}:settings
```

Global cache entries must be explicitly marked.

Example:

```text
global:feature-flags
```

---

# Architectural Rules

## Never Add tenant_id to Users

Users are global identities.

Tenant ownership belongs to membership tables, not identity tables.

---

## Never Read Tenant Directly From Request in Domain Code

Only middleware may resolve raw tenant input.

All business logic must depend on TenantContext.

---

## Never Couple Authentication to Tenancy

Authentication identifies the user.

Tenancy resolves organizational context.

Authorization evaluates capabilities within tenant context.

These boundaries must remain independent.

---

## Never Implement Database-Per-Tenant Prematurely

The official strategy is:

```text
shared database + tenant isolation
```

Database-per-tenant and schema-per-tenant strategies are intentionally postponed until operational requirements justify the complexity.

---

# Long-Term Goals

The Tenancy module is designed to support:

- SaaS applications
- multi-organization systems
- platform operations
- AI-agent infrastructure
- modular domain applications
- future package extraction
- future distributed architectures

without changing the core organizational model.

---

# Final Principle

Tenancy is a cross-cutting infrastructure concern.

It must remain:

- deterministic
- explicit
- request-scoped
- framework-agnostic
- reusable
- isolated from business domains

The primary responsibility of the Tenancy module is to guarantee safe organizational boundaries across the entire platform.
