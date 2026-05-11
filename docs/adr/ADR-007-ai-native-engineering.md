# ADR-007 — AI‑Native Engineering

## Status

Accepted

## Context

Core Platform aims to be AI‑native, meaning AI assists development, architecture analysis, and code generation. However, the team must avoid premature autonomy where AI makes decisions without human oversight.

## Decision

Core Platform adopts **AI‑native engineering** as a collaboration model:
- **Humans** remain responsible for architecture decisions, domain modeling, security validation, tenant isolation, final approvals, and production decisions.
- **AI** assists implementation, analysis, code review, documentation, and scaffolding.
- The platform maintains explicit documentation (ADRs, architecture docs, prompt registries) to keep AI aligned with architectural standards.

AI systems may **not** autonomously modify architecture, bypass authorization, alter tenancy rules, or deploy to production without human approval.

## Consequences

- **Positive**: accelerates development while keeping humans in control.
- **Positive**: documentation‑first approach improves AI alignment and reduces tribal knowledge.
- **Negative**: requires investment in documentation and prompt infrastructure.
- **Negative**: risk of over‑reliance on AI if humans skip critical reviews.

## Alternatives Considered

- **Fully autonomous AI development**: rejected because it would introduce unacceptable risks for security, tenancy, and production stability.
- **No AI integration**: rejected because the platform explicitly aims to leverage AI for engineering speed.

## References

- docs/arquitecture/AI_NATIVE_STRATEGY.md (Sections 2, 3, 8, 9, 10, 17)
- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-006)
