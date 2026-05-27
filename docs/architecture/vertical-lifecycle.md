# Vertical Lifecycle

> Authoritative reference. Defines the maturity stages of a platform vertical.
> Last updated: 2026-05-25

---

## Purpose

Every vertical module in the platform progresses through a defined lifecycle.
Each stage has explicit rules about what is allowed, what is expected, and what governance applies.

There is no skipping stages. There is no ambiguity about what stage a module is in.

---

## Stage 1: Reference Pattern

**Definition:** A frontend-only example demonstrating canonical UI patterns.

| Dimension | Rule |
|-----------|------|
| Backend | None |
| Database | None |
| Data source | MSW runtime mocks |
| Auth | Mocked |
| Tenant scope | None |
| Observability | None |
| Tests | Frontend unit tests only |
| Extractable | No — it's a teaching tool |

**Example:** Reference Cookbook module

**Exit criteria to Stage 2:**
- Decision to explore a business domain
- Stakeholder approval for prototyping

---

## Stage 2: Sandbox Module

**Definition:** An experimental module exploring domain concepts with mock data.

| Dimension | Rule |
|-----------|------|
| Backend | None or minimal stub |
| Database | None |
| Data source | MSW or local state |
| Auth | Mocked |
| Tenant scope | Simulated |
| Observability | None |
| Tests | Frontend tests with mock server |
| Extractable | No — still exploratory |

**Exit criteria to Stage 3:**
- Domain model stabilized
- Business validation from stakeholders
- Decision to invest in real backend

---

## Stage 3: Prototype Vertical

**Definition:** Module with real Laravel backend but incomplete coverage.

| Dimension | Rule |
|-----------|------|
| Backend | Laravel controllers exist |
| Database | Migrations exist |
| Data source | Mix of real API + some MSW fallbacks |
| Auth | Real Sanctum (may have gaps) |
| Tenant scope | Implemented but not fully tested |
| Observability | Telescope captures basic requests |
| Tests | Backend + frontend tests exist |
| Extractable | Not yet — boundaries still shifting |

**Exit criteria to Stage 4:**
- All CRUD endpoints working
- MSW removed from runtime
- Full test coverage (backend + frontend)
- Seeders provide development data

---

## Stage 4: Backend-Backed Vertical

**Definition:** Fully operational module running against real database with zero mock dependencies at runtime.

| Dimension | Rule |
|-----------|------|
| Backend | Complete Laravel module (models, controllers, policies, routes) |
| Database | Domain migrations in isolated folder |
| Data source | PostgreSQL only — NO runtime MSW |
| Auth | Real Sanctum with policies |
| Tenant scope | Enforced via `TenantScope` on all models |
| Observability | Full Telescope coverage |
| Tests | Backend (Pest) + frontend (Vitest) comprehensive |
| Extractable | Preparing — boundaries are clean |

**Governance requirements:**
- MSW handlers exist only for tests
- Seeders exist for development data
- Migrations isolated in `database/migrations/<domain>/`
- Routes registered via module service provider
- No cross-vertical imports

**Example:** CondoFlow (current stage), Dynamic Forms

---

## Stage 5: Isolated Experience

**Definition:** Vertical with its own navigation, branding, login flow, and user journey — fully isolated from other experiences.

| Dimension | Rule |
|-----------|------|
| Backend | Same as Stage 4 |
| Database | Same as Stage 4 |
| Data source | PostgreSQL only |
| Auth | Experience-aware login (own login page, redirects, roles) |
| Tenant scope | Full enforcement |
| Observability | Full + experience-tagged metrics |
| Navigation | Isolated in `experiences/<name>/navigation.ts` |
| Branding | Isolated in `experiences/<name>/branding.ts` |
| Tests | Full stack + experience isolation tests |
| Extractable | Ready for extraction if needed |

**Governance requirements:**
- All Stage 4 rules apply
- Experience registered in `experiences/registry.ts`
- Navigation items self-contained
- No navigation leakage to other experiences
- Login page exists under vertical routes

**Example:** CondoFlow (current stage — Stage 4 + Stage 5)

---

## Stage 6: Extractable Domain

**Definition:** Module whose boundaries are clean enough to be extracted into a separate deployment unit with minimal effort.

| Dimension | Rule |
|-----------|------|
| Backend | Fully isolated module under `app/Core/<Domain>/` |
| Database | Own migrations, own seeders, zero shared tables (beyond core) |
| Data source | Own database schema (could become separate DB) |
| Auth | Uses Core auth but could adapt to external auth provider |
| Tenant scope | Could become single-tenant if extracted |
| Observability | Could run own Telescope/logging independently |
| API surface | Documented, versioned, stable contracts |
| Tests | Fully self-contained test suite |
| Extractable | ✅ YES — ready when business/compliance requires |

**Governance requirements:**
- All Stage 5 rules apply
- Zero direct PHP class imports between verticals
- Communication between verticals only via events or API calls
- Database queries never JOIN across vertical boundaries
- Could be deployed as standalone Laravel app with effort

---

## Stage 7: Independently Deployable Service

**Definition:** Module extracted into its own repository, deployment pipeline, and runtime.

| Dimension | Rule |
|-----------|------|
| Backend | Separate Laravel application |
| Database | Own PostgreSQL instance |
| Auth | Own auth or federated identity |
| Communication | API contracts or async events only |
| Deployment | Independent CI/CD pipeline |
| Observability | Own monitoring stack |

**This stage does NOT exist today.** It is documented as the end-state possibility.

**Extraction triggers (see domain-extraction-strategy.md):**
- Legal/compliance isolation required
- Independent scaling needs
- Independent release cadence required
- Team autonomy demands separate repos

---

## Current Module Stages

| Module | Current Stage | Notes |
|--------|:------------:|-------|
| Reference Cookbook | 1 (Reference Pattern) | Permanent — will never advance |
| CondoFlow | 4+5 (Backend-Backed + Isolated Experience) | First real vertical |
| Dynamic Forms | 4 (Backend-Backed Vertical) | Not yet an isolated experience |
| MiniHIS | — (planned) | Will start at Stage 3, targeting Stage 5 |
| Observability | Infrastructure | Not a vertical — owned by Core |

---

## Stage Transition Rules

1. A module CANNOT regress to a previous stage
2. Stage transitions MUST be documented in a worklog entry
3. MSW runtime removal is the irreversible gate between Stage 3 → Stage 4
4. Experience isolation (Stage 5) requires governance review
5. Extraction (Stage 6→7) requires ADR approval
