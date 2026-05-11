# Core Platform — Authorization Model

## Purpose

Define the official authorization strategy for Core Platform.

---

# Authorization Philosophy

Authorization is a first-class architectural concern.

The platform must guarantee:

- tenant isolation
- permission consistency
- explicit access rules
- policy-driven security

---

# Official Authorization Stack

```txt
Laravel Policies
Roles
Permissions
Tenant Context
```

---

# Authorization Layers

## Platform Authorization

Controls:
- tenant management
- platform operations
- observability
- global administration

## Domain Authorization

Controls:
- business resources
- workflows
- domain actions

---

# Role Model

```txt
Platform Admin
Tenant Admin
Tenant User
```

Domains may define additional tenant-scoped roles.

---

# Permissions

Permissions should remain explicit.

Examples:

```txt
manage_users
manage_products
manage_matches
view_audit_logs
execute_ai_prompts
```

---

# Policy Strategy

Policies are mandatory.

Every business resource should have explicit policies.

Example:

```txt
ProductPolicy
MatchPolicy
TenantPolicy
```

---

# Tenant-Aware Authorization

Every authorization decision must include tenant context.

Cross-tenant authorization is forbidden by default.

---

# DTO-Based Security

DTOs must carry:

- tenant_id
- actor_id
- role context

when relevant.

---

# Event Authorization

Events must never bypass authorization rules.

Async processing must preserve security context.

---

# AI Authorization

AI operations must support:

- permission validation
- AI execution scopes
- prompt access control
- tenant isolation

---

# Security Goals

The authorization model exists to maximize:

- security
- tenant isolation
- consistency
- maintainability
- auditability