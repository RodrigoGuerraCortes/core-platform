# Reference Module Rules

## Status: Mandatory (enforced by ESLint + PR review)

---

## 1. Purpose

The `src/modules/reference/` module is a **frontend-only demonstration cookbook**.  
It exists to show correct patterns for building module features — API calls, composables, MSW mocking, UI assembly — without requiring a running Laravel backend.

---

## 2. Core Invariants

### 2.1 Never couple to real Laravel routes

The reference module **must never** depend on routes that exist in `routes/api.php`.  
All API calls are intercepted by MSW handlers (`src/modules/reference/mocks/handlers.ts`).

❌ DO NOT add `/api/reference/*` routes to Laravel.  
❌ DO NOT call a real HTTP endpoint that only works with a running backend.  
✅ DO add an MSW handler for every new API call.

### 2.2 MSW handlers are the source of truth for data shape

The fixtures in `src/modules/reference/mocks/fixtures.ts` define the canonical data shape for `ReferenceUser`, `ReferenceApproval`, and `ReferenceMetrics`. The TypeScript types in `src/modules/reference/types/index.ts` must stay in sync with those fixtures.

### 2.3 No real side effects

Reference module handlers must return fixture data — they must not write to localStorage, Pinia stores shared with other modules, or make outbound HTTP calls.

---

## 3. MSW Architecture

```
src/mocks/browser.ts          ← dev-only, started in main.ts (import.meta.env.DEV)
src/tests/mocks/server.ts     ← Vitest only (msw/node setupServer)
src/modules/reference/mocks/handlers.ts   ← shared by both
src/modules/reference/mocks/fixtures.ts   ← in-memory data store for handlers
```

**Rule:** Import handlers into **both** `browser.ts` and `server.ts`. Never import `msw/node` in browser code or `msw/browser` in test code.

### 3.1 Adding a new reference endpoint

1. Add the TypeScript type to `types/index.ts`
2. Add a fixture to `fixtures.ts`
3. Add an `http.*` handler in `handlers.ts`, export it in `referenceHandlers`
4. Add the API function to `api/index.ts` (uses `apiClient` — MSW intercepts it)
5. Write a Vitest test that verifies the handler responds correctly

---

## 4. Module File Layout

```
src/modules/reference/
  api/
    index.ts          ← apiClient calls (intercepted by MSW in both dev + test)
  composables/
    useReferenceUsers.ts
    useReferenceApprovals.ts
    useReferenceMetrics.ts
  mocks/
    handlers.ts       ← MSW http.* handlers (importable from msw/browser OR msw/node)
    fixtures.ts       ← In-memory fixture data
  pages/
    ReferenceDashboardPage.vue
    UsersExamplePage.vue
    ApprovalWorkflowPage.vue
    UploadExamplePage.vue
    UserDetailPage.vue
    ApprovalDetailPage.vue
  types/
    index.ts
```

---

## 5. What the Reference Module Demonstrates

| Pattern | Implementation |
|---|---|
| TanStack Query with MaybeRef | `useReferenceUsers`, `useReferenceApprovals` |
| Pagination + filtering | `UsersExamplePage` + `useTableState` |
| Approval workflow UI | `ApprovalWorkflowPage` + `AppConfirmDialog` |
| File upload with progress | `UploadExamplePage` |
| Enterprise detail layout | `UserDetailPage` + `ApprovalDetailPage` |
| AppDetailLayout with all slots | `UserDetailPage` |
| AppPermissionBoundary | `ApprovalDetailPage` |
| AppActivityTimeline | Both detail pages |
| AppEmptyState presets | `UsersExamplePage` (no-results, filtered) |

---

## 6. Testing Requirements

Every handler must have at least one Vitest test that:
- Verifies the MSW handler intercepts the request (no real network call)
- Asserts the response shape matches the TypeScript type
- Tests error states (404 for non-existent IDs, etc.)

Tests live in `src/modules/reference/tests/` and use `server.use()` from `src/tests/mocks/server.ts`.

---

## 7. What NOT to Put Here

- **Business logic** — use the appropriate domain module (e.g. `dynamic-forms`, `auth`)
- **Shared UI components** — those go in `src/shared/ui/`
- **Real HTTP calls** — the reference module is always MSW-backed

---

## Related Docs

- [ADR-008 — Prompt Infrastructure](../adr/ADR-008-prompt-infrastructure-lightweight-first.md)
- [MSW Setup Guide](./msw-browser-worker.md)
- [API Conventions](../arquitecture/API_CONVENTIONS.md)
