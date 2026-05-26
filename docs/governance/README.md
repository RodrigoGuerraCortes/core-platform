# Platform Governance

> THE FIRST DOCUMENT AI AGENTS AND NEW DEVELOPERS READ.
> Last updated: 2026-05-25

---

## What Is This?

This directory contains the **operational governance** of Core Platform.
Not theory. Not aspirations. Rules that are actively enforced.

If you are an AI agent working on this codebase: **read this entire directory before making changes.**

---

## Governance Documents

### Runtime & Data

| Document | Purpose |
|----------|---------|
| [Runtime Modes](./runtime-modes.md) | The 5 official runtime modes and what each allows |
| [Data Source Governance](./data-source-governance.md) | What data sources are legal per module and mode |
| [MSW Browser Worker](./frontend/msw-browser-worker.md) | Runtime vs test-only MSW rules |
| [Runtime Audit (GOV-2)](./runtime-audit-gov2.md) | Complete audit of current runtime state |

### Ownership & Boundaries

| Document | Purpose |
|----------|---------|
| [Ownership Matrix](./ownership-matrix.md) | Who owns what — data, APIs, navigation, state |
| [CondoFlow Runtime Rules](./condoflow-runtime-rules.md) | CondoFlow-specific runtime governance |

### Frontend Governance

| Document | Location |
|----------|----------|
| [Component Standards](./frontend/) | UI component rules, slot conventions, imports |
| [MSW Browser Worker](./frontend/msw-browser-worker.md) | Service worker governance |
| [Experience Isolation](./frontend/experience-isolation.md) | How vertical experiences remain isolated |

### Architecture (in `docs/architecture/`)

| Document | Purpose |
|----------|---------|
| [Vertical Lifecycle](../architecture/vertical-lifecycle.md) | The 7 stages a module progresses through |
| [Domain Extraction Strategy](../architecture/domain-extraction-strategy.md) | When and how to extract domains |
| [API Conventions](../arquitecture/API_CONVENTIONS.md) | API design rules |

---

## Quick Reference: Current Module Stages

| Module | Stage | Runtime Mode |
|--------|-------|:------------:|
| Reference Cookbook | 1 — Reference Pattern | `cookbook` |
| CondoFlow | 4+5 — Backend-Backed + Isolated Experience | `vertical-runtime` |
| Dynamic Forms | 4 — Backend-Backed Vertical | `vertical-runtime` |
| MiniHIS | Planned — will start at Stage 3 | `vertical-runtime` |

---

## Cardinal Rules

These rules are NEVER violated:

1. **Business verticals MUST NOT use runtime MSW** after backend exists
2. **Verticals NEVER import from other verticals** — only from `@/shared/` and Core
3. **All business data is tenant-scoped** — no exceptions
4. **Core Platform NEVER depends on verticals** — dependency flows downward only
5. **Migrations live in domain folders** — never in root `migrations/`
6. **Frontend imports UI from `@/shared/ui` barrel** — never from internal paths
7. **No premature extraction** — monolith until a trigger fires
8. **Every vertical owns its navigation, routes, and branding** — Core owns the shell

---

## For AI Agents

When working on this codebase:

1. **Check ownership** — ensure changes go in the correct module
2. **Check runtime mode** — ensure data sources match the module's mode
3. **Check lifecycle stage** — ensure patterns match the module's maturity
4. **Check dependencies** — ensure imports flow downward (Core → Vertical, never cross)
5. **Run tests** — backend (`pest`) and frontend (`vitest`) must pass
6. **Run lint** — `eslint` must show zero errors

---

## Governance Gaps (Known)

| Gap | Priority | Notes |
|-----|----------|-------|
| CI enforcement of cross-vertical imports | High | Need ESLint rule |
| CI enforcement of MSW runtime registration | Medium | Lint for `setupWorker` imports in verticals |
| API versioning strategy | Low | Not needed until external consumers exist |
| Performance budgets per vertical | Low | Not needed at current scale |
| Security governance (OWASP, input validation) | Medium | Needed before production |
