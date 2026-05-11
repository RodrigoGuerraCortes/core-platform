# ADR-008 — Prompt Infrastructure: Lightweight First

## Status

Accepted

## Context

The architecture treats prompts as first‑class infrastructure, but building a full prompt registry with versioning, analytics, A/B testing, and a marketplace in Phase 1 would consume engineering time without proven value.

## Decision

Prompt infrastructure starts **lightweight** in Phase 1:
- Prompts are stored as filesystem files (e.g., `resources/prompts/`) or in a simple DB table.
- Metadata is kept minimal (name, version, category, active flag).
- Versioning is manual (file naming or a `version` column).
- Review workflows are manual (human marks a prompt as active).
- No automated A/B testing, analytics, or marketplace.

Advanced features (prompt registry with API endpoints, adaptive prompts, analytics, A/B testing, marketplace) are explicitly **future roadmap** and will be built only after product validation.

## Consequences

- **Positive**: fast to implement, easy to change, low operational overhead.
- **Positive**: avoids building infrastructure that may never be used.
- **Negative**: manual versioning and review may become cumbersome as the number of prompts grows.
- **Negative**: migrating to a full registry later will require data migration and code changes.

## Alternatives Considered

- **Full centralized prompt registry from day one**: rejected because it would delay shipping product features.
- **No prompt infrastructure (ad‑hoc prompts in code)**: rejected because it would lead to prompt drift and inconsistency.

## References

- docs/arquitecture/PROMPT_INFRASTRUCTURE.md (Sections 23, 24)
- docs/arquitecture/AI_NATIVE_STRATEGY.md (Sections 11, 12, 13)
- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-007)
