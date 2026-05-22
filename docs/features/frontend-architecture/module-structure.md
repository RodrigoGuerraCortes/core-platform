# Frontend Module Structure

**Block:** 6.3 — Frontend Architecture & Governance  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Every frontend module mirrors the backend's modular monolith philosophy. Each feature domain owns its own directory, types, API access, and composables. No cross-module imports except through the `shared/` boundary.

---

## Canonical Module Directory Layout

```
src/
  modules/
    {module}/
      api/            # API call functions (no Axios usage elsewhere)
      composables/    # Vue composables scoped to this module
      components/     # Vue components scoped to this module
      types/          # TypeScript types and interfaces
      pages/          # Route-level views (one per route)
      index.ts        # Public surface — explicit exports only
  shared/
    ui/               # Generic reusable UI components (no business logic)
    lib/              # Utility functions, formatters, constants
    api/              # Shared API client (axios instance + interceptors)
    types/            # Global types (Tenant, Pagination, ApiResponse, etc.)
    composables/      # Cross-cutting composables (useTenant, useAuth, etc.)
```

---

## Module Boundary Rules

1. **No module imports from another module's internals.** All inter-module access is through `index.ts` public surface only.
2. **No module imports directly from `shared/ui` for business-aware components.** `shared/ui` is for generic presentational elements only.
3. **No inline API calls inside components.** All API access goes through `modules/{module}/api/`.
4. **No shared mutable state across modules.** Each module manages its own local state.

---

## `index.ts` — Public Surface Contract

Each module exposes only what other modules or route files need:

```typescript
// modules/projects/index.ts

export { useProjectList } from './composables/useProjectList'
export { useProject } from './composables/useProject'
export type { Project, ProjectListItem } from './types/project'
// Do NOT export internal API functions, internal helpers, or component internals
```

Anything not exported from `index.ts` is **private to the module**.

---

## Pages vs Components

| Concern | Location |
|---|---|
| Route entry point | `modules/{module}/pages/` |
| Module-specific UI | `modules/{module}/components/` |
| Generic reusable UI | `shared/ui/` |
| Business-aware shared | Not allowed — belongs to owning module |

Pages are thin. They compose composables and components. They contain no data-fetching logic directly.

```vue
<!-- modules/projects/pages/ProjectListPage.vue -->
<script setup lang="ts">
import { useProjectList } from '../composables/useProjectList'
import ProjectTable from '../components/ProjectTable.vue'

const { projects, isLoading, error } = useProjectList()
</script>

<template>
  <ProjectTable :projects="projects" :loading="isLoading" :error="error" />
</template>
```

---

## TypeScript Types Per Module

Each module defines its own types in `modules/{module}/types/`. Types are never inferred from API responses directly inside components.

```
modules/projects/types/
  project.ts       # Project, ProjectListItem, ProjectStatus
  filters.ts       # ProjectFilters, ProjectSortField
  forms.ts         # CreateProjectPayload, UpdateProjectPayload
```

Global types (used by multiple modules) live in `shared/types/`:

```
shared/types/
  api.ts           # ApiResponse<T>, PaginatedResponse<T>
  tenant.ts        # TenantContext
  pagination.ts    # PaginationMeta, PaginationLinks
```

---

## Naming Conventions

| Entity | Convention | Example |
|---|---|---|
| Composable | `use{Noun}{Verb}` | `useProjectList`, `useProjectCreate` |
| API function | `{verb}{Noun}` | `fetchProjects`, `createProject` |
| Component | `{Module}{Role}` | `ProjectTable`, `ProjectForm` |
| Page | `{Noun}Page` | `ProjectListPage`, `ProjectDetailPage` |
| Type | PascalCase noun | `Project`, `ProjectFilters` |
| Payload type | `{Verb}{Noun}Payload` | `CreateProjectPayload` |

---

## Banned Patterns

- `import { something } from '../../other-module/internals'` — cross-module internal access
- API calls inside `<script setup>` directly
- Business logic inside `shared/ui/` components
- Inline `ref()` state that should be owned by a composable
- Type assertions (`as SomeType`) on raw API responses

---

## Module Registration

Modules register their routes in a central router file, not inside individual modules:

```typescript
// router/index.ts
import { projectsRoutes } from '@/modules/projects'
import { identityRoutes } from '@/modules/identity'

const routes = [
  ...projectsRoutes,
  ...identityRoutes,
]
```

Each module exports its route definitions from `index.ts`.
