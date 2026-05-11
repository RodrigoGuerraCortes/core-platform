# ADR-004 — Module‑Oriented Architecture (Not Global Technical Layers)

## Status

Accepted

## Context

Many Laravel projects organize code by technical layers (Controllers/, Services/, Repositories/) at the root level, which leads to coupling and makes it hard to understand which code belongs to which domain. For a modular monolith with multiple domain applications, a module‑oriented structure improves maintainability and AI‑assisted development.

## Decision

Code is organized by **modules/domains**, not by global technical layers. Every piece of code belongs to a module (Core/ or Domain/). Modules follow explicit dependency rules: Domain → Core → Shared, and Domain → Domain is forbidden. Communication between modules uses events, DTOs, and contracts rather than direct coupling.

## Consequences

- **Positive**: clearer ownership, easier to navigate, better AI‑assisted code generation (the AI can see module boundaries).
- **Positive**: future extraction of a module into a separate service is straightforward because boundaries already exist.
- **Negative**: requires discipline to avoid creating a “Shared” dumping ground.
- **Negative**: developers must learn the module structure instead of a flat technical layer layout.

## Alternatives Considered

- **Global technical layers (Controllers/Services/Repositories)**: rejected because it hides domain boundaries and makes the monolith harder to maintain as it grows.
- **Package‑by‑feature within a single module**: accepted as a complement, but the top‑level organization remains module‑oriented.

## References

- docs/arquitecture/MODULE_MAP.md (Section 9.1, Section 10)
- docs/arquitecture/DOMAIN_BOUNDARIES.md (Section 3, Section 16)
- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-001)
