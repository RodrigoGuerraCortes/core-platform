# Ownership Matrix

> Authoritative reference. Defines who owns what. No ambiguity.
> Last updated: 2026-05-25

---

## Purpose

Every piece of the platform has exactly ONE owner.
Ownership means: the owner controls the schema, API surface, runtime behavior, navigation, and observability of that domain.

Cross-owner dependencies MUST be explicit and documented.
Implicit coupling is a governance violation.

---

## Core Platform

**Owner:** Platform team / infrastructure layer

### Owns

| Asset | Scope |
|-------|-------|
| Authentication | Sanctum, sessions, login/logout, guards |
| Tenancy | `TenantContext`, tenant middleware, `X-Tenant-Id` resolution |
| Observability | Telescope, structured logging, health endpoints |
| Experience Routing | Experience resolver, registry, experience-aware guards |
| UI Governance | `@/shared/ui` barrel, `AppDataTable`, `AppPageLayout`, design tokens |
| Queue Infrastructure | Job dispatch, worker config, retry policies |
| Audit | Audit trail infrastructure (not domain-specific audit entries) |
| Migration Infrastructure | `MigrationServiceProvider`, domain folder convention |
| API Client | `@/shared/api/client.ts`, interceptors, base URL |
| Auth Store | `useAuthStore`, session management |
| MSW Infrastructure | `browser.ts` worker setup, `devAuthHandlers` |
| Router Shell | `router/index.ts`, guards, experience resolution |

### Does NOT Own

- ❌ Business domain models (buildings, forms, patients)
- ❌ Vertical-specific navigation items
- ❌ Vertical-specific API endpoints
- ❌ Vertical-specific seeders (beyond core tenant/user setup)

---

## Reference Cookbook

**Owner:** DX team / pattern governance

### Owns

| Asset | Scope |
|-------|-------|
| Frontend examples | `src/modules/reference/` — all pages, components |
| Canonical patterns | Demonstrates how to use shared UI correctly |
| Sandbox demos | Interactive playgrounds for components |
| UI references | Visual catalog of approved patterns |
| MSW handlers (runtime) | `src/modules/reference/mocks/handlers.ts` |

### Does NOT Own

- ❌ Real database tables
- ❌ Backend APIs
- ❌ Tenant-scoped data
- ❌ Domain events
- ❌ Production behavior

### Invariants

- Reference module NEVER touches the database
- Reference module works with zero backend processes
- Reference module is the ONLY module allowed runtime MSW for its data

---

## CondoFlow

**Owner:** CondoFlow vertical team

### Owns

| Asset | Scope |
|-------|-------|
| Buildings | Model, migration, controller, policy, API, frontend page |
| Units | Model, migration, controller, policy, API, frontend page |
| Residents | Model, migration, controller, policy, API, frontend page |
| Tickets | Model, migration, controller, policy, API, frontend page |
| Dashboard | Aggregate metrics controller + frontend widget |
| Navigation | `src/experiences/condoflow/navigation.ts` |
| Branding | `src/experiences/condoflow/branding.ts` |
| Seeders | `database/seeders/CondoFlow/CondoFlowSeeder.php` |
| Migrations | `database/migrations/condoflow/` |
| Routes | `app/Core/CondoFlow/Routes/api.php` |
| Test mocks | `src/modules/condoflow/mocks/handlers.ts` (test-only) |

### Does NOT Own

- ❌ Auth system (uses Core Platform's Sanctum)
- ❌ Tenant resolution (uses Core Platform's middleware)
- ❌ UI primitives (uses `@/shared/ui`)
- ❌ API client infrastructure (uses `@/shared/api/client`)
- ❌ Experience routing logic (registered in Core's registry)

### Invariants

- All CondoFlow data is tenant-scoped (enforced via `TenantScope`)
- All CondoFlow API responses come from PostgreSQL (never MSW at runtime)
- CondoFlow navigation only appears in the `condoflow` experience
- CondoFlow migrations live in `database/migrations/condoflow/`

---

## Dynamic Forms

**Owner:** Forms vertical team

### Owns

| Asset | Scope |
|-------|-------|
| Form schemas | Model, migration, JSON schema engine |
| Form submissions | Storage, validation, processing |
| Form versioning | Version tracking, draft/publish lifecycle |
| Form builder UI | `src/modules/dynamic-forms/` pages and components |
| Migrations | `database/migrations/dynamic_forms/` |
| Seeders | Domain-specific form seeders |

### Does NOT Own

- ❌ Auth system
- ❌ Tenant resolution
- ❌ UI primitives
- ❌ File storage infrastructure (uses Core's filesystem)

---

## Observability

**Owner:** Core Platform (infrastructure concern)

### Owns

| Asset | Scope |
|-------|-------|
| Telescope | Configuration, dashboard, data retention |
| Structured logging | Log channels, format, sinks |
| Health checks | `/api/health`, dependency status |
| Migrations | `database/migrations/observability/` |

### Does NOT Own

- ❌ What verticals log (they decide their own log entries)
- ❌ Business metrics (verticals own their dashboards)

---

## Forbidden Ownership Leaks

These are GOVERNANCE VIOLATIONS:

| Violation | Why |
|-----------|-----|
| CondoFlow importing from Dynamic Forms directly | Cross-vertical coupling |
| Reference module writing to database | Cookbook must be read-only/mock |
| Core Platform defining business models | Core owns infra, not domains |
| Vertical defining its own auth guards | Auth is Core's responsibility |
| Vertical bypassing `TenantScope` | Tenant isolation is a platform invariant |
| Vertical importing from another vertical's `composables/` | Coupling between verticals |
| Vertical registering its own MSW handlers at runtime | Only Reference may do this |
| Core Platform containing CondoFlow-specific UI | Vertical UI lives in vertical |

---

## Dependency Direction

```
┌─────────────────────────────────────────────────┐
│                  Core Platform                    │
│  (auth, tenancy, observability, UI primitives)   │
└────────────────────┬────────────────────────────┘
                     │ depends on (downward only)
        ┌────────────┼────────────────┐
        ▼            ▼                ▼
   CondoFlow    Dynamic Forms     MiniHIS
   (vertical)    (vertical)      (future)
```

- Verticals depend on Core Platform — NEVER on each other
- Core Platform NEVER depends on verticals
- Reference Cookbook depends on Core Platform UI only (no backend)

---

## Cross-Cutting Concerns

| Concern | Owner | How verticals use it |
|---------|-------|---------------------|
| Auth | Core | Import `useAuthStore`, use Sanctum session |
| Tenancy | Core | Middleware auto-applies; verticals never manage tenant state |
| API client | Core | Import `apiClient` from `@/shared/api/client` |
| UI components | Core | Import from `@/shared/ui` barrel |
| Table system | Core | Import from `@/shared/table` |
| Experience | Core | Register in `experiences/registry.ts` |
| Navigation | Vertical | Define in `experiences/<name>/navigation.ts` |
| Branding | Vertical | Define in `experiences/<name>/branding.ts` |
