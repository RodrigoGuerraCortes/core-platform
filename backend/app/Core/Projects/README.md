# Core/Projects Module

## Purpose

`Core/Projects` is the **first real domain entity** in the Core Platform. It validates the Tenancy Foundation by consuming `BelongsToTenant`, `TenantScope`, and `ProjectPolicy` in a real HTTP API.

This is NOT a business application module. It is a minimal, verified reference implementation that demonstrates how tenant-owned domain models integrate with the platform's isolation infrastructure.

---

## Tenant Ownership

Every `Project` belongs to exactly one `Tenant` via a `tenant_id` foreign key.

The `BelongsToTenant` trait:
1. Registers `TenantScope` as a global scope — all SELECT queries are automatically filtered to the resolved tenant
2. Auto-fills `tenant_id` from `TenantContextContract` on creation — `tenant_id` is **never** accepted from user input
3. Exposes a `tenant()` BelongsTo relationship

Cross-tenant access via route model binding returns **404** (not 403) — the existence of a resource in another tenant is never revealed.

---

## Authorization Model

`ProjectPolicy` reads `membership_role` via `MembershipResolver` (request-scoped cache). Multiple policy checks within the same request share one DB lookup per `user+tenant` combination.

| Role | viewAny | view | create | update | delete |
|---|---|---|---|---|---|
| **owner** | ✓ | ✓ | ✓ | ✓ | ✓ |
| **admin** | ✓ | ✓ | ✓ | ✓ | ✓ |
| **member** | ✓ | ✓ | ✗ | ✗ | ✗ |
| non-member | blocked by `tenant.member` middleware before policy | | | | |

**Platform admin status does NOT grant automatic authorization.** A platform admin with `membership_role = 'member'` cannot create, update, or delete projects. `is_platform_admin` is not consulted anywhere in `ProjectPolicy`.

---

## Routes

All routes use `TenantRouteMiddleware::STACK` (ADR-011): `auth:sanctum` → `tenant.resolve` → `SubstituteBindings` → `tenant.member`

`SubstituteBindings` runs **after** `tenant.resolve` so route model binding resolves `{project}` with `TenantScope` active. Cross-tenant access returns 404 — resource existence is not revealed.

| Method | URI | Action | Policy |
|---|---|---|---|
| GET | `/projects` | `index` | `viewAny` |
| POST | `/projects` | `store` | `create` |
| GET | `/projects/{project}` | `show` | `view` |
| PATCH | `/projects/{project}` | `update` | `update` |
| DELETE | `/projects/{project}` | `destroy` | `delete` |

---

## Structure

```
Core/Projects/
├── Enums/
│   └── ProjectStatus.php         # active | inactive | archived
├── Http/
│   ├── Controllers/
│   │   └── ProjectController.php # paginate(15) on index
│   ├── Requests/
│   │   ├── StoreProjectRequest.php   # Rule::enum(ProjectStatus::class)
│   │   └── UpdateProjectRequest.php
│   └── Resources/
│       └── ProjectResource.php   # status emitted as string value
├── Models/
│   └── Project.php               # BelongsToTenant, status cast to enum, default 'active'
├── Policies/
│   └── ProjectPolicy.php         # uses MembershipResolver (cached lookup)
├── Providers/
│   └── ProjectsServiceProvider.php
├── Routes/
│   └── api.php                   # Route::middleware(TenantRouteMiddleware::STACK)
└── README.md
```

Supporting files (outside Core/Projects/):
- `database/migrations/2026_05_20_000003_create_projects_table.php`
- `database/factories/ProjectFactory.php`
- `tests/Feature/Projects/ProjectApiTest.php`

---

## Tests

`tests/Feature/Projects/ProjectApiTest.php` — **27 tests**  
`tests/Feature/MembershipResolverTest.php` — **6 tests**  
`tests/Feature/Tenancy/TenantRouteBindingConventionTest.php` — **7 tests** (Block 5 — also validates this module)

| Group | Count | Covers |
|---|---|---|
| Tenant isolation | 4 | A can't see/retrieve/update/delete B's projects |
| Authorization (owner) | 3 | create, update, delete allowed |
| Authorization (admin) | 3 | create, update, delete allowed |
| Authorization (member) | 5 | list/view allowed, write forbidden (3) |
| Runtime | 4 | missing header, unauthenticated, auto-fill, platform admin |
| Pagination | 3 | structure, tenant isolation, page size = 15 |
| ProjectStatus enum | 4 | valid values, invalid → 422, default 'active' |

```bash
cd backend

# Block 4 tests only
./vendor/bin/pest tests/Feature/Projects/ProjectApiTest.php --no-coverage

# Full suite (all blocks)
./vendor/bin/pest tests/Unit/TenantContextTest.php tests/Feature/Tenancy/ tests/Feature/Projects/ --no-coverage
```

---

## Architecture Decisions Respected

| Invariant | Status |
|---|---|
| `users.tenant_id` never added | ✓ |
| No persistent active tenant (session/token) | ✓ |
| `TenantContextContract` is the only runtime tenant source | ✓ |
| `withoutGlobalScopes()` (plural) never used | ✓ |
| Platform admin no automatic bypass | ✓ |
| `tenant_id` never accepted from user input | ✓ |
| `SubstituteBindings` placed after `tenant.resolve` | ✓ |

---

## Non-Goals

- Full RBAC or generic permissions system
- Business domain logic (categories, tags, assignments)
- Onboarding, billing, or dashboards
- Subdomain routing
- Tenant-to-tenant project sharing
- Soft-delete restore API endpoint
