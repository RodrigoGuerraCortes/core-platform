# Runtime Audit Report — Block GOV-2

> Generated: 2026-05-25
> Audited by: AI Agent during GOV-2 stabilization pass

---

## PHASE 1: RUNTIME AUDIT SUMMARY

### Module Runtime Modes

| Module | Current Mode | Data Source | MSW Runtime | Status |
|--------|:------------:|:-----------:|:-----------:|:------:|
| Reference Cookbook | `cookbook` | MSW browser | ✅ Allowed | ✅ CORRECT |
| CondoFlow | `vertical-runtime` | Laravel API | ❌ Removed | ✅ CORRECT |
| Dynamic Forms | `vertical-runtime` | Laravel API | ✅ Active | ⚠️ HYBRID |

### Data Source Analysis

#### Reference Module
- **API client:** `src/modules/reference/api/index.ts` ✅
- **MSW handlers:** `src/modules/reference/mocks/handlers.ts` ✅
- **Runtime registration:** `browser.ts` includes `referenceHandlers` ✅
- **Status:** CORRECT — cookbook mode allows runtime MSW

#### CondoFlow Module
- **API client:** `src/modules/condoflow/api/condoflow.ts` ✅
- **MSW handlers:** `src/modules/condoflow/mocks/handlers.ts` ✅ (test-only)
- **Runtime registration:** REMOVED from `browser.ts` ✅
- **Status:** CORRECT — pure backend-backed vertical

#### Dynamic Forms Module
- **API client:** `src/modules/dynamic-forms/api/index.ts` ✅
- **MSW handlers:** `src/modules/dynamic-forms/tests/mocks/handlers.ts` ✅
- **Runtime registration:** `browser.ts` includes `formsHandlers` ⚠️
- **Status:** HYBRID — has real backend but MSW still active at runtime

---

## PHASE 2: CONDOFLOW PURIFICATION STATUS

### ✅ Purity Checklist

- [x] MSW handlers removed from runtime (`browser.ts`)
- [x] All pages use API composables
- [x] No hardcoded arrays in components
- [x] No local mock data in composables
- [x] All CRUD operations use apiClient
- [x] Types have index signature for TableRow compatibility

### Pages Verified

| Page | Composable | API Path | Status |
|------|-----------|----------|:------:|
| BuildingsIndexPage | `useBuildingsQuery` | `/condoflow/buildings` | ✅ |
| UnitsIndexPage | `useUnitsQuery` | `/condoflow/units` | ✅ |
| ResidentsIndexPage | `useResidentsQuery` | `/condoflow/residents` | ✅ |
| TicketsIndexPage | `useTicketsQuery` | `/condoflow/tickets` | ✅ |
| DashboardPage | `useDashboardQuery` | `/condoflow/dashboard` | ✅ |

### Backend Integration

| Endpoint | Method | Controller | Policy | Test |
|----------|:------:|-----------|:------:|:----:|
| `/api/condoflow/buildings` | GET | ✅ | ✅ | ✅ |
| `/api/condoflow/buildings` | POST | ✅ | ✅ | ✅ |
| `/api/condoflow/units` | GET | ✅ | ✅ | ✅ |
| `/api/condoflow/residents` | GET | ✅ | ✅ | ✅ |
| `/api/condoflow/tickets` | GET | ✅ | ✅ | ✅ |
| `/api/condoflow/dashboard` | GET | ✅ | ❌ | ✅ |

---

## PHASE 3: AUTHENTICATION FLOW ANALYSIS

### Experience Registry

| Experience | Key | Guest Entry | Auth Entry | Status |
|-----------|:---:|-------------|-----------|:------:|
| Platform | `platform` | `/login` | `/t/:tenantSlug/dashboard` | ✅ |
| CondoFlow | `condoflow` | `/condoflow/login` | `/t/:tenantSlug/condoflow` | ✅ |

### Route Ownership

- Platform `/login` → LoginPage.vue → uses `useExperienceAuth()` ✅
- CondoFlow `/condoflow/login` → CondoFlowLoginPage.vue → uses `useExperienceAuth()` ✅
- Router guard calls `resolveExperience(to)` ✅
- No hardcoded vertical names in guard ✅

### Auth Redirects

| Scenario | Expected | Actual | Status |
|----------|----------|--------|:------:|
| Unauthenticated → `/t/acme/dashboard` | Redirect to `/login` | ✅ | ✅ |
| Unauthenticated → `/t/acme/condoflow` | Redirect to `/condoflow/login` | ✅ | ✅ |
| Authenticated @ `/login` | Redirect to `/t/:slug/dashboard` | ⚠️ | VERIFY |
| Authenticated @ `/condoflow/login` | Redirect to `/t/:slug/condoflow` | ⚠️ | VERIFY |

---

## PHASE 4: SEEDER VALIDATION

### Seeder Hierarchy

```
DevelopmentBootstrapSeeder (orchestrator)
├── CoreSeeder (platform admin + tenants)
│   ├── Creates: admin@platform.com
│   └── Creates: tenants (acme)
└── CondoFlowSeeder (condo data)
    ├── Tenant: vista-mar
    ├── Buildings: Torre A, Torre B
    ├── Units: 5 units
    ├── Residents: 3 residents
    └── Tickets: 4 tickets
```

### Migration Status

| Domain | Path | Count | Status |
|--------|------|:-----:|:------:|
| core | `database/migrations/core/` | 5 | ✅ |
| platform | `database/migrations/platform/` | 2 | ✅ |
| dynamic_forms | `database/migrations/dynamic_forms/` | 3 | ✅ |
| condoflow | `database/migrations/condoflow/` | 4 | ✅ |
| observability | `database/migrations/observability/` | 2 | ✅ |

### setup.sh Verification

- [ ] Runs `migrate:fresh`
- [ ] Runs `db:seed`
- [ ] Creates admin user
- [ ] Creates tenants
- [ ] Creates CondoFlow data
- [ ] Frontend starts correctly
- [ ] No errors in console

**Status:** NEEDS VALIDATION

---

## PHASE 5: RUNTIME ENFORCEMENT

### Current Enforcement Mechanisms

- MSW handlers removed from CondoFlow runtime registration ✅
- Governance docs define forbidden patterns ✅
- No automated CI checks ❌

### Missing Safeguards

- [ ] ESLint rule: ban `setupWorker` imports in vertical modules
- [ ] ESLint rule: ban cross-vertical imports
- [ ] CI check: verify no MSW in production bundle
- [ ] Runtime assertion: vertical pages never use MSW

---

## PHASE 6: OBSERVABILITY STATUS

### Telescope Configuration

- Enabled: ✅
- Watchers: requests, queries, exceptions, jobs ✅
- Auth: Not exposed publicly ✅

### Tagging Status

- Tenant tagging: ❌ NOT IMPLEMENTED
- Experience tagging: ❌ NOT IMPLEMENTED
- Request tagging: ✅ (via URL path)

---

## PHASE 7: TEST COVERAGE

### Backend Tests (Pest)

| Suite | Tests | Status |
|-------|:-----:|:------:|
| CondoFlow | 17 | ✅ PASS |
| Core | Multiple | ✅ PASS |
| Total | 273 | ✅ PASS |

### Frontend Tests (Vitest)

| Suite | Tests | Status |
|-------|:-----:|:------:|
| CondoFlow | 6 | ✅ PASS |
| Experience | 6 | ✅ PASS |
| Total | 184 | ✅ PASS |

### Missing Tests

- [ ] E2E test: Platform login → dashboard
- [ ] E2E test: CondoFlow login → condo dashboard
- [ ] Integration test: CRUD persists across app restart
- [ ] Experience isolation test: No cross-vertical leakage

---

## DETECTED RISKS

| Risk | Severity | Mitigation |
|------|:--------:|-----------|
| Dynamic Forms still uses runtime MSW | Medium | Remove from browser.ts when forms stabilize |
| No CI enforcement of governance rules | High | Add ESLint rules + CI checks |
| Auth redirects not E2E tested | Medium | Add Playwright/Cypress tests |
| Telescope not tenant-tagged | Low | Add middleware to tag requests |
| No experience tagging in logs | Low | Add experience context to logging |
| CRUD persistence not integration tested | Medium | Add restart survival tests |

---

## TECHNICAL DEBT

| Debt | Impact | Effort |
|------|:------:|:------:|
| MSW devAuthHandlers still mock `/api/auth/*` | Low | Medium |
| No API versioning strategy | Low | Low |
| No rate limiting on CondoFlow endpoints | Medium | Low |
| Forms module in hybrid mode | Medium | Medium |
| No OpenAPI/Swagger docs | Medium | High |

---

## RECOMMENDED NEXT BLOCK

**GOV-3: CI Enforcement + E2E Testing**

Before MiniHIS, implement:

1. ESLint rules enforcing governance
2. CI pipeline checking:
   - No cross-vertical imports
   - No setupWorker in vertical modules
   - No MSW in production bundle
3. E2E test suite:
   - Platform auth flow
   - CondoFlow auth flow
   - CRUD persistence validation
4. Telescope tagging (tenant + experience)
