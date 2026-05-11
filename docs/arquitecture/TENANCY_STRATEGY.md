# Core Platform — Tenancy Strategy

## Purpose

Define the official multi-tenant architecture strategy for Core Platform.

---

# Official Strategy

The platform officially adopts:

# Shared Database + tenant_id

This is the default and mandatory strategy for all domain applications unless explicitly overridden in the future.

---

# Tenant Model

A tenant represents:

- an organization
- a company
- a customer workspace
- an isolated operational environment

All business data must belong to a tenant unless explicitly marked as global.

---

# Platform Roles

```txt
Platform Admin
Tenant Admin
Tenant User
```

## Platform Admin

Can manage:
- tenants
- global settings
- observability
- platform operations

## Tenant Admin

Can manage:
- tenant users
- tenant settings
- tenant domain resources

## Tenant User

Restricted to tenant-scoped operations.

---

# Tenant Isolation

Isolation must exist at:

- query level
- authorization level
- policy level
- upload level
- event level
- audit level
- AI context level

Cross-tenant access is forbidden by default.

---

# Tenant Resolution

Tenant resolution may occur through:

- session
- auth token
- subdomain
- request header
- API context

The authenticated tenant context must always be available during request execution.

---

# Tenant-Aware Resources

The following resources must be tenant-aware:

- users
- uploads
- prompts
- AI context
- notifications
- reports
- jobs
- dashboards
- settings
- audit logs

---

# Global Resources

Some resources may remain global:

- platform templates
- AI provider configs
- observability configs
- feature flags
- platform roles

Global resources must be explicitly declared.

---

# Upload Isolation

Uploads must support tenant segmentation.

Example:

```txt
tenants/{tenant_id}/uploads/
```

---

# AI Isolation

AI executions must include tenant context.

Examples:

- tenant prompts
- tenant memory
- tenant AI templates
- tenant orchestration

AI data leakage across tenants is forbidden.

---

# Event Isolation

All internal events should carry tenant context.

Example:

```txt
TenantContext
tenant_id
actor_id
```

---

# Reporting Strategy

Reporting infrastructure must support:

- tenant-scoped reporting
- platform-level reporting
- operational analytics

---

# Future Evolution

The architecture must remain prepared for:

- tenant database extraction
- dedicated infrastructure
- premium tenant isolation
- distributed tenancy

without requiring full rewrites.