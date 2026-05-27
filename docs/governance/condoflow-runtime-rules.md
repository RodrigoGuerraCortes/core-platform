# CondoFlow Runtime Rules

> Authoritative reference for CondoFlow vertical runtime behavior.
> Last updated: 2026-05-25

---

## Purpose

CondoFlow is a **Stage 4+5 backend-backed vertical** with isolated experience.
This document defines its runtime governance rules.

---

## Official Runtime Mode: `vertical-runtime`

CondoFlow operates in **vertical-runtime** mode. See [runtime-modes.md](../runtime-modes.md).

---

## Data Source Rules

### ✅ ALLOWED

| Source | Context |
|--------|---------|
| PostgreSQL via Laravel API | All runtime (dev + production) |
| MSW node server | Vitest tests only |
| Development seeders | Initial data bootstrap |

### ❌ FORBIDDEN

| Source | Why |
|--------|-----|
| MSW browser worker at runtime | Violates vertical-runtime mode |
| Hardcoded arrays in components | Not a data source |
| localStorage for business data | Not persistent |
| Inline mock data | Demo pattern, not production |

---

## The Absolute Rule

> **CondoFlow requests MUST hit Laravel → PostgreSQL.**
> **Never MSW at runtime.**

This rule was enforced: 2026-05-25 (Block 9.2.2).

---

## Architecture Flow

```
Frontend Page
    ↓
TanStack Query composable (useBuildingsQuery, etc.)
    ↓
API client (fetchBuildings, etc.)
    ↓
apiClient.get('/condoflow/buildings')
    ↓
Vite proxy (no MSW interception)
    ↓
Laravel CondoFlow controller
    ↓
Eloquent model with TenantScope
    ↓
PostgreSQL
```

---

## API Surface

All CondoFlow APIs are under `/api/condoflow/*`:

| Endpoint | Methods | Purpose |
|----------|---------|---------|
| `/api/condoflow/dashboard` | GET | Aggregate metrics |
| `/api/condoflow/buildings` | GET, POST | Building CRUD |
| `/api/condoflow/buildings/:id` | GET, PATCH, DELETE | Building detail |
| `/api/condoflow/units` | GET, POST | Unit CRUD |
| `/api/condoflow/units/:id` | GET, PATCH, DELETE | Unit detail |
| `/api/condoflow/residents` | GET, POST | Resident CRUD |
| `/api/condoflow/residents/:id` | GET, PATCH, DELETE | Resident detail |
| `/api/condoflow/tickets` | GET, POST | Ticket CRUD |
| `/api/condoflow/tickets/:id` | GET, PATCH, DELETE | Ticket detail |

---

## Tenant Isolation

All CondoFlow data is **tenant-scoped**:

1. Every model has `tenant_id` column
2. Every model uses `TenantScope` (global scope)
3. Every API request carries `X-Tenant-Id` header (injected by apiClient)
4. Backend middleware validates tenant access
5. Queries automatically filter by tenant

**Cross-tenant access is physically impossible at the query level.**

---

## Authentication

CondoFlow uses:
- Experience-aware login at `/condoflow/login`
- Sanctum session cookies
- Post-login redirect to `/t/:tenantSlug/condoflow`
- Shared `useAuthStore` with Core Platform
- Experience-specific auth via `useExperienceAuth()`

---

## Navigation

CondoFlow navigation is isolated in:

```
src/experiences/condoflow/navigation.ts
```

Items MUST only reference `/t/:slug/condoflow/*` routes.
NEVER reference Platform or other vertical routes.

---

## Seeding

Development seeder: `database/seeders/CondoFlow/CondoFlowSeeder.php`

Creates:
- 2 tenants (acme, vista-mar)
- 2 buildings per tenant (Torre A, Torre B)
- 5 units
- 3 residents
- 4 tickets

Seeder MUST:
- Call `setTenant()` before creating records
- Be idempotent (safe to re-run)
- Create realistic data (not lorem ipsum)

---

## Frontend Structure

```
src/modules/condoflow/
├── api/
│   └── condoflow.ts              ← API client (fetchBuildings, etc.)
├── composables/
│   └── index.ts                  ← TanStack Query hooks
├── mocks/
│   └── handlers.ts               ← MSW handlers (TEST-ONLY)
├── pages/
│   ├── BuildingsIndexPage.vue
│   ├── UnitsIndexPage.vue
│   ├── ResidentsIndexPage.vue
│   └── TicketsIndexPage.vue
├── routes.ts                     ← Route definitions
└── types/
    └── index.ts                  ← TypeScript interfaces
```

---

## CRUD Persistence Guarantee

All CRUD operations MUST:

1. **Persist in PostgreSQL** — changes survive app restart
2. **Respond with real data** — no fake responses
3. **Respect tenant scope** — cannot affect other tenants
4. **Trigger Telescope entries** — observable in Laravel Telescope
5. **Pass integration tests** — verified end-to-end

---

## Forbidden Patterns

| Pattern | Why |
|---------|-----|
| `setupWorker` import in CondoFlow runtime code | Runtime MSW forbidden |
| Hardcoded `const buildings = [...]` in pages | Not real data |
| Calling Dynamic Forms APIs from CondoFlow | Cross-vertical coupling |
| Bypassing apiClient with raw fetch() | Skips tenant header injection |
| Using localStorage for entities | Not a database |

---

## Validation Checklist

Before deploying CondoFlow changes, verify:

- [ ] Network tab shows real XHR (not "from service worker")
- [ ] Responses contain seeded PostgreSQL data
- [ ] Telescope captures all requests
- [ ] CRUD mutations persist after refresh
- [ ] All 17 backend tests pass (`pest`)
- [ ] All 6 frontend tests pass (`vitest`)
- [ ] Zero ESLint errors
- [ ] No MSW runtime handlers registered

---

## Testing Strategy

| Test Type | Tool | Scope |
|-----------|------|-------|
| Unit | Vitest | Composables, utilities |
| Component | Vitest + MSW node | Pages with mock API |
| Integration | Pest | Backend controllers + policies |
| E2E | (future) | Full stack browser automation |

---

## Observability

CondoFlow requests appear in Telescope:

- **Requests tab:** HTTP method, path, status
- **Queries tab:** SQL with tenant_id WHERE clause
- **Exceptions tab:** Runtime errors
- **Jobs tab:** Async background work (future)

Telescope URL: `http://localhost:8010/telescope` (dev only)

---

## Extraction Readiness

CondoFlow is **extraction-ready** (Stage 6) if:

- [ ] No direct PHP imports from other verticals
- [ ] No frontend imports from other modules
- [ ] No database JOINs across vertical boundaries
- [ ] Communication only via events (future)
- [ ] Own test suite passes independently

**Status:** ⚠️ Partially ready — shares `users` table with Core.
