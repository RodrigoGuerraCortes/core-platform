# ADR-002 — Laravel as Primary Business Platform

## Status

Accepted

## Context

Core Platform needs a backend framework that balances rapid development, mature ecosystem, and long‑term maintainability. The team is small (two developers) and must ship products quickly without sacrificing quality.

## Decision

**Laravel** is the primary business application framework for all domain applications, administration systems, APIs, dashboards, AI integrations, and orchestration layers. Go is recognized as a **future specialized infrastructure technology** for heavy workers, ETL pipelines, high‑concurrency systems, or distributed runtimes, but it will not be used for business logic in Phase 1.

## Consequences

- **Positive**: high development speed, built‑in authentication, authorization, queues, testing, and AI‑assisted development friendliness.
- **Positive**: large ecosystem reduces the need to build common infrastructure from scratch.
- **Negative**: Laravel is PHP‑based, which may limit performance for extremely high‑throughput workloads; those workloads will be extracted to Go later if needed.
- **Negative**: the team must stay current with Laravel releases and PHP versions.

## Alternatives Considered

- **Go as primary framework**: rejected because Go’s ecosystem for rapid CRUD, authentication, and admin panels is less mature, slowing initial delivery.
- **Python (Django/FastAPI)**: rejected because the team has stronger Laravel expertise and the existing codebase is Laravel.

## References

- docs/arquitecture/TECH_STACK_DECISION.md (Section 3.1, Section 12)
- docs/arquitecture/VISION.md (Section 12)
- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-003)
