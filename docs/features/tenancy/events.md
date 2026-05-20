# Events

## Purpose

This document defines the official domain and infrastructure events for the Tenancy module.

The goal is to standardize tenant-aware runtime events across the platform.

These events support:

* observability
* auditability
* async processing
* future integrations
* workflow orchestration
* AI-native infrastructure

---

# Core Principle

Tenancy events are infrastructure events.

They communicate organizational runtime state transitions without coupling business domains to tenant implementation details.

---

# Event Categories

The platform distinguishes between:

1. Tenant lifecycle events
2. Runtime context events
3. Membership events
4. Operational events
5. Future async events

---

# Tenant Lifecycle Events

## TenantCreated

Dispatched when a tenant is created.

Example payload:

```json
{
  "tenant_id": "tenant_123",
  "tenant_slug": "nativeit"
}
```

---

## TenantUpdated

Dispatched when tenant metadata or settings change.

---

## TenantDeleted

Dispatched when a tenant is soft deleted.

---

## TenantRestored

Dispatched when a soft-deleted tenant is restored.

---

## TenantDisabled

Dispatched when tenant access becomes operationally blocked.

---

## TenantEnabled

Dispatched when tenant access is restored.

---

# Runtime Context Events

## TenantResolved

Dispatched when tenant resolution succeeds.

Payload examples:

```json
{
  "tenant_id": "tenant_123",
  "tenant_slug": "nativeit"
}
```

---

## TenantContextInitialized

Dispatched after TenantContext becomes available.

---

## TenantResolutionFailed

Dispatched when tenant resolution fails.

Examples:

* missing tenant
* invalid tenant
* malformed tenant identifier

---

# Membership Events

## TenantMembershipCreated

Dispatched when a user joins a tenant.

---

## TenantMembershipRemoved

Dispatched when membership is revoked.

---

## TenantMembershipRoleUpdated

Dispatched when membership role changes.

Examples:

* member → admin
* admin → owner

---

## TenantOwnerTransferred

Dispatched when tenant ownership changes.

---

# Operational Events

## TenantIsolationViolationDetected

Reserved for future operational tooling.

Used for:

* suspicious cross-tenant access
* isolation anomalies
* security monitoring

---

## TenantContextPropagationFailed

Reserved for async infrastructure failures.

Examples:

* queue propagation failure
* missing tenant context during async execution

---

# Event Metadata Rules

All tenant-aware events should eventually include:

* tenant_id
* tenant_slug
* user_id (when available)
* request_id (future)
* trace_id (future)

---

# Async Propagation Rules

Events dispatched inside tenant-aware execution must preserve tenant context explicitly.

Listeners must never assume tenant context exists automatically.

---

# Observability Goals

Tenancy events should eventually enrich:

* logs
* traces
* audit payloads
* metrics
* distributed telemetry

---

# Deferred Event Categories

The following event categories are intentionally postponed:

* billing events
* onboarding events
* tenant analytics events
* tenant quota events
* tenant subscription events

until supporting modules exist.

---

# Architectural Restrictions

## Events Must Remain Infrastructure-Oriented

Tenancy events must never contain:

* business workflows
* domain logic
* UI decisions
* authorization policies

---

## Events Must Remain Explicit

Tenant context must never be inferred implicitly inside listeners.

---

# Long-Term Goals

The event architecture is designed to support:

* modular monoliths
* async workflows
* AI agents
* future event buses
* distributed services
* future package extraction

without changing tenant runtime behavior.

---

# Final Principle

Tenancy events exist to communicate organizational runtime transitions safely, explicitly, and asynchronously across the platform.
