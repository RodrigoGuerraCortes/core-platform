# ADR-009 — Agent Orchestration: Roadmap‑First, Workflow‑Oriented

## Status

Accepted

## Context

The architecture documents describe agents, orchestration, and multi‑agent systems. Building autonomous agents, swarm systems, and distributed orchestration in Phase 1 would introduce high complexity and operational risk without proven product need.

## Decision

Agent orchestration is **roadmap‑first** and **workflow‑oriented** in Phase 1:
- Phase 1 uses **simple AI services** (single provider, single model) invoked via queued jobs.
- Retries follow standard queue exponential backoff with a maximum limit (e.g., 3 attempts).
- Every AI execution is audited (prompt, model, tokens, result, actor).
- Human approval is required for any AI action that modifies data.
- Agents behave like **workflow services** (deterministic sequence of steps), not autonomous entities.

The following are explicitly **future roadmap** and will not be implemented in Phase 1:
- autonomous agents, self‑planning systems, distributed orchestration, advanced memory systems, orchestration graphs, complex agent chaining, swarm systems, autonomous retries, autonomous architecture changes.

## Consequences

- **Positive**: keeps Phase 1 simple, predictable, and easy to operate with a two‑person team.
- **Positive**: avoids infinite loops, runaway costs, and debugging nightmares.
- **Negative**: limits the AI capabilities available to domain applications until later phases.
- **Negative**: the team must resist the temptation to add autonomous features prematurely.

## Alternatives Considered

- **Full agent orchestration with autonomous agents**: rejected because it would create unacceptable operational risk and complexity for Phase 1.
- **No AI orchestration at all**: rejected because the platform is AI‑native and needs basic AI execution from the start.

## References

- docs/arquitecture/AGENT_ORCHESTRATION.md (Sections 26, 27, 28)
- docs/arquitecture/AI_NATIVE_STRATEGY.md (Section 22)
- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-008)
