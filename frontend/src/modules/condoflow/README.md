# CondoFlow — Frontend Module

## Overview

CondoFlow is the property management vertical module. It provides a dashboard, CRUD for buildings/units/residents, and a maintenance ticket system. It also includes an **independent login** for residents.

## Directory Structure

```
src/modules/condoflow/
├── api/
│   └── condoflow.api.ts         # Axios API layer (CRUD + dashboard)
├── composables/
│   └── useCondoflow.ts          # TanStack Query composables for all entities
├── mocks/
│   └── handlers.ts              # MSW request handlers for dev/test
├── pages/
│   ├── CondoFlowLoginPage.vue   # Independent resident login
│   ├── CondoDashboardPage.vue   # Stats overview
│   ├── BuildingsIndexPage.vue   # Buildings table
│   ├── UnitsIndexPage.vue       # Units table
│   ├── ResidentsIndexPage.vue   # Residents table
│   ├── TicketsIndexPage.vue     # Tickets table
│   └── TicketDetailPage.vue     # Single ticket detail
├── tests/
│   └── composables.test.ts      # 6 unit tests for composables
├── types/
│   └── index.ts                 # TypeScript interfaces
└── routes.ts                    # Route definitions
```

## Routes

### Tenant-scoped (require auth, nested under `/t/:tenantSlug`)

| Path | Name | Page |
|------|------|------|
| `condoflow` | condoflow.dashboard | CondoDashboardPage |
| `condoflow/buildings` | condoflow.buildings.index | BuildingsIndexPage |
| `condoflow/units` | condoflow.units.index | UnitsIndexPage |
| `condoflow/residents` | condoflow.residents.index | ResidentsIndexPage |
| `condoflow/tickets` | condoflow.tickets.index | TicketsIndexPage |
| `condoflow/tickets/:id` | condoflow.tickets.detail | TicketDetailPage |

### Public (independent login)

| Path | Name | Page |
|------|------|------|
| `/condoflow/login` | condoflow.login | CondoFlowLoginPage |

## Independent Login

CondoFlow exposes its own login page at `/condoflow/login`. This allows residents to access the portal without going through the main platform login. It uses the same Sanctum session auth but redirects to the condoflow dashboard after login.

**Exported from routes.ts:**
- `condoflowRoutes` — tenant-scoped routes (spread into TenantLayout children)
- `condoflowPublicRoutes` — independent login (spread at router top-level)

## Important Notes

### Governance
- All UI imports MUST come from `@/shared/ui` (barrel) or `@/shared/table`.
- Never import directly from `@/shared/primitives/` or `@/shared/layouts/`.
- Column slot pattern: `#col-<key>` with `{ item }` or `{ value }` props.
- For dotted column keys (e.g., `building.name`), use dynamic slot: `` #[`col-building.name`] ``.

### API Layer
- Base path: `/api/{tenantSlug}/condoflow/`
- All requests go through the shared Axios client with Sanctum CSRF handling.

### Testing
```bash
npx vitest run src/modules/condoflow/
```

### MSW Mocks
Handlers are registered in both `src/mocks/browser.ts` (dev) and `src/tests/mocks/server.ts` (test). They provide realistic fake data for all endpoints.

### AppStatusChip Usage
CondoFlow uses custom status values (open, in_progress, resolved, etc.) that aren't in the `PresetStatus` type. Use `label` + `color` props instead of the `status` prop:
```vue
<AppStatusChip :label="item.status" :color="statusColor(item.status)" />
```
