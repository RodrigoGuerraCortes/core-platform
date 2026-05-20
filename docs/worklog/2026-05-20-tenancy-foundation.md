We are now starting the TENANCY FOUNDATION IMPLEMENTATION phase for Core Platform.

IMPORTANT:
This repository already contains a fully defined tenancy architecture and documentation.
You MUST follow the existing architecture exactly.
Do NOT redesign tenancy.
Do NOT introduce alternative patterns.
Do NOT add hidden runtime state.
Do NOT implement advanced RBAC or onboarding features.

Your task is to implement ONLY the first foundational infrastructure layer.

━━━━━━━━━━━━━━━━━━
CURRENT ARCHITECTURE
━━━━━━━━━━━━━━━━━━

The platform uses:

* Laravel modular monolith
* shared database + tenant isolation
* global user identities
* request-scoped tenant context
* explicit tenant resolution
* X-Tenant-Id header strategy
* TenantContext singleton
* tenant_user membership pivot
* no persistent active tenant session
* platform context separated from tenant context

Users are GLOBAL identities.

NEVER:

* add tenant_id to users table
* store active tenant in session
* read tenant directly from request inside domain logic

Tenant context must ONLY be accessed through:

```php
app(TenantContext::class)
```

━━━━━━━━━━━━━━━━━━
IMPLEMENTATION GOAL
━━━━━━━━━━━━━━━━━━

Implement ONLY Phase 1 foundation infrastructure.

NO:

* RBAC
* onboarding
* billing
* self-service signup
* tenant dashboards
* advanced scopes
* tenant-aware UI
* subdomain routing

━━━━━━━━━━━━━━━━━━
TASKS TO IMPLEMENT
━━━━━━━━━━━━━━━━━━

1. Create Tenancy module structure

Create:

backend/app/Core/Tenancy/

Recommended structure:

Core/Tenancy/
├── Context/
├── Middleware/
├── Models/
├── Exceptions/
├── Contracts/
├── Support/
├── Providers/
├── Tests/
└── README.md

━━━━━━━━━━━━━━━━━━
2. Create Tenant model
━━━━━━━━━━━━━━━━━━

Create Tenant model with:

Fields:

* id
* name
* slug
* metadata
* settings
* timestamps
* soft deletes

Requirements:

* casts for metadata/settings arrays
* relationships to users via tenant_user
* slug unique

━━━━━━━━━━━━━━━━━━
3. Create tenant_user pivot migration
━━━━━━━━━━━━━━━━━━

Fields:

* tenant_id
* user_id
* membership_role
* timestamps

Membership roles:

* owner
* admin
* member

Requirements:

* proper indexes
* foreign keys if appropriate
* composite uniqueness

━━━━━━━━━━━━━━━━━━
4. Update User model
━━━━━━━━━━━━━━━━━━

Add ONLY:

* tenants() relationship

DO NOT:

* add currentTenant()
* add active tenant state
* add hidden helpers
* add authorization logic

━━━━━━━━━━━━━━━━━━
5. Create TenantContext
━━━━━━━━━━━━━━━━━━

Create request-scoped organizational runtime context object.

Responsibilities:

* hold resolved tenant
* expose tenant ID
* expose tenant entity

NO:

* request parsing
* header parsing
* auth logic
* business logic

━━━━━━━━━━━━━━━━━━
6. Create ResolveTenant middleware
━━━━━━━━━━━━━━━━━━

Responsibilities:

* read X-Tenant-Id
* validate tenant existence
* reject invalid tenants
* initialize TenantContext
* fail fast

Requirements:

* platform route bypass support
* deterministic behavior
* no business logic

DO NOT:

* validate permissions
* validate membership
* authorize actions

━━━━━━━━━━━━━━━━━━
7. Create ValidateTenantMembership middleware
━━━━━━━━━━━━━━━━━━

Responsibilities:

* validate authenticated user belongs to resolved tenant
* reject invalid memberships

Requirements:

* use TenantContext
* fail with 403

DO NOT:

* implement RBAC
* implement policies
* implement permissions

━━━━━━━━━━━━━━━━━━
8. Middleware registration
━━━━━━━━━━━━━━━━━━

Prepare middleware registration strategy for:

* tenant.api
* tenant.web
* platform.web

DO NOT aggressively refactor current routes.

━━━━━━━━━━━━━━━━━━
9. Tests
━━━━━━━━━━━━━━━━━━

Add foundational tests for:

* valid tenant resolution
* invalid tenant rejection
* missing tenant rejection
* membership validation
* platform bypass behavior
* TenantContext initialization

Tests are mandatory.

━━━━━━━━━━━━━━━━━━
10. IMPORTANT ARCHITECTURAL RESTRICTIONS
━━━━━━━━━━━━━━━━━━

FORBIDDEN:

* users.tenant_id
* persistent active tenant
* hidden session tenant state
* direct request tenant access in domain logic
* advanced global scopes
* automatic tenant switching
* subdomain routing
* schema-per-tenant
* database-per-tenant

━━━━━━━━━━━━━━━━━━
11. IMPLEMENTATION STYLE
━━━━━━━━━━━━━━━━━━

Prioritize:

* explicitness
* simplicity
* deterministic behavior
* infrastructure boundaries
* readability
* testability

Avoid:

* magic abstractions
* hidden helpers
* over-engineering
* premature optimization

━━━━━━━━━━━━━━━━━━
12. OUTPUT EXPECTATIONS
━━━━━━━━━━━━━━━━━━

At the end provide:

* files created
* migrations added
* middleware added
* tests added
* architectural decisions respected
* remaining TODOs for next phase

DO NOT implement anything outside the defined scope.
