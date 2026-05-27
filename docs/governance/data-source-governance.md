# Data Source Governance

> Authoritative reference. Defines what data sources are allowed per context.
> Last updated: 2026-05-25

---

## Purpose

Every API call, every rendered list, every dashboard metric has a source.
This document defines which sources are allowed and which are forbidden per module and runtime mode.

There is no "it works so it's fine." The source must be correct.

---

## Source-of-Truth Hierarchy

For any business vertical in `vertical-runtime` mode:

```
1. PostgreSQL (via Laravel Eloquent) ← ONLY acceptable source
2. Cache (Redis) ← acceptable for read optimization, not for truth
3. MSW mock ← FORBIDDEN at runtime
4. Hardcoded fixture ← FORBIDDEN at runtime
5. localStorage ← FORBIDDEN for business data
```

---

## Rules by Module

### Reference Cookbook

| Source | Allowed | Context |
|--------|:-------:|---------|
| MSW runtime (browser) | ✅ | Development |
| MSW test (node) | ✅ | Vitest |
| PostgreSQL | ❌ | Never — cookbook has no backend |
| Hardcoded fixtures | ✅ | For static demos |

### CondoFlow

| Source | Allowed | Context |
|--------|:-------:|---------|
| PostgreSQL via API | ✅ | Development + Production |
| MSW runtime (browser) | ❌ | **FORBIDDEN** (removed 2026-05-25) |
| MSW test (node) | ✅ | Vitest component tests |
| Development seeders | ✅ | `CondoFlowSeeder` provides initial data |
| Hardcoded fixtures | ❌ | Never in runtime code |

### Dynamic Forms

| Source | Allowed | Context |
|--------|:-------:|---------|
| PostgreSQL via API | ✅ | Development + Production |
| MSW runtime (browser) | ⚠️ | Transitional — to be removed |
| MSW test (node) | ✅ | Vitest |
| Development seeders | ✅ | Form seeders |

### MiniHIS (future)

| Source | Allowed | Context |
|--------|:-------:|---------|
| PostgreSQL via API | ✅ | Required from day one |
| MSW runtime (browser) | ❌ | Forbidden — follows CondoFlow model |
| MSW test (node) | ✅ | Vitest |

---

## When MSW Is Allowed

> The platform defaults to `vertical` runtime. MSW browser interception only
> activates when `VITE_RUNTIME_MODE=cookbook` is explicitly set.

| Context | Allowed |
|---------|:-------:|
| Runtime browser — Reference module (cookbook mode) | ✅ |
| Runtime browser — Business vertical | ❌ |
| Vitest (any module) | ✅ |
| E2E tests | ❌ |
| Production | ❌ |

**The rule is absolute:**
> Once a module has a working backend (controllers + migrations + seeders), MSW MUST be removed from runtime registration.

---

## Development Seeder Expectations

Every backend-backed vertical MUST provide:

1. **Domain seeder** — creates realistic development data
2. **Tenant-scoped data** — seeder calls `setTenant()` before creating records
3. **Deterministic output** — same seed produces same data (use sequential IDs, fixed names)
4. **Orchestrator registration** — seeder is called from `DevelopmentBootstrapSeeder`

### Seeder hierarchy

```
DevelopmentBootstrapSeeder (orchestrator)
├── CoreSeeder (platform admin, tenants)
├── CondoFlowSeeder (buildings, units, residents, tickets)
├── DynamicFormsSeeder (form schemas, versions)
└── MiniHISSeeder (future)
```

### Seeder rules

- Seeders MUST be idempotent (safe to re-run)
- Seeders MUST create enough data for meaningful UI testing (≥3 records per entity)
- Seeders MUST NOT depend on other vertical seeders (only Core)
- Production NEVER runs seeders — only migrations

---

## Tenant-Scoped Data Rules

All business vertical data MUST be tenant-scoped:

1. Every model has `tenant_id` column
2. Every model uses `TenantScope` (global scope)
3. Every seeder sets tenant context before creating records
4. Every API response only contains current tenant's data
5. Cross-tenant queries are physically impossible at the Eloquent level

---

## Runtime Data Verification

To verify a module is using real data:

1. **Network tab:** Request shows real XHR (not "from service worker")
2. **Response body:** Contains data matching seeders (not MSW fixtures)
3. **Telescope:** Request appears in Telescope dashboard
4. **SQL tab:** Telescope shows the actual query with `tenant_id` WHERE clause
5. **Cookie:** `laravel_session` and `XSRF-TOKEN` cookies present

---

## Forbidden Patterns

| Pattern | Why |
|---------|-----|
| `localStorage` for business entities | Not a database |
| `sessionStorage` for persistent data | Lost on tab close |
| Hardcoded arrays pretending to be API responses | Lie to the developer |
| MSW runtime handlers for backend-backed verticals | Hides real integration bugs |
| Frontend-only state as source of truth for shared data | Will desync |
| Direct fetch without `apiClient` | Bypasses tenant header injection |
