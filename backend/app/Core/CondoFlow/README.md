# CondoFlow — Backend Module

## Overview

CondoFlow is the first vertical validation module for the Core Platform. It provides property management functionality: buildings, units, residents, and maintenance tickets.

## Directory Structure

```
app/Core/CondoFlow/
├── Enums/
│   ├── MaintenanceTicketPriority.php   # high, medium, low
│   ├── MaintenanceTicketStatus.php     # open, in_progress, resolved, closed
│   ├── ResidentStatus.php             # active, inactive
│   └── UnitStatus.php                 # occupied, vacant
├── Http/
│   ├── Controllers/
│   │   ├── BuildingController.php     # CRUD (index, store, show, update, destroy)
│   │   ├── DashboardController.php    # Aggregated stats (__invoke)
│   │   ├── MaintenanceTicketController.php
│   │   ├── ResidentController.php
│   │   └── UnitController.php
│   ├── Requests/
│   │   ├── StoreBuildingRequest.php
│   │   ├── UpdateBuildingRequest.php
│   │   ├── StoreUnitRequest.php
│   │   ├── UpdateUnitRequest.php
│   │   ├── StoreResidentRequest.php
│   │   ├── UpdateResidentRequest.php
│   │   ├── StoreMaintenanceTicketRequest.php
│   │   └── UpdateMaintenanceTicketRequest.php
│   └── Resources/
│       ├── BuildingResource.php
│       ├── UnitResource.php
│       ├── ResidentResource.php
│       └── MaintenanceTicketResource.php
├── Models/
│   ├── Building.php
│   ├── Unit.php
│   ├── Resident.php
│   └── MaintenanceTicket.php
├── Policies/
│   ├── BuildingPolicy.php
│   ├── UnitPolicy.php
│   ├── ResidentPolicy.php
│   └── MaintenanceTicketPolicy.php
├── Providers/
│   └── CondoFlowServiceProvider.php
└── Routes/
    └── api.php
```

## API Routes

All routes are prefixed with `/api/{tenantSlug}/condoflow` and require Sanctum auth.

| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/buildings` | BuildingController@index | condoflow.buildings.index |
| POST | `/buildings` | BuildingController@store | condoflow.buildings.store |
| GET | `/buildings/{building}` | BuildingController@show | condoflow.buildings.show |
| PUT | `/buildings/{building}` | BuildingController@update | condoflow.buildings.update |
| DELETE | `/buildings/{building}` | BuildingController@destroy | condoflow.buildings.destroy |
| GET | `/units` | UnitController@index | condoflow.units.index |
| POST | `/units` | UnitController@store | condoflow.units.store |
| GET | `/units/{unit}` | UnitController@show | condoflow.units.show |
| PUT | `/units/{unit}` | UnitController@update | condoflow.units.update |
| DELETE | `/units/{unit}` | UnitController@destroy | condoflow.units.destroy |
| GET | `/residents` | ResidentController@index | condoflow.residents.index |
| POST | `/residents` | ResidentController@store | condoflow.residents.store |
| GET | `/residents/{resident}` | ResidentController@show | condoflow.residents.show |
| PUT | `/residents/{resident}` | ResidentController@update | condoflow.residents.update |
| DELETE | `/residents/{resident}` | ResidentController@destroy | condoflow.residents.destroy |
| GET | `/tickets` | MaintenanceTicketController@index | condoflow.tickets.index |
| POST | `/tickets` | MaintenanceTicketController@store | condoflow.tickets.store |
| GET | `/tickets/{ticket}` | MaintenanceTicketController@show | condoflow.tickets.show |
| PUT | `/tickets/{ticket}` | MaintenanceTicketController@update | condoflow.tickets.update |
| DELETE | `/tickets/{ticket}` | MaintenanceTicketController@destroy | condoflow.tickets.destroy |
| GET | `/dashboard` | DashboardController@__invoke | condoflow.dashboard |

## Important Notes

### Tenant Isolation
- All models use the `BelongsToTenant` trait — `tenant_id` is auto-scoped on all queries.
- Policies verify `$model->tenant_id === auth()->user()->tenant_id`.
- Route model binding uses `TenantRouteRegistrar` to scope resolution.

### Service Provider
- Registered in `bootstrap/providers.php`.
- Loads routes, registers policies, and binds models for route model binding.

### Migrations
- `2026_05_25_300001_create_condoflow_buildings_table.php`
- `2026_05_25_300002_create_condoflow_units_table.php`
- `2026_05_25_300003_create_condoflow_residents_table.php`
- `2026_05_25_300004_create_condoflow_maintenance_tickets_table.php`

### Running Tests
```bash
php artisan test --filter=CondoFlow
```

### Key Conventions
- Enums are string-backed for JSON serialization.
- FormRequests handle authorization via `Gate::authorize()`.
- Resources wrap responses in standard `{ data: ... }` envelope.
- Controllers follow single-responsibility (no nested resource controllers).
