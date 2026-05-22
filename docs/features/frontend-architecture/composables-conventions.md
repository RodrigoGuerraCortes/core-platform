# Composables Conventions

**Block:** 6.3 — Frontend Architecture & Governance  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Composables are the primary abstraction unit for frontend logic. They encapsulate state, side effects, and API calls. Components remain thin: they consume composables and render output.

This is not optional. Business logic in components is a governance violation.

---

## Composable Placement Rules

| Scope | Location |
|---|---|
| Module-specific | `modules/{module}/composables/` |
| Cross-cutting platform | `shared/composables/` |
| Tenant/auth context | `shared/composables/` (always) |

No composable may be defined inline inside a component file.

---

## Naming Convention

```
use{Noun}             → state-only composable   → useTenantContext
use{Noun}List         → collection query         → useProjectList
use{Noun}             → single-record query      → useProject(id)
use{Noun}Create       → create mutation          → useProjectCreate
use{Noun}Update       → update mutation          → useProjectUpdate
use{Noun}Delete       → delete mutation          → useProjectDelete
use{Noun}Form         → form state + validation  → useProjectForm
```

Composables that handle a mutation are always named with a verb suffix, not a noun alone.

---

## Query Composable Shape

Read-only data fetching composables expose a consistent contract:

```typescript
// modules/projects/composables/useProjectList.ts

import { useQuery } from '@tanstack/vue-query'
import { fetchProjects } from '../api/projects'
import { projectKeys } from '../queryKeys'
import type { ProjectFilters } from '../types/filters'

export function useProjectList(filters?: Ref<ProjectFilters>) {
  const query = useQuery({
    queryKey: projectKeys.list(filters),
    queryFn: () => fetchProjects(filters?.value),
  })

  return {
    projects: computed(() => query.data.value?.data ?? []),
    pagination: computed(() => query.data.value?.meta),
    isLoading: query.isLoading,
    isError: query.isError,
    error: query.error,
    refetch: query.refetch,
  }
}
```

Rule: Composables never return the raw `query` object. They return named, typed values.

---

## Mutation Composable Shape

Write operations expose a consistent contract:

```typescript
// modules/projects/composables/useProjectCreate.ts

import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { createProject } from '../api/projects'
import { projectKeys } from '../queryKeys'
import type { CreateProjectPayload } from '../types/forms'
import type { ApiError } from '@/shared/types/api'

export function useProjectCreate() {
  const queryClient = useQueryClient()

  const mutation = useMutation({
    mutationFn: (payload: CreateProjectPayload) => createProject(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: projectKeys.lists() })
    },
  })

  return {
    createProject: mutation.mutateAsync,
    isCreating: mutation.isPending,
    createError: mutation.error as Ref<ApiError | null>,
  }
}
```

Rule: `mutateAsync` is exposed (not `mutate`) so callers can `await` and handle errors in forms.

---

## Form Composable Shape

Form composables wrap validation and submission state. They do not embed API calls directly — they compose a mutation composable:

```typescript
// modules/projects/composables/useProjectForm.ts

import { reactive, ref } from 'vue'
import { useProjectCreate } from './useProjectCreate'
import type { CreateProjectPayload } from '../types/forms'

export function useProjectForm() {
  const { createProject, isCreating, createError } = useProjectCreate()

  const form = reactive<CreateProjectPayload>({
    name: '',
    description: '',
  })

  const validationErrors = ref<Record<string, string[]>>({})

  async function submit() {
    validationErrors.value = {}
    try {
      await createProject(form)
    } catch (error: unknown) {
      if (isApiError(error) && error.errors) {
        validationErrors.value = error.errors
      }
      throw error
    }
  }

  return {
    form,
    submit,
    isSubmitting: isCreating,
    validationErrors,
    serverError: createError,
  }
}
```

---

## Cross-Cutting Composables (shared/)

Platform-level composables available everywhere:

```
shared/composables/
  useTenantContext.ts     # Current tenant ID and metadata
  useAuth.ts              # Current user, roles, permissions
  usePagination.ts        # Pagination state and page navigation
  useNotification.ts      # Toast/alert notifications
  useConfirmDialog.ts     # Confirmation dialog trigger
```

These composables never import from any module. They depend only on `shared/` internals.

---

## Rules

1. **One concern per composable.** A composable either queries, mutates, or manages form state — never all three.
2. **No business logic in components.** Components call composables; they do not contain `if/else` branches based on API data.
3. **All composables are TypeScript.** No `any` types in composable return shapes.
4. **Composables do not render.** No template logic, no `document` access, no Vue `<template>` refs inside composables.
5. **Query and mutation composables are always separate files.** `useProjectList` and `useProjectCreate` are never merged into one composable.

---

## Composable Testing Expectations

Every composable must be independently testable without mounting a component. Composables that depend on `useTenantContext` mock it via dependency injection or a test store setup.

Tests live at: `modules/{module}/composables/__tests__/`

---

## Banned Patterns

- `const data = ref([])` + manual `axios.get()` inside a component
- Composables that accept a component instance as a parameter
- Composables that directly mutate props
- Composables that mix query + mutation + form state in one function
- Anonymous composables defined as inline arrow functions inside `<script setup>`
