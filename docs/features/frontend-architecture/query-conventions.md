# Query Conventions

**Block:** 6.3 — Frontend Architecture & Governance  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

TanStack Query (Vue Query) is the official data-fetching and cache management layer. It replaces manual `ref`/`reactive` loading state, manual cache invalidation, and ad-hoc refetch logic. All server-state management goes through TanStack Query.

---

## Why TanStack Query

| Concern | Without TQ | With TanStack Query |
|---|---|---|
| Loading state | Manual `isLoading` ref | Automatic |
| Error state | Manual `error` ref | Automatic |
| Cache invalidation | Manual, error-prone | `invalidateQueries()` |
| Stale-while-revalidate | Manual | Built-in |
| Background refetch | Manual | Built-in |
| Pagination | Manual state | Built-in with `keepPreviousData` |
| Deduplication | Not available | Automatic |

---

## Query Key Strategy

Query keys are the foundation of the cache. They must be structured, typed, and centralized per module.

Every module defines a `queryKeys.ts` file:

```typescript
// modules/projects/queryKeys.ts

import type { ProjectFilters } from './types/filters'

export const projectKeys = {
  all: ['projects'] as const,
  lists: () => [...projectKeys.all, 'list'] as const,
  list: (filters?: Ref<ProjectFilters> | ProjectFilters) =>
    [...projectKeys.lists(), filters] as const,
  details: () => [...projectKeys.all, 'detail'] as const,
  detail: (id: string) => [...projectKeys.details(), id] as const,
}
```

Rules:
- Query keys are **always arrays** — never strings
- Keys follow a **hierarchical structure**: `[scope, type, ...params]`
- Invalidating `projectKeys.lists()` invalidates all list variants automatically
- Keys that include filters must include the full filter object, not individual fields

---

## Standard Query Key Hierarchy

```
['projects']                          → all project data
['projects', 'list']                  → all list queries (any filter)
['projects', 'list', { status: 'active' }]  → filtered list
['projects', 'detail']                → all detail queries
['projects', 'detail', 'proj-123']   → single project
```

This enables surgical invalidation:
- Create/update → invalidate `projectKeys.lists()`
- Delete → invalidate `projectKeys.lists()` + remove `projectKeys.detail(id)`

---

## Query Configuration Defaults

Global defaults are set at the QueryClient level, not per-query:

```typescript
// shared/api/queryClient.ts

import { QueryClient } from '@tanstack/vue-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 2,     // 2 minutes
      gcTime: 1000 * 60 * 10,       // 10 minutes
      retry: 1,
      refetchOnWindowFocus: false,  // Explicit opt-in per query if needed
    },
  },
})
```

Per-query overrides are allowed only when there is a documented reason (e.g., real-time data needing short `staleTime`).

---

## Pagination

Paginated queries use TanStack Query's `keepPreviousData` behavior:

```typescript
export function useProjectList(filters: Ref<ProjectFilters>, page: Ref<number>) {
  return useQuery({
    queryKey: projectKeys.list(computed(() => ({ ...filters.value, page: page.value }))),
    queryFn: () => fetchProjects({ ...filters.value, page: page.value }),
    placeholderData: keepPreviousData,  // Smooth pagination UX
  })
}
```

Pagination state (`page`, `perPage`) is passed as parameters into the composable — it is not stored inside the composable.

---

## Mutation + Invalidation Pattern

After a mutation succeeds, invalidate only the minimum required keys:

```typescript
useMutation({
  mutationFn: createProject,
  onSuccess: (data) => {
    // Invalidate lists so new item appears
    queryClient.invalidateQueries({ queryKey: projectKeys.lists() })

    // Optionally seed the detail cache immediately (avoids extra fetch)
    queryClient.setQueryData(projectKeys.detail(data.data.id), data)
  },
})
```

Invalidation strategy by operation:

| Operation | Invalidate |
|---|---|
| Create | `{module}.lists()` |
| Update | `{module}.lists()` + `{module}.detail(id)` |
| Delete | `{module}.lists()`, remove `{module}.detail(id)` |

---

## Dependent Queries

When a query depends on the result of another query, use the `enabled` option:

```typescript
const { data: project } = useQuery({
  queryKey: projectKeys.detail(projectId),
  queryFn: () => fetchProject(projectId),
})

const { data: tasks } = useQuery({
  queryKey: taskKeys.list(projectId),
  queryFn: () => fetchTasksForProject(projectId),
  enabled: computed(() => !!project.value),
})
```

Never chain queries using `watch()` + `ref()` when TanStack Query's `enabled` suffices.

---

## Optimistic Updates

Optimistic updates are allowed only for low-risk, reversible mutations (e.g., toggling a status). They must include a proper `onError` rollback:

```typescript
useMutation({
  mutationFn: updateProject,
  onMutate: async (variables) => {
    await queryClient.cancelQueries({ queryKey: projectKeys.detail(variables.id) })
    const previous = queryClient.getQueryData(projectKeys.detail(variables.id))
    queryClient.setQueryData(projectKeys.detail(variables.id), (old) => ({
      ...old,
      data: { ...old.data, ...variables },
    }))
    return { previous }
  },
  onError: (_err, variables, context) => {
    queryClient.setQueryData(projectKeys.detail(variables.id), context?.previous)
  },
  onSettled: (_data, _err, variables) => {
    queryClient.invalidateQueries({ queryKey: projectKeys.detail(variables.id) })
  },
})
```

If the rollback logic cannot be implemented cleanly, do not use optimistic updates.

---

## Banned Patterns

- Global `ref([])` arrays hydrated with manual `axios.get()` calls
- `watch()` chains triggering refetches instead of query key reactivity
- Cache invalidation by calling `refetch()` on a composable from a parent component
- Storing server data in Pinia (server state belongs in the query cache, not Pinia)
- Queries without typed return shapes
- Per-query `retry: 3` without documented justification
