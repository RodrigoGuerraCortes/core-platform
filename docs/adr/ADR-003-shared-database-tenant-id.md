# ADR-003 — Shared Database + tenant_id for Multi‑Tenancy

## Status

Accepted

## Context

Core Platform must support multiple tenants (organizations, companies, customer workspaces) from the start. The team needs a tenancy strategy that is operationally simple, low‑cost, and easy to migrate away from later if required.

## Decision

Multi‑tenancy starts with a **shared database + tenant_id** strategy. Every business table includes a `tenant_id` column, and all queries, policies, audits, uploads, events, and AI contexts enforce tenant isolation. Cross‑tenant access is forbidden by default. The architecture remains prepared for future extraction to dedicated databases or infrastructure when compliance or scaling demands it.

## Consequences

- **Positive**: operational simplicity, lower infrastructure cost, easier migrations, faster onboarding of new tenants.
- **Positive**: single database makes reporting and debugging straightforward.
- **Negative**: a missing `WHERE tenant_id = ?` can leak data across tenants; rigorous testing and policy enforcement are mandatory.
- **Negative**: future extraction to separate databases will require careful data migration and application changes.

## Alternatives Considered

- **Database‑per‑tenant**: rejected because it multiplies infrastructure cost and operational complexity for Phase 1.
- **Schema‑per‑tenant**: rejected because it complicates migrations and shared tooling.

## References

- docs/arquitecture/TENANCY_STRATEGY.md (Official Strategy, Tenant Isolation)
- docs/arquitecture/AUTHORIZATION_MODEL.md (Tenant‑Aware Authorization)
- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-002)
