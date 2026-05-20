# Tenancy Business Rules

## Purpose

This document defines the operational and architectural rules governing tenant behavior inside the Core Platform.

These rules are mandatory and must be respected by all modules and future applications built on top of the platform.

---

# Tenant Definition

A tenant represents an organizational isolation boundary.

Internally:

```text
Tenant
```

Externally, applications may expose:

- Organization
- Workspace
- Company
- Hospital
- Community

depending on product requirements.

---

# User Membership Rules

## Users Are Global

Users are global platform identities.

Rules:

- users must not belong to a single tenant permanently
- users may belong to multiple tenants
- users must not contain tenant_id columns
- email uniqueness is global

---

## Membership Is Explicit

Users access tenants through explicit membership relationships.

Official pivot table:

```text
tenant_user
```

---

## Membership Roles

Initial operational membership roles:

- owner
- admin
- member

These are not RBAC permissions.

Future authorization modules may extend capabilities independently.

---

# Tenant Resolution Rules

## Tenant Context Is Mandatory

All tenant-aware requests must resolve tenant context.

Current official strategy:

```http
X-Tenant-Id
```

Requests without valid tenant context must fail immediately unless explicitly categorized as platform operations.

---

## Tenant Context Is Request-Scoped

The platform does not support persistent active tenant state.

Tenant context exists only during the current request lifecycle.

---

## TenantContext Is the Only Valid Source

Business logic must never resolve tenant context directly from:

- headers
- route parameters
- request payloads
- sessions

Only:

```php
app(TenantContext::class)
```

is valid inside application services and domain logic.

---

# Platform Operations Rules

## Platform Context Exists Outside Tenant Context

Certain routes operate globally.

Examples:

- platform administration
- observability
- operational tooling
- tenant provisioning

Examples:

```text
/admin
/platform/*
```

These routes may bypass tenant resolution.

---

## Platform Admins Do Not Bypass Tenant Isolation Automatically

Platform administrators may:

- manage tenants
- perform support operations
- access global tooling

But tenant-owned data still requires explicit tenant context.

---

# Tenant Isolation Rules

## Tenant Isolation Is Mandatory

Tenant-owned data must never leak across organizations.

All tenant-aware operations must enforce tenant isolation.

---

## Tenant-Owned Models Must Be Scoped

Tenant-owned entities must eventually use tenant-aware global scopes.

Bypassing scopes must:

- be explicit
- be auditable
- be restricted

---

## Cross-Tenant Access Is Forbidden

A user belonging to one tenant must never access another tenant’s data unless explicit membership exists.

---

# Queue and Async Rules

## Tenant Context Must Propagate

All queued jobs must preserve tenant context.

This includes:

- jobs
- retries
- notifications
- scheduled tasks
- events

---

# Cache Rules

## Tenant Cache Must Be Namespaced

Tenant-owned cache keys must include tenant namespace.

Example:

```text
tenant:{tenantId}:settings
```

Global cache entries must be explicitly marked.

---

# Metadata Rules

## Tenants Support Flexible Metadata

Tenants support:

```sql
metadata jsonb
settings jsonb
```

This supports future organizational configuration without schema explosion.

---

# Soft Delete Rules

## Tenants Support Soft Deletes

Tenant deletion must not immediately destroy organizational data.

Reasons:

- auditability
- recovery
- operational safety
- future billing support

---

# Architectural Restrictions

## Forbidden Patterns

The following patterns are forbidden:

### Users Table Tenant Ownership

```text
users.tenant_id
```

### Request-Based Tenant Resolution in Domain Code

```php
$request->header('X-Tenant-Id')
```

inside business logic.

### Session-Based Active Tenant

Persistent tenant session state is forbidden.

### Premature Database-Per-Tenant

The official architecture is:

```text
shared database + tenant isolation
```

---

# Final Rule

Tenant isolation is infrastructure responsibility.

Applications, domains, and business services must rely on the Tenancy module instead of implementing organizational isolation independently.
