# ADR-010 — Human Approval Required for Critical Operations

## Status

Accepted

## Context

AI systems can propose changes, but allowing them to execute critical operations autonomously (architecture changes, migrations, destructive actions, production‑sensitive workflows) would introduce unacceptable risk for security, tenancy, and production stability.

## Decision

**Human approval is mandatory** for the following categories of operations:
- Architecture changes (modifying module boundaries, dependency rules, tenancy strategy).
- Database migrations (schema changes, destructive operations, tenant‑impacting changes).
- Production‑sensitive workflows (deployments, infrastructure changes, security‑sensitive actions).
- Any AI action that modifies data (requires a human to approve before execution).

AI may **propose** changes, but a human must explicitly approve them before they are executed. This rule applies to both engineering AI (coding assistants) and runtime AI (agents).

## Consequences

- **Positive**: protects security, tenancy, and production stability.
- **Positive**: keeps humans accountable for critical decisions.
- **Negative**: slows down fully automated workflows; some operations that could be safe may require manual approval.
- **Negative**: requires building approval workflows (UI, notifications, queues) for AI‑proposed actions.

## Alternatives Considered

- **Full autonomy for AI**: rejected because it would create unacceptable risk for a platform handling multi‑tenant data and production systems.
- **No approval for any AI action**: rejected because even simple AI actions (e.g., generating content) could have unintended consequences.

## References

- docs/arquitecture/AI_NATIVE_STRATEGY.md (Sections 9, 10, 17)
- docs/arquitecture/AGENT_ORCHESTRATION.md (Section 8)
- docs/arquitecture/ARCHITECTURE_DECISIONS.md (ADR-009)
