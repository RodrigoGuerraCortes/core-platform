# Tenancy Module

## Purpose

The Tenancy module provides organizational isolation infrastructure for the Core Platform.

Tenancy is **infrastructure**, not business logic.

## Core Principles

- Users are **global identities** — never add `tenant_id` to the `users` table
- Tenant context is **request-scoped** — no persistent active tenant session
- `TenantContext` is the **only** runtime tenant provider in domain logic
- Authentication = WHO, Tenancy = WHERE, Authorization = WHAT

## Structure

```
Core/Tenancy/
├── Context/              # TenantContext — request-scoped runtime state
├── Middleware/           # ResolveTenant, ValidateTenantMembership
├── Models/               # Tenant model
├── Exceptions/           # TenantNotResolvedException, etc.
├── Providers/            # TenancyServiceProvider
└── README.md
```

## Resolution Strategy (Phase 1)

Tenant is resolved from the `X-Tenant-Id` request header.

```http
GET /projects
Authorization: Bearer xxx
X-Tenant-Id: 1
```

## Middleware Aliases

- `tenant.resolve` → `ResolveTenant` — resolves and validates tenant from header
- `tenant.member` → `ValidateTenantMembership` — ensures user belongs to tenant

## Usage

Access tenant context inside controllers/actions:

```php
$context = app(TenantContext::class);
$tenant  = $context->tenant();
$id      = $context->tenantId();
```

## Block 2 TODOs

- Tenant-aware global scopes for owned entities
- Queue tenant propagation
- Cache key isolation helpers
- Subdomain/path resolution strategies
- Role-based access within tenant (RBAC)
