# CondoFlow Lite — Overview

## Purpose

CondoFlow Lite is the **first real vertical validation module** built on Core Platform. It demonstrates that the platform's modular monolith architecture, governance patterns, and shared primitives can support a realistic business domain without friction.

## What It Is

A simplified condominium management module covering:
- **Buildings** — physical structures with floors
- **Units** — apartments/offices within buildings
- **Residents** — people associated to units
- **Maintenance Tickets** — service requests with status workflow

## What It Is NOT

- Not a full condominium management system
- No payments/accounting
- No complex RBAC beyond tenant membership roles
- No realtime notifications
- No mobile app
- No separate repository or extracted package

## Architecture

```
backend/app/Core/CondoFlow/
├── Enums/          (5 enums: UnitType, UnitStatus, ResidentStatus, TicketStatus, TicketPriority)
├── Http/
│   ├── Controllers/ (5 controllers including Dashboard)
│   ├── Requests/    (8 form requests)
│   └── Resources/   (4 API resources)
├── Models/          (4 models with BelongsToTenant)
├── Policies/        (4 policies)
├── Providers/       (CondoFlowServiceProvider)
└── Routes/          (api.php with TenantRouteRegistrar)

frontend/src/modules/condoflow/
├── api/             (condoflow.ts — all API functions)
├── composables/     (TanStack Query hooks + mutations)
├── mocks/           (MSW handlers for dev & test)
├── pages/           (6 pages: Dashboard, 4 indexes, 1 detail)
├── tests/           (composable integration tests)
├── types/           (TypeScript interfaces)
└── routes.ts        (Vue Router definitions)
```

## Validation Results

| Pattern | Reused Successfully |
|---------|-------------------|
| BelongsToTenant | ✅ |
| TenantRouteRegistrar | ✅ |
| CoreModuleServiceProvider | ✅ |
| Gate::authorize policies | ✅ |
| API Resources | ✅ |
| FormRequests | ✅ |
| AppDataTable | ✅ |
| AppPageLayout | ✅ |
| AppDetailLayout | ✅ |
| AppStatusChip | ✅ |
| AppButton | ✅ |
| TanStack Query composables | ✅ |
| MSW browser + node handlers | ✅ |
| Pest feature tests | ✅ |
| Vitest composable tests | ✅ |
