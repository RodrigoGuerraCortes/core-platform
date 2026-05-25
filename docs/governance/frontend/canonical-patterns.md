# Canonical Patterns

Approved patterns for building features on Core Platform. Follow these exactly.
Deviating requires an ADR.

---

## 1. Module Structure

```
src/modules/<name>/
├── api/            # HTTP functions — thin wrappers over apiClient
├── components/     # Dumb/presentational components specific to this module
├── composables/    # TanStack Query composables (useXQuery, useXMutation)
├── mocks/          # MSW handlers for tests (handlers.ts + resetState fn)
├── pages/          # Routed page components — orchestrate composables + layout
├── routes/         # Route records (index.ts exports moduleRoutes array)
├── tests/          # Vitest tests (composables.test.ts, components.test.ts)
├── types/          # TypeScript interfaces + domain types
└── validation/     # Zod schemas and error mappers
```

**Dependency direction:** pages → composables → api → shared/api/client

No cross-module imports. Shared contracts go through `src/shared/`.

---

## 2. CRUD Page Pattern

```vue
<script setup lang="ts">
// 1. Table state — owns pagination, sort, filters
const table = useTableState({
  defaultSort: { key: 'created_at', direction: 'desc' },
  defaultFilters: { search: null, status: null },
})

// 2. Query — reactive to table state
const { data, isLoading, isError } = useXQuery(table.queryParams)

// 3. Mutations
const { mutateAsync: deleteItem, isPending: isDeleting } = useDeleteXMutation()

// 4. Local dialog state
const confirmDelete = ref<X | null>(null)
</script>

<template>
  <AppPageLayout title="Items" :loading="isLoading" :error="isError">
    <template #actions>
      <AppButton variant="primary" @click="openCreate">New Item</AppButton>
    </template>

    <AppDataTable
      :columns="columns"
      :rows="data?.data ?? []"
      :total="data?.meta.total ?? 0"
      :page="table.page.value"
      :per-page="table.perPage.value"
      :sort="table.sort.value"
      :loading="isLoading"
      :error="isError"
      @update:page="table.setPage"
      @update:per-page="table.setPerPage"
      @update:sort="table.setSort"
    >
      <template #toolbar>
        <AppTableToolbar title="Items" :count="data?.meta.total">
          <template #actions>
            <AppFilterBar :model-value="table.filters.value" :fields="filterFields"
              @update:model-value="table.setFilters" />
          </template>
        </AppTableToolbar>
      </template>
    </AppDataTable>

    <AppConfirmDialog
      v-model="!!confirmDelete"
      title="Delete item?"
      description="This cannot be undone."
      :loading="isDeleting"
      @confirm="handleDelete"
    />
  </AppPageLayout>
</template>
```

---

## 3. API Layer Pattern

```ts
// modules/example/api/index.ts
import apiClient from '@/shared/api/client'
import type { PaginatedResponse } from '@/shared/types'
import type { TableQueryParams } from '@/shared/table'
import type { Example } from '../types'

export async function fetchExamples(
  params: Partial<TableQueryParams> = {},
): Promise<PaginatedResponse<Example>> {
  const { data } = await apiClient.get<PaginatedResponse<Example>>('/examples', {
    params: Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''),
    ),
  })
  return data
}
```

---

## 4. Composable Pattern

```ts
// modules/example/composables/index.ts
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { toValue, type MaybeRef } from 'vue'
import type { TableQueryParams } from '@/shared/table'
import { fetchExamples, deleteExample } from '../api'

export function useExamplesQuery(params: MaybeRef<Partial<TableQueryParams>> = {}) {
  return useQuery({
    queryKey: ['example', 'list', params] as const,
    queryFn: () => fetchExamples(toValue(params)),
    staleTime: 30_000,
  })
}

export function useDeleteExampleMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteExample(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['example'] }),
  })
}
```

---

## 5. MSW Handler Pattern

```ts
// modules/example/mocks/handlers.ts
import { http, HttpResponse } from 'msw'
import { EXAMPLES } from '../data/fixtures'

let examples = [...EXAMPLES]

export function resetExampleHandlerState(): void {
  examples = [...EXAMPLES]
}

export const exampleHandlers = [
  http.get('/api/examples', ({ request }) => {
    const url = new URL(request.url)
    const page = Number(url.searchParams.get('page') ?? 1)
    const perPage = Number(url.searchParams.get('per_page') ?? 15)
    const slice = examples.slice((page - 1) * perPage, page * perPage)
    return HttpResponse.json({
      data: slice,
      meta: { current_page: page, per_page: perPage, total: examples.length, last_page: Math.ceil(examples.length / perPage) },
    })
  }),
]
```

---

## 6. Query Cache Key Convention

```
['module-name', 'resource', params?]
```

Examples:
- `['reference', 'users', queryParams]`
- `['forms', 'list', queryParams]`
- `['forms', 'detail', formId]`
- `['reference', 'metrics']`

Invalidation uses prefix: `{ queryKey: ['reference'] }` invalidates all reference queries.

---

## 7. Async State Canon

Every async screen must render one of these states:

| State | Component | When |
|---|---|---|
| Loading | `AppLoadingState` or `:loading` prop on AppPageLayout | data.isLoading |
| Empty | `AppEmptyState` or `#empty` slot on AppDataTable | zero results |
| Error | `AppErrorState` or `:error` prop on AppPageLayout | isError |
| Retry | `@retry` emit / refetch button | after error |
| In-flight | `:loading` on AppButton / `isPending` on mutation | during mutate |

**No blank screens. No silent failures.**
