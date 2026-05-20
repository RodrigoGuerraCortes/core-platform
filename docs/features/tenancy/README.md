# README

## Purpose

The Tenancy module provides organizational isolation infrastructure for the Core Platform.

The module is responsible for:

* tenant resolution
* tenant membership
* organizational context propagation
* tenant isolation
* tenant-aware infrastructure behavior

The module is NOT responsible for:

* authentication
* business authorization
* RBAC
* billing
* onboarding
* subscription management

---

# Core Philosophy

Tenancy is infrastructure, not business logic.

The platform intentionally uses:

* global user identities
* request-scoped tenant context
* explicit tenant resolution
* shared database isolation

to guarantee deterministic organizational boundaries.

---

# Core Concepts

## User

Global platform identity.

Users may belong to multiple tenants simultaneously.

---

## Tenant

Organizational isolation boundary.

Externally applications may expose:

* Organization
* Workspace
* Company
* Hospital

depending on product requirements.

---

## TenantContext

Canonical request-scoped organizational runtime provider.

Example:

```php
app(TenantContext::class)
```

---

# Official Resolution Strategy

Current official strategy:

```http
X-Tenant-Id
```

Future strategies may include:

* subdomains
* path resolution
* gateway-based resolution

Regardless of strategy, all resolution mechanisms must initialize:

```php
TenantContext
```

---

# Runtime Lifecycle

Standard tenant-aware flow:

```text
Request
→ ResolveTenant
→ TenantContext
→ Authentication
→ Membership Validation
→ Authorization
→ Business Execution
```

---

# Middleware

Core middleware:

* ResolveTenant
* ValidateTenantMembership

Responsibilities:

* tenant resolution
* membership validation
* fail-fast isolation enforcement

---

# Database Strategy

Official architecture:

```text
shared database + tenant isolation
```

Key tables:

```text
tenants
tenant_user
```

Users remain globally scoped.

Tenant ownership uses explicit membership relationships.

---

# Architectural Rules

## Never Add tenant_id to Users

Users are global identities.

---

## Never Read Tenant Directly From Request in Domain Code

Only middleware may resolve raw tenant input.

Business logic must consume:

```php
TenantContext
```

---

## Never Use Persistent Active Tenant State

Tenant context is request-scoped and explicit.

---

# Deferred Features

Explicitly postponed:

* database-per-tenant
* schema-per-tenant
* self-service provisioning
* tenant-aware RBAC
* tenant-aware billing
* tenant-aware onboarding

until supporting platform maturity exists.

---

# Documentation

Core tenancy documents:

* overview.md
* business-rules.md
* tenant-resolution.md
* middleware-strategy.md
* flows.md
* testing-strategy.md
* implementation-plan.md
* architectural-warnings.md
* database-design.md
* events.md

---

# Long-Term Goals

The module is designed to support:

* SaaS platforms
* modular monoliths
* distributed systems
* async workers
* AI agents
* future package extraction

without changing organizational runtime behavior.

---

# Final Principle

The Tenancy module exists to guarantee deterministic organizational isolation across the entire platform lifecycle.
