# ADR-005 — Pragmatic CQRS‑lite (Actions, Queries, DTOs)

## Status

Accepted

## Context

The architecture documents mention CQRS‑lite, but the team must avoid the complexity of full enterprise CQRS (separate read models, event store, eventual consistency) until performance or scalability demands it. Phase 1 needs a simple pattern that improves clarity and AI‑friendliness without overengineering.

## Decision

Use **pragmatic CQRS‑lite** with three constructs:
- **Actions** (commands) for write operations.
- **Queries** for read operations.
- **DTOs** for data transfer between modules and orchestration layers.

No separate read models, event store, or eventual consistency are implemented in Phase 1. Read models are served directly from the same database using Eloquent scopes with `tenant_id` filtering. Caching (Redis) is used for read‑heavy queries when needed.

## Consequences

- **Positive**: clear separation of reads and writes improves code organization and AI‑assisted development.
- **Positive**: avoids the operational complexity of eventual consistency and event sourcing.
- **Negative**: if read/write performance becomes a bottleneck, migrating to full CQRS will require significant refactoring.
- **Negative**: the pattern may feel unfamiliar to developers used to traditional MVC.

## Alternatives Considered

- **Full CQRS with event store**: rejected because it adds complexity (eventual consistency, outbox pattern, replay logic) without proven need.
- **Traditional MVC (Eloquent in controllers)**: rejected because it leads to bloated controllers and unclear boundaries.

## References

- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-004)
- docs/arquitecture/MODULE_MAP.md (Section 13)
- docs/arquitecture/AI_NATIVE_STRATEGY.md (Section 18)
