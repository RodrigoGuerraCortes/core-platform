# CondoFlow Lite — Implementation Notes

## Patterns Reused (Zero Friction)

1. **CoreModuleServiceProvider** — declared policies + routesPath, zero boilerplate
2. **TenantRouteRegistrar** — single call wraps all routes with auth + tenant middleware stack
3. **BelongsToTenant** — trait on all models, auto-scoping works perfectly
4. **Gate::authorize** in controllers — consistent with Projects module
5. **FormRequest validation** — identical pattern, enums with Rule::enum()
6. **API Resources** — whenLoaded, whenCounted patterns work well
7. **AppDataTable** — server pagination, search, filters all composed declaratively
8. **TanStack Query** — composables follow exact same pattern as dynamic-forms
9. **MSW handlers** — shared paginate helper, stateless test mocks
10. **Pest feature tests** — helper functions, afterEach TenantContext clear

## Minor Friction Observed

1. **Factory namespacing** — had to put factories in `Database\Factories\CondoFlow\` subfolder to avoid class name collisions. Laravel's `newFactory()` override handles this fine.

2. **Route model binding with `$table` override** — `MaintenanceTicket` model needed explicit `$table = 'maintenance_tickets'` because Laravel inflects from class name. Standard Laravel behavior.

3. **ilike vs like** — used PostgreSQL `ilike` for case-insensitive search. This couples controllers to PostgreSQL. If MySQL support needed, would need a search abstraction. NOT extracted yet — premature.

4. **Dashboard controller is raw queries** — no caching, no service class. Acceptable for this vertical. Would need a DashboardService if complexity grows.

## What Should NOT Be Extracted Yet

- **No "CondoFlow package"** — module lives inside the monolith as intended
- **No "BaseController"** — controller patterns are simple enough to be explicit
- **No "SearchService"** — ilike queries are 3 lines, not worth abstracting
- **No "StatusMachine" package** — ticket transitions are a simple map in the frontend
- **No "ActivityLog" package** — timeline is placeholder, wait for real requirement

## Possible Future Modules (Do Not Build Now)

1. **Common Areas** — reservable spaces (gym, pool, BBQ)
2. **Announcements** — broadcast messages to residents
3. **Visitor Log** — entry/exit tracking
4. **Fee Collection** — monthly condo fees (requires payments integration)
5. **Document Repository** — meeting minutes, regulations

## Test Coverage

| Layer | Tests | Assertions |
|-------|-------|------------|
| Backend (Pest) | 17 | 37 |
| Frontend (Vitest) | 6 | 6+ |

## Key Design Decisions

1. **Tickets are polymorphic-optional** — unit_id and resident_id are both nullable. A ticket can exist for common area issues without a specific unit.

2. **Members can create tickets** — unlike buildings/units/residents (admin-only create), any tenant member can submit a maintenance ticket. Only admins can update/close them.

3. **No nested routes** — `/api/condoflow/units` not `/api/condoflow/buildings/:id/units`. Flat routes are simpler and the building_id filter handles the relationship.

4. **Dashboard is a single endpoint** — not multiple API calls. Reduces frontend complexity and allows backend to optimize queries.
