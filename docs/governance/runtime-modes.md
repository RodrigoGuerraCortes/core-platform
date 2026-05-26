# Runtime Modes

> Authoritative reference. All modules and agents MUST comply.
> Last updated: 2026-05-25

---

## Purpose

The platform operates in exactly **five** runtime modes.
Each mode has explicit rules for data sources, auth, observability, and allowed patterns.
There is no ambiguity — if a mode does not explicitly allow a behavior, it is forbidden.

---

## Mode 1: `cookbook` (explicit opt-in)

> ⚠️ **This mode is NOT the default.** Developers must explicitly set
> `VITE_RUNTIME_MODE=cookbook` in their `.env` to activate it.
> The platform defaults to `vertical` to ensure all requests reach the real backend.

**Purpose:** Frontend-only demo/sandbox for UI patterns and reference implementations.

| Dimension | Rule |
|-----------|------|
| Data source | MSW (Mock Service Worker) — runtime interception allowed |
| Auth | Mocked via `devAuthHandlers` in browser.ts |
| Backend dependency | NONE — must work with zero backend processes |
| Tenant behavior | Ignored — no tenant context required |
| Observability | None expected — no Telescope, no logs |
| MSW allowed | ✅ Yes — this is the ONLY business mode where runtime MSW is acceptable |
| Database | Not accessed |
| Seeding | Not applicable |

**Modules in this mode:** Reference Cookbook

**Forbidden patterns:**
- ❌ Accessing real database
- ❌ Requiring Docker/Laravel to render
- ❌ Creating real tenant-scoped records
- ❌ Emitting domain events

---

## Mode 2: `vertical-runtime` (DEFAULT)

> ✅ **This is the default runtime mode.** When `VITE_RUNTIME_MODE` is unset or set
> to `vertical`, the MSW browser worker is NEVER started and all `/api/*` requests
> flow through the Vite proxy directly to Laravel.

**Purpose:** Real business vertical operating against Laravel + PostgreSQL with tenant-scoped data.

| Dimension | Rule |
|-----------|------|
| Data source | Laravel API → PostgreSQL (real seeded or user-created data) |
| Auth | Sanctum session auth with real cookies |
| Backend dependency | REQUIRED — Docker stack must be running |
| Tenant behavior | MANDATORY — all requests carry `X-Tenant-Id` header |
| Observability | REQUIRED — Telescope captures requests, queries, events |
| MSW allowed | ❌ **FORBIDDEN** — requests MUST hit real backend |
| Database | Real PostgreSQL with domain migrations applied |
| Seeding | Development seeders provide initial data |

**Modules in this mode:** CondoFlow, Dynamic Forms, MiniHIS (future)

**Forbidden patterns:**
- ❌ Runtime MSW handlers for this module's endpoints
- ❌ Hardcoded/mock data in composables
- ❌ Bypassing tenant middleware
- ❌ Direct database access from frontend
- ❌ Skipping auth/session flow

**Invariants:**
- Every API call results in a Telescope entry
- Every response contains real database records
- Tenant isolation is enforced at query scope level
- Network tab shows real XHR (never "from service worker")

---

## Mode 3: `tests`

**Purpose:** Isolated unit and component testing via Vitest with deterministic data.

| Dimension | Rule |
|-----------|------|
| Data source | MSW `setupServer` (msw/node) — test-only mock server |
| Auth | Mocked per test scenario |
| Backend dependency | NONE — tests run without Docker |
| Tenant behavior | Simulated via mock responses |
| Observability | None — console assertions only |
| MSW allowed | ✅ Yes — via `src/tests/mocks/server.ts` (node, not browser) |
| Database | Not accessed |
| Seeding | Not applicable — fixtures defined in test files |

**Forbidden patterns:**
- ❌ Tests depending on running Docker containers
- ❌ Tests hitting real network endpoints
- ❌ Tests mutating shared state between files
- ❌ Importing from `msw/browser` in test files

---

## Mode 4: `e2e`

**Purpose:** End-to-end testing of full stack (browser → Vite → Laravel → PostgreSQL).

| Dimension | Rule |
|-----------|------|
| Data source | Real Laravel API + test database |
| Auth | Real Sanctum session flow |
| Backend dependency | REQUIRED — full Docker stack running |
| Tenant behavior | Real tenant resolution with test tenants |
| Observability | Optional — Telescope may be disabled for speed |
| MSW allowed | ❌ **FORBIDDEN** — e2e tests validate the real stack |
| Database | Dedicated test database, reset between suites |
| Seeding | Test seeders run before suite |

**Forbidden patterns:**
- ❌ MSW interception (defeats purpose of e2e)
- ❌ Skipping auth flow
- ❌ Sharing database state between test files
- ❌ Mocking HTTP at any layer

---

## Mode 5: `production`

**Purpose:** Live deployment serving real users with real data.

| Dimension | Rule |
|-----------|------|
| Data source | Production PostgreSQL |
| Auth | Sanctum with secure cookies, HTTPS only |
| Backend dependency | Always running (orchestrated deployment) |
| Tenant behavior | Strict enforcement — no cross-tenant access possible |
| Observability | Full — structured logs, metrics, alerting |
| MSW allowed | ❌ **FORBIDDEN** — MSW code is tree-shaken out of production builds |
| Database | Production PostgreSQL with real migrations |
| Seeding | NEVER — only migrations run in production |

**Forbidden patterns:**
- ❌ Any mock data or MSW code in production bundle
- ❌ Development seeders running against production
- ❌ Debug endpoints exposed
- ❌ Telescope without access control

---

## Decision Matrix

| Question | cookbook | vertical-runtime | tests | e2e | production |
|----------|:-------:|:-----------------:|:-----:|:---:|:----------:|
| MSW browser allowed? | ✅ | ❌ | — | ❌ | ❌ |
| MSW node allowed? | — | — | ✅ | ❌ | ❌ |
| Real database? | ❌ | ✅ | ❌ | ✅ | ✅ |
| Docker required? | ❌ | ✅ | ❌ | ✅ | ✅ |
| Telescope active? | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| Tenant enforced? | ❌ | ✅ | Simulated | ✅ | ✅ |
| Auth real? | ❌ | ✅ | ❌ | ✅ | ✅ |

---

## Enforcement

- CI lint checks MUST verify no MSW browser imports in vertical modules
- Production builds MUST tree-shake all MSW code
- Backend tests (`phpunit`/`pest`) run in `tests` mode with SQLite or test PostgreSQL
- Frontend tests (`vitest`) run in `tests` mode with MSW node server
- Runtime default MUST be `vertical` — CI fails if fallback is `cookbook`
- `.env.example` MUST contain `VITE_RUNTIME_MODE=vertical`

## Stale Service Worker Cleanup

When switching from `cookbook` to `vertical` mode (or on first load in vertical mode),
`main.ts` checks for any existing service worker registrations. If found:

1. All registrations are unregistered via `navigator.serviceWorker.getRegistrations()`
2. `localStorage` MSW version key is cleared
3. Page reloads once to fully release SW control
4. On the second load, no SW exists and bootstrap proceeds normally

This ensures a developer who previously ran in `cookbook` mode will have a clean
vertical runtime after a single automatic reload.
