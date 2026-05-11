# ADR-006 — Events for Async Boundaries

## Status

Accepted

## Context

The architecture promotes event‑driven communication, but overusing events for every module interaction can lead to event explosion, debugging difficulty, and unnecessary async complexity. The team needs clear guidance on when to use events and when to keep synchronous calls.

## Decision

Events are used primarily for **async boundaries**:
- notifications (email, push, in‑app)
- AI execution (triggering a queued AI job)
- uploads (processing a file after it is stored)
- integrations (webhooks, third‑party sync)
- long‑running workflows that should not block the request

**Direct synchronous calls inside the monolith are acceptable and encouraged when simpler.** Not every module interaction needs an event. Use events only when decoupling, retry capability, or parallel execution is required.

## Consequences

- **Positive**: reduces event explosion and unnecessary async complexity.
- **Positive**: keeps the codebase easier to trace and debug.
- **Negative**: synchronous calls create tighter coupling; if a module later needs to be extracted, the synchronous call must be replaced with an event or API call.
- **Negative**: developers must decide case‑by‑case whether to use an event or a direct call.

## Alternatives Considered

- **Event‑driven for all module communication**: rejected because it would create excessive indirection and make simple flows hard to follow.
- **Synchronous only**: rejected because it would block the request for slow operations (AI calls, file processing).

## References

- docs/arquitecture/EVENT_MODEL.md (Events for Async Boundaries)
- docs/arquitecture/MODULE_MAP.md (Section 11)
- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-005)
