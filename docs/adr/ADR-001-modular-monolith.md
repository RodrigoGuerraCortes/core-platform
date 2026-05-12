# ADR-001 — Modular Monolith Architecture

## Status

Accepted

## Context

Core Platform must support multiple domain applications (Marketplace, Parcelas, MYLTracker, etc.) while keeping operational complexity low for a small team. Premature microservices would increase deployment, debugging, and testing overhead without proven scaling needs.

## Decision

Core Platform adopts a **Modular Monolith** architecture. The system is deployed as a single application, but code is organized into isolated internal modules with explicit boundaries, contracts, and dependency rules. Future extraction of heavy workers, AI runtimes, or orchestration engines into separate services is supported by the module boundaries but will only be performed when justified by concrete scaling or deployment requirements.

## Consequences

- **Positive**: simpler operational model, faster development, easier debugging, lower infrastructure cost, better AI-assisted code generation.
- **Positive**: clear module boundaries enable future extraction without a full rewrite.
- **Negative**: requires discipline to maintain module isolation; a single deployment means any module failure can affect the whole system.
- **Negative**: scaling a single monolith may eventually require extraction, but this is deferred until needed.

## Alternatives Considered

- **Microservices from day one**: rejected because it would multiply infrastructure, deployment, and debugging complexity for a two‑person team.
- **Traditional layered monolith (Controllers/Services/Repositories)**: rejected because it lacks module boundaries and makes AI‑assisted development harder.

## References

- docs/arquitecture/VISION.md (Section 4.1)
- docs/arquitecture/TECH_STACK_DECISION.md (Section 2.1)
- docs/arquitecture/MODULE_MAP.md (Section 2)
- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-001)
