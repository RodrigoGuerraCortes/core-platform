# Shared Boundaries

**Block:** 6.3 — Frontend Architecture & Governance  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Shared code is the most dangerous surface in a modular frontend. If left ungoverned, it becomes a dumping ground for miscellaneous utilities and creates invisible coupling between modules. This document defines exactly what may live in `shared/` and the rules for placing things there.

---

## The `shared/` Contract

Code lives in `shared/` if and only if **two or more distinct modules use it** and **it contains no business logic specific to any single module**.

If only one module uses something, it stays in that module. Even if it feels "generic".

---

## `shared/` Directory Structure

```
shared/
  api/
    client.ts           # Axios instance + interceptors
    errors.ts           # ApiError type + normalizeApiError()
    queryClient.ts      # TanStack Query client instance + defaults
  composables/
    useTenantContext.ts # Reactive tenant ID from useTenantStore
    useAuth.ts          # Current user, token, permissions
    usePagination.ts    # Pagination state helper
    useNotification.ts  # Trigger toast notifications
    useConfirmDialog.ts # Trigger confirmation dialogs
  stores/
    auth.ts             # useAuthStore
    tenant.ts           # useTenantStore
    notifications.ts    # useNotificationStore
  types/
    api.ts              # ApiResponse<T>, PaginatedResponse<T>, ApiError
    tenant.ts           # TenantContext
    pagination.ts       # PaginationMeta, PaginationLinks
    auth.ts             # AuthUser, Permission
  ui/
    (generic UI components — see below)
  lib/
    (utility functions — see below)
```

---

## `shared/ui/` — Generic UI Components

Components in `shared/ui/` must be:

- **Purely presentational** — no API calls, no Pinia imports, no business logic
- **Domain-agnostic** — they have no knowledge of modules like `projects` or `identity`
- **Prop-driven** — they receive all data via props and emit events

Approved candidates for `shared/ui/`:

```
shared/ui/
  FormField.vue         # Label + input slot + error messages
  DataTable.vue         # Generic paginated table (column definitions via props)
  LoadingSpinner.vue    # Consistent loading indicator
  ErrorAlert.vue        # Consistent error display
  EmptyState.vue        # Consistent empty list display
  ConfirmDialog.vue     # Confirmation modal
  Pagination.vue        # Pagination control (emits page change)
  Badge.vue             # Status badge
  PageHeader.vue        # Page title + breadcrumb slot
```

What does NOT belong in `shared/ui/`:

- `ProjectStatusBadge.vue` — belongs in `modules/projects/components/`
- `UserAvatar.vue` with user-fetching logic — business logic disqualifies it
- Any component that calls an API or imports from a module

---

## `shared/lib/` — Utility Functions

Pure functions and constants with no side effects and no Vue/Pinia dependencies:

```
shared/lib/
  formatters.ts         # formatDate, formatCurrency, formatFileSize
  validators.ts         # isEmail, isUrl, isUuid (reusable Zod refinements)
  constants.ts          # APP_NAME, DEFAULT_PAGE_SIZE, DATE_FORMAT
  pagination.ts         # buildPaginationParams(page, perPage)
```

Rules for `shared/lib/`:
- Every function must be pure and unit-testable in isolation
- No Vue refs, no reactive values, no composable calls
- No imports from modules or from `shared/composables/`

---

## The `shared/` Admission Gate

Before adding anything to `shared/`, answer these questions:

1. **Is it used by 2+ modules?** If no — keep it in its owning module.
2. **Does it contain business logic from a specific domain?** If yes — it belongs in that module.
3. **Does it import from any module?** If yes — it cannot be in `shared/`.
4. **Is it a generic UI component that fetches data?** If yes — split the data fetching out, make it presentational.

If all four questions pass: the code may move to `shared/`.

---

## Module-to-Module Communication

Modules must not import directly from each other's internals. When one module needs data from another:

### Option A — Via the query cache (preferred)

Module B reads Module A's data from the TanStack Query cache using Module A's public query keys:

```typescript
// modules/tasks/composables/useProjectContext.ts
import { projectKeys } from '@/modules/projects'  // imported from index.ts

const project = useQueryClient().getQueryData(projectKeys.detail(projectId))
```

### Option B — Via URL params / router

Parent modules pass IDs to child modules via route parameters. The child module fetches its own data.

### Option C — Via Pinia (cross-cutting state only)

If both modules genuinely need the same global state, that state belongs in a `shared/stores/` store, not in either module.

**Prohibited:** Importing a composable from one module directly into another module's composable.

---

## Import Direction Rules

```
modules/projects/  →  shared/           ✓ allowed
modules/projects/  →  modules/tasks/    ✗ never (use index.ts + query cache)
shared/            →  modules/projects/ ✗ never
shared/ui/         →  shared/stores/    ✗ never (ui must be purely presentational)
shared/lib/        →  shared/           ✗ never (lib must be pure functions)
```

---

## Graduated Boundary Violations

The following violations are listed from least to most severe. All are banned.

| Severity | Violation |
|---|---|
| Low | Using a module-internal helper in shared/lib/ |
| Medium | Importing a module composable directly from another module |
| High | Shared/ui component that calls an API |
| Critical | Module that reads another module's Pinia store state directly |

---

## Boundary Audit (Future Tooling)

ESLint rules or custom import boundary rules (e.g., `eslint-plugin-boundaries`) should enforce these constraints automatically. Until automated, the conventions in this document govern code review decisions.
