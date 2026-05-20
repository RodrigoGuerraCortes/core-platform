# Testing Strategy

## Purpose

This document defines the official testing strategy for the Tenancy module.

The primary goal is to guarantee organizational isolation across the entire platform.

Tenant isolation failures are considered critical platform failures.

The testing strategy must prevent:

* cross-tenant data leaks
* unauthorized access
* invalid tenant resolution
* missing tenant context
* async tenant propagation failures
* cache contamination
* scope bypass regressions

---

# Core Principle

The Tenancy module is infrastructure.

Infrastructure must be deterministic and heavily tested.

The platform assumes:

```text id="qj3sm0"
Tenant isolation failures are security failures.
```

---

# Primary Testing Goals

The tenancy testing strategy exists to validate:

1. tenant isolation
2. deterministic tenant resolution
3. membership enforcement
4. request-scoped context
5. async propagation
6. platform bypass behavior
7. fail-fast behavior

---

# Testing Layers

The platform distinguishes between:

1. Unit tests
2. Feature tests
3. Integration tests
4. Isolation tests
5. Async propagation tests
6. Future architecture tests

---

# Unit Tests

## Purpose

Validate isolated infrastructure behavior.

Examples:

* TenantContext behavior
* tenant identifier parsing
* middleware helpers
* cache key generation
* tenant metadata helpers

---

## Example Targets

```text id="vqq1ah"
TenantContext
ResolveTenant middleware helpers
Tenant cache helpers
Tenant resolution validators
```

---

# Feature Tests

## Purpose

Validate HTTP runtime behavior.

Examples:

* tenant resolution
* membership validation
* platform bypass behavior
* invalid tenant rejection

---

## Example Scenarios

### Valid Tenant Resolution

```text id="2chj5w"
Authenticated user
+ valid tenant
+ valid membership
→ request succeeds
```

---

### Missing Tenant Header

```text id="u3xx97"
Missing X-Tenant-Id
→ request fails
```

---

### Invalid Tenant

```text id="a53kxm"
Tenant does not exist
→ request fails
```

---

### Missing Membership

```text id="v8mckn"
User belongs to Tenant A
Request targets Tenant B
→ reject request
```

---

# Integration Tests

## Purpose

Validate cooperation between:

* middleware
* TenantContext
* authentication
* authorization
* scopes
* cache
* queues

---

## Example Scenarios

### Tenant-Aware Query Scoping

```text id="b2vt5o"
TenantContext active
→ query automatically scoped
```

---

### Platform Route Bypass

```text id="px8i22"
/admin route
→ no tenant required
```

---

### Tenant Route Enforcement

```text id="mpcxl8"
/tenant/projects
→ tenant required
```

---

# Isolation Tests

## Purpose

Isolation tests are the MOST important category.

These tests validate that tenant data never leaks across organizations.

---

# Mandatory Isolation Rules

The platform must always guarantee:

```text id="mcrx1d"
Tenant A cannot access Tenant B data
```

under any circumstance.

---

## Example Isolation Scenarios

### Cross-Tenant Query Leak

```text id="rz7myl"
Tenant A user
→ queries projects
→ receives only Tenant A data
```

---

### Cross-Tenant Cache Leak

```text id="7zzw9d"
Tenant A cache entry
→ never visible to Tenant B
```

---

### Cross-Tenant Event Leak

```text id="93m45x"
Tenant A event
→ listeners preserve Tenant A context
```

---

### Cross-Tenant Queue Leak

```text id="djlwm7"
Tenant A dispatches job
→ worker restores Tenant A context
```

---

# Fail-Fast Tests

## Purpose

Validate immediate rejection behavior.

---

## Example Cases

### Missing Tenant

```text id="h4jvlz"
Missing tenant header
→ fail immediately
```

---

### Disabled Tenant

```text id="jlwm8m"
Disabled tenant
→ reject request
```

---

### Soft Deleted Tenant

```text id="w0vjlwm"
Soft deleted tenant
→ reject request
```

---

# Async Propagation Tests

## Purpose

Validate tenant context survival during async execution.

---

## Required Validation

Tenant context must survive:

* queued jobs
* retries
* notifications
* scheduled tasks
* event listeners

---

## Example Scenario

```text id="r8s4n5"
Tenant A request
→ dispatch job
→ queue worker restores Tenant A context
→ execute safely
```

---

# Cache Tests

## Purpose

Validate cache namespace isolation.

---

## Example Cases

### Tenant Namespace Isolation

```text id="jlwm9q"
tenant:A:settings
tenant:B:settings
```

must never collide.

---

### Global Cache Validation

```text id="dbjlwm"
global:feature-flags
```

must remain intentionally global.

---

# Middleware Tests

## Purpose

Validate middleware runtime behavior.

---

## Required Middleware Tests

### ResolveTenant

Validate:

* header parsing
* tenant resolution
* invalid tenant rejection
* TenantContext initialization

---

### ValidateTenantMembership

Validate:

* valid membership
* missing membership
* platform bypass rules

---

# Global Scope Tests

## Purpose

Validate automatic tenant filtering.

---

## Example Cases

### Scoped Query

```text id="jlwm1r"
TenantContext active
→ only tenant-owned rows returned
```

---

### Scope Bypass

Validate:

* bypass is explicit
* bypass is auditable
* bypass is restricted

---

# Platform Route Tests

## Purpose

Validate separation between:

```text id="jlwm2s"
platform context
```

and:

```text id="jlwm3t"
tenant context
```

---

## Example Cases

### Platform Route

```text id="jlwm4u"
/admin
→ tenant not required
```

---

### Tenant Route

```text id="jlwm5v"
/api/projects
→ tenant required
```

---

# Future Architecture Tests

The platform should eventually include:

* architecture tests
* static analysis rules
* forbidden dependency validation
* forbidden request access validation

---

# Forbidden Runtime Patterns

The testing strategy should eventually detect forbidden patterns such as:

---

## Direct Tenant Header Access

Forbidden:

```php id="jlwm6w"
$request->header('X-Tenant-Id')
```

inside domain logic.

---

## Direct Request Usage in Domain Layer

Forbidden:

```php id="jlwm7x"
request()
```

inside:

* actions
* services
* queries
* domain logic

---

## Missing TenantContext Usage

Tenant-aware services must depend on:

```php id="jlwm8y"
TenantContext
```

instead of infrastructure input.

---

# Test Factories

The platform should eventually provide:

* TenantFactory
* MembershipFactory
* Tenant-aware test helpers
* TenantContext testing utilities

---

# CI/CD Strategy

Tenancy isolation tests are mandatory CI gates.

No deployment should occur if:

* tenant isolation tests fail
* membership validation fails
* middleware tests fail
* async propagation fails

---

# Long-Term Goals

The testing architecture is designed to support:

* SaaS systems
* modular monoliths
* distributed systems
* async workers
* AI agents
* future microservices

without weakening organizational isolation.

---

# Final Principle

The platform must continuously prove:

```text id="jlwm9z"
organizational boundaries remain intact
```

across all runtime environments and execution paths.
