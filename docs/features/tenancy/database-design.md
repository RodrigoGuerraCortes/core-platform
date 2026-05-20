# Database Design

## Purpose

This document defines the official database architecture for the Tenancy module.

The goal is to establish deterministic organizational isolation while maintaining operational simplicity and long-term scalability.

The official strategy is:

```text
shared database + tenant isolation
```

---

# Core Principles

## Global User Identities

Users are global platform identities.

The `users` table must never contain:

```text
tenant_id
```

Correct relationship:

```text
users
↕
tenant_user
↕
tenants
```

---

## Tenant-Owned Data

Tenant-owned entities must eventually contain:

```text
tenant_id
```

Examples:

* projects
* cases
* uploads
* notifications
* future business entities

---

# Tenants Table

## Official Table

```text
tenants
```

---

# Recommended Initial Schema

```sql
CREATE TABLE tenants (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    metadata JSONB NULL,
    settings JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);
```

---

# Field Definitions

## id

Unique tenant identifier.

Recommendation:

* ULID or BIGINT strategy must remain globally consistent across platform architecture.

---

## name

Human-readable organization name.

Examples:

* NativeIT
* Bupa
* MYL Labs

---

## slug

Globally unique organizational slug.

Examples:

```text
nativeit
bupa
myl-labs
```

Used for:

* debugging
* observability
* future URLs
* future subdomains
* operational tooling

---

## metadata

Flexible tenant metadata.

Recommended type:

```sql
JSONB
```

Examples:

* branding
* localization
* contact information
* onboarding metadata

---

## settings

Flexible operational settings.

Recommended type:

```sql
JSONB
```

Examples:

* feature flags
* tenant configuration
* preferences
* operational toggles

---

## deleted_at

Soft delete support is mandatory.

Tenant deletion must remain recoverable.

---

# Membership Table

## Official Pivot Table

```text
tenant_user
```

---

# Recommended Initial Schema

```sql
CREATE TABLE tenant_user (
    tenant_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    membership_role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    PRIMARY KEY (tenant_id, user_id)
);
```

---

# Membership Roles

Initial operational roles:

* owner
* admin
* member

These are NOT full RBAC permissions.

Future Authorization module will extend capabilities separately.

---

# Index Strategy

Recommended indexes:

```sql
CREATE INDEX idx_tenant_user_user_id
ON tenant_user(user_id);

CREATE INDEX idx_tenant_user_tenant_id
ON tenant_user(tenant_id);

CREATE UNIQUE INDEX idx_tenants_slug
ON tenants(slug);
```

---

# Future Tenant-Owned Entities

Future tenant-owned tables should eventually follow:

```sql
tenant_id BIGINT NOT NULL
```

Examples:

```sql
projects
cases
uploads
notifications
```

---

# Foreign Key Strategy

Recommended approach:

```sql
FOREIGN KEY (tenant_id)
REFERENCES tenants(id)
```

However:

foreign key strictness should remain operationally pragmatic to avoid migration lock complexity during future scaling.

---

# Tenant Isolation Strategy

Tenant isolation is enforced primarily through:

* middleware
* TenantContext
* membership validation
* global scopes
* testing
* async propagation

NOT through separate databases.

---

# Shared Database Strategy

Official architecture:

```text
shared database + tenant isolation
```

Reasons:

* simpler migrations
* simpler observability
* simpler analytics
* simpler local development
* simpler deployment
* lower infrastructure complexity

---

# Explicitly Deferred Strategies

The following strategies are intentionally postponed:

---

## Database-Per-Tenant

Deferred indefinitely unless operational requirements justify complexity.

---

## Schema-Per-Tenant

Deferred indefinitely.

---

## Dynamic Tenant Databases

Not part of current architecture.

---

# Soft Delete Strategy

Tenants must support soft deletes.

Reasons:

* auditability
* operational recovery
* billing support
* historical traceability

Tenant deletion must never immediately destroy organizational data.

---

# Metadata Strategy

The platform intentionally uses flexible metadata columns early.

Reasons:

* reduced schema explosion
* future configuration flexibility
* AI-native extensibility
* tenant customization support

---

# Future Expansion Areas

The database architecture is designed to eventually support:

* tenant feature flags
* tenant quotas
* tenant observability metadata
* tenant onboarding state
* tenant lifecycle tracking

without redesigning core organizational structure.

---

# Final Principle

The database architecture exists to support deterministic organizational isolation while preserving operational simplicity and long-term scalability.
