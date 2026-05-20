# Architectural Warnings

## Purpose

This document defines the architectural anti-patterns, forbidden implementations, and long-term risks related to the Tenancy module.

The goal is to protect organizational isolation across the entire Core Platform lifecycle.

These warnings are mandatory architectural constraints.

Violating these principles may introduce:

* cross-tenant leaks
* hidden runtime state
* security vulnerabilities
* operational instability
* long-term coupling
* distributed architecture failures

---

# Core Principle

Tenancy is infrastructure.

It must remain:

* explicit
* deterministic
* request-scoped
* isolated from business logic

The platform must always know exactly which organization owns the current runtime context.

---

# Critical Warning

## Tenant Isolation Failures Are Security Failures

Cross-tenant data exposure must always be treated as:

```text id="jlwm26"
critical security incidents
```

not normal application bugs.

---

# Forbidden Patterns

## Never Add tenant_id to Users

Forbidden:

```text id="jlwm27"
users.tenant_id
```

Users are global identities.

Correct architecture:

```text id="jlwm28"
users
↕
tenant_user
↕
tenants
```

Reasons:

* multi-organization support
* centralized authentication
* support tooling
* invitation flows
* future collaboration
* platform administration

---

## Never Read Tenant Directly From Request in Domain Logic

Forbidden:

```php id="jlwm29"
$request->header('X-Tenant-Id')
```

inside:

* controllers
* actions
* services
* repositories
* policies
* domain logic

Correct approach:

```php id="’wini30"
app(TenantContext::class)
```

---

## Never Depend on Route Parsing in Business Logic

Forbidden:

```php id="’wini31"
request()->route()
```

for tenant resolution.

Tenant resolution belongs to infrastructure middleware.

---

## Never Implement Persistent Active Tenant State

Forbidden:

* session-based active tenant
* cached active organization
* hidden tenant switching
* implicit organizational context

The platform intentionally uses:

```text id="’wini32"
request-scoped explicit tenant context
```

Reasons:

* deterministic behavior
* reduced session bugs
* safer multi-tab execution
* simpler async propagation
* cleaner observability

---

## Never Couple Authentication to Tenancy

Authentication identifies:

```text id="’wini33"
WHO
```

Tenancy identifies:

```text id="’wini34"
WHERE
```

Authorization defines:

```text id="’wini35"
WHAT
```

These boundaries must remain separate.

---

## Never Store Hidden Tenant State Inside Tokens

Forbidden:

* hidden active tenant in Sanctum tokens
* implicit tenant switching via authentication
* tenant mutation during login

Tenant context must remain explicit per request.

---

## Never Allow Silent Scope Bypass

Forbidden:

```php id="’wini36"
withoutGlobalScopes()
```

without:

* explicit operational justification
* auditability
* restricted access

Tenant isolation bypass must always be visible and intentional.

---

## Never Make Platform Admins Global Data Superusers Automatically

Platform admins may:

* manage platform infrastructure
* manage tenants
* perform operational support

But they must NOT automatically bypass tenant isolation for tenant-owned data.

Tenant context must remain explicit.

---

## Never Introduce Database-Per-Tenant Prematurely

Official architecture:

```text id="’wini37"
shared database + tenant isolation
```

Database-per-tenant introduces:

* migration complexity
* observability complexity
* deployment complexity
* operational fragmentation
* async coordination complexity

The platform intentionally postpones this strategy.

---

## Never Introduce Schema-Per-Tenant Prematurely

Schema-per-tenant significantly complicates:

* migrations
* tooling
* analytics
* observability
* testing
* local development

This strategy is intentionally deferred indefinitely.

---

## Never Mix Platform Context With Tenant Context

Platform routes:

```text id="’wini38"
/admin
/platform/*
```

must remain operational.

Tenant routes must remain organizational.

These concerns must stay separated.

---

# Coupling Risks

## Auth ↔ Tenancy Coupling

Risk:

adding tenant behavior directly into authentication flows.

Examples:

* tenant-aware login mutation
* hidden tenant session state
* implicit tenant switching

Correct approach:

* authentication remains identity-focused
* tenancy remains request-scoped

---

## Filament ↔ Tenancy Coupling

Risk:

turning Filament into:

* platform admin
* tenant admin
* operational tooling
* customer dashboard

all simultaneously.

Recommendation:

```text id="’wini39"
/admin
```

should remain platform-oriented first.

Tenant-facing panels should eventually be separated intentionally.

---

## Queue ↔ Tenancy Coupling

Risk:

jobs assuming tenant context exists automatically.

Correct approach:

* serialize tenant context explicitly
* restore tenant context explicitly

---

## Cache ↔ Tenancy Coupling

Risk:

missing tenant namespaces.

Example failure:

```text id="’wini40"
Tenant A cache visible to Tenant B
```

All tenant-owned cache entries must include tenant namespace.

---

## Observability ↔ Tenancy Coupling

Risk:

logs and traces missing tenant metadata.

This creates:

* debugging blind spots
* audit difficulties
* operational ambiguity

Tenant metadata should eventually enrich telemetry consistently.

---

# Async Risks

## Queue Worker Leakage

Workers processing multiple tenants sequentially may leak organizational state if TenantContext is not reset correctly.

TenantContext restoration and cleanup are mandatory.

---

## Event Propagation Leakage

Events must preserve tenant metadata explicitly.

Listeners must never assume tenant context exists automatically.

---

## Scheduled Task Leakage

Scheduled tasks operating globally must never accidentally execute tenant-owned operations without explicit context.

---

# AI-Native Risks

## Hidden Runtime State

AI-assisted development tools frequently introduce:

* hidden request access
* direct header parsing
* implicit runtime assumptions

These patterns are forbidden.

---

## Unauthorized Helper Abstractions

Developers may accidentally create helpers such as:

```php id="’wini41"
currentTenant()
```

that bypass TenantContext boundaries incorrectly.

All tenant access must remain infrastructure-driven.

---

## Prompt Drift

As the platform evolves:

* prompts
* scaffolding
* AI code generation

may diverge from architectural standards.

The Tenancy documentation acts as the canonical architectural reference.

---

# Testing Warnings

## Isolation Tests Are Mandatory

A platform without cross-tenant isolation tests is considered unsafe.

---

## Async Tests Are Mandatory

Queue propagation must be tested continuously.

---

## Middleware Tests Are Mandatory

Tenant resolution failures must be deterministic and validated automatically.

---

# Operational Warnings

## Tenant Deletion Must Never Be Immediate

Tenant deletion must remain recoverable.

Soft deletes are mandatory.

---

## Tenant Context Must Be Observable

Operational tooling must eventually expose:

* tenant identifiers
* tenant metadata
* tenant traces
* tenant logs

for debugging and support purposes.

---

# Long-Term Warnings

## Premature Microservice Extraction

Do not extract Tenancy into a separate service prematurely.

The module should first mature inside the modular monolith.

---

## Premature RBAC Complexity

Do not implement advanced tenant-aware RBAC until the Authorization module exists formally.

---

## Premature Self-Service Provisioning

Do not implement public tenant provisioning until:

* notifications
* onboarding
* billing
* audit logging

become mature.

---

# Final Principle

Tenancy exists to guarantee organizational isolation.

Every architectural decision must prioritize:

```text id="’wini42"
deterministic organizational boundaries
```

over convenience, shortcuts, or premature optimization.
