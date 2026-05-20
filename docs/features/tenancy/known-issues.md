# Known Issues

## Purpose

This document tracks known limitations, accepted tradeoffs, deferred risks, and future architectural concerns related to the Tenancy module.

The goal is to maintain explicit visibility into areas that are:

* intentionally incomplete
* operationally sensitive
* deferred by design
* future scalability concerns
* architectural tradeoffs

This document is NOT a bug tracker.

It exists to preserve architectural awareness over time.

---

# Current Status

The Tenancy module is currently in:

```text id="jlwm43"
architecture foundation phase
```

The platform has not yet implemented full runtime tenancy infrastructure.

Current documentation defines the intended architecture and operational model.

---

# Known Architectural Limitations

## No Runtime Tenant Enforcement Yet

Current state:

* no ResolveTenant middleware
* no TenantContext implementation
* no membership validation
* no tenant-aware global scopes

Impact:

organizational isolation is not yet enforced at runtime.

Status:

```text id="jlwm44"
expected during foundation phase
```

---

## No Tenant-Aware Domain Entities Yet

Current state:

domain entities are not yet tenant-owned.

Examples:

* projects
* cases
* uploads
* notifications

do not yet contain tenant isolation rules.

Impact:

tenant isolation has not yet propagated across business domains.

Status:

```text id="jlwm45"
expected during infrastructure-first rollout
```

---

## No Tenant-Aware Queue Propagation Yet

Current state:

queued jobs do not yet preserve tenant context.

Impact:

future async execution may leak organizational state if propagation is not implemented correctly.

Status:

```text id="jlwm46"
deferred until async propagation phase
```

---

## No Tenant-Aware Cache Isolation Yet

Current state:

cache namespaces are not tenant-scoped.

Impact:

future cache collisions may occur if tenant namespacing is introduced incorrectly.

Status:

```text id="jlwm47"
deferred until cache infrastructure phase
```

---

## No Tenant-Aware Observability Yet

Current state:

logs, traces, and telemetry do not yet contain tenant metadata.

Impact:

future debugging and operational tracing remain limited.

Status:

```text id="jlwm48"
expected during early foundation phase
```

---

# Accepted Tradeoffs

## Shared Database Strategy

Current official architecture:

```text id="’wini49"
shared database + tenant isolation
```

Tradeoff:

organizational isolation depends heavily on application correctness and testing discipline.

Reasons accepted:

* operational simplicity
* lower infrastructure complexity
* simpler migrations
* simpler observability
* simpler local development
* faster platform iteration

---

## Request-Scoped Tenant Context

The platform intentionally avoids persistent active tenant state.

Tradeoff:

clients must provide tenant context on every request.

Reasons accepted:

* deterministic behavior
* safer async propagation
* fewer session bugs
* cleaner observability
* explicit organizational context

---

## Global User Identities

Users are platform-global identities.

Tradeoff:

authentication and tenancy remain separate systems.

Reasons accepted:

* multi-organization support
* invitation flows
* platform administration
* future collaboration support

---

# Deferred Features

The following capabilities are intentionally postponed.

---

## Database-Per-Tenant

Status:

```text id="’wini50"
explicitly deferred
```

Reason:

operational complexity currently outweighs benefits.

---

## Schema-Per-Tenant

Status:

```text id="’wini51"
explicitly deferred
```

Reason:

migration and observability complexity.

---

## Persistent Tenant Sessions

Status:

```text id="’wini52"
forbidden by architecture
```

Reason:

hidden runtime state introduces operational instability.

---

## Self-Service Tenant Provisioning

Status:

```text id="’wini53"
deferred
```

Blocked by:

* notifications
* onboarding
* billing
* audit logging

---

## Tenant-Aware RBAC

Status:

```text id="’wini54"
deferred
```

Blocked by:

Authorization module not implemented yet.

---

# Operational Risks

## Cross-Tenant Leakage Risk

Current risk:

future developers may accidentally bypass tenant isolation.

Mitigation strategy:

* isolation tests
* global scopes
* architecture documentation
* static analysis
* middleware enforcement

---

## AI-Generated Code Risk

Current risk:

AI-assisted tooling may introduce:

* direct request access
* hidden tenant assumptions
* implicit organizational state

Mitigation strategy:

Tenancy documentation acts as canonical architectural guidance.

---

## Scope Bypass Risk

Future risk:

developers may use:

```php id="’wini55"
withoutGlobalScopes()
```

incorrectly.

Mitigation strategy:

* explicit bypass review
* auditability
* restricted operational tooling

---

## Queue Worker State Leakage

Future risk:

workers processing multiple tenants may leak TenantContext accidentally.

Mitigation strategy:

mandatory TenantContext restoration and cleanup.

---

# Future Scalability Concerns

## Observability Growth

As the platform grows:

* logs
* traces
* metrics
* audit events

must become tenant-aware.

Failure to enrich telemetry with tenant metadata may create operational blind spots.

---

## Async Complexity Growth

As async infrastructure expands:

* queues
* workflows
* AI agents
* event buses

tenant propagation complexity increases significantly.

The platform must maintain explicit tenant context consistently.

---

## Platform vs Tenant UX Separation

Future risk:

mixing:

* operational tooling
* tenant administration
* end-user dashboards

inside the same UI surface.

Recommendation:

maintain explicit platform vs tenant boundaries.

---

# Documentation Risks

## Prompt Drift

AI-assisted development may slowly diverge from official tenancy architecture.

Mitigation:

these documents act as canonical architecture references.

---

## Incomplete Runtime Enforcement

Documentation currently exceeds implementation maturity.

This is intentional during the architecture foundation phase.

---

# Future Review Points

The following decisions should be revisited after platform maturity:

* subdomain resolution
* path-based resolution
* tenant-aware notifications
* advanced observability
* distributed tracing
* support impersonation
* service extraction

---

# Final Note

The Tenancy module intentionally prioritizes:

```text id="’wini56"
correct organizational isolation
```

over convenience, speed, or premature optimization.

Many deferred features are postponed intentionally to preserve long-term architectural stability.
