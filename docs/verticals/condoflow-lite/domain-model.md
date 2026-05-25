# CondoFlow Lite — Domain Model

## Entity Relationships

```
Tenant (1) ──── (N) Building
                     │
Building (1) ── (N) Unit
                     │
Unit (1) ────── (N) Resident
Unit (1) ────── (N) MaintenanceTicket

Resident (1) ── (N) MaintenanceTicket
```

## Building
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| tenant_id | FK → tenants | Scoped |
| name | string(255) | Required |
| address | string(500) | Nullable |
| floors | smallint | Default 1 |
| metadata | json | Nullable |
| timestamps | | |
| soft_deletes | | |

## Unit
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| tenant_id | FK → tenants | Scoped |
| building_id | FK → buildings | Required |
| number | string(50) | Required |
| floor | smallint | Default 1 |
| type | enum | apartment, office, commercial, parking, storage |
| status | enum | available, occupied, maintenance |
| metadata | json | Nullable |

## Resident
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| tenant_id | FK → tenants | Scoped |
| unit_id | FK → units | Nullable |
| name | string(255) | Required |
| rut | string(20) | Chilean ID, nullable |
| email | string(255) | Nullable |
| phone | string(50) | Nullable |
| status | enum | active, inactive |
| metadata | json | Nullable |

## MaintenanceTicket
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| tenant_id | FK → tenants | Scoped |
| unit_id | FK → units | Nullable |
| resident_id | FK → residents | Nullable |
| title | string(255) | Required |
| description | text | Nullable |
| status | enum | open, in_progress, resolved, closed |
| priority | enum | low, medium, high |
| metadata | json | Nullable |

## Status Workflow (Tickets)

```
open → in_progress → resolved → closed
  ↑                      │
  └──────────────────────┘ (reopen)
```

## Tenant Isolation

All models use `BelongsToTenant` trait which:
1. Adds a global `TenantScope` (WHERE tenant_id = current)
2. Auto-fills `tenant_id` on `creating` event from `TenantContextContract`

Route model binding resolves within tenant scope — cross-tenant access returns 404, never 403 (no information leak).
