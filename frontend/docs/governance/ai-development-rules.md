# AI Development Rules

Rules for AI agents generating frontend code on Core Platform.
Follow these rules exactly. No exceptions without an ADR.

---

## Approved Primitives

Always import from these surfaces only:

```ts
import { AppButton, AppCard, AppSection, AppPageHeader, AppConfirmDialog,
         AppStatusChip, AppToolbarActions, AppPageLayout, AppEmptyState,
         AppLoadingState, AppErrorState, AppTextField, AppTextarea,
         AppSelect, AppCheckbox } from '@/shared/ui'

import { AppDataTable, AppTableToolbar, AppFilterBar,
         useTableState, useFilterState } from '@/shared/table'

import { isAxiosError } from '@/shared/api/client'
import type { PaginatedResponse, ApiResponse } from '@/shared/types'
```

**Never import** from `vuetify/components`, `@/shared/primitives`, `@/shared/feedback`, `@/shared/table/components/*`, `axios`.

---

## Page Composition Rules

Every routed page component must:

1. Use `<AppPageLayout>` as the root element
2. Pass `:loading` and `:error` props to `AppPageLayout`
3. Use `<script setup lang="ts">` (no Options API)
4. Import data via a composable (`useXQuery`) — never `axios.get()` in a page
5. Handle all three async states: loading, error, empty
6. Stay under 300 lines; extract sub-components if larger

```vue
<script setup lang="ts">
// ✅ Always this structure for a data page
const { data, isLoading, isError } = useXQuery(params)
</script>

<template>
  <AppPageLayout title="..." :loading="isLoading" :error="isError">
    <!-- content -->
  </AppPageLayout>
</template>
```

---

## CRUD Table Pages

For any list/table page:

```vue
<script setup lang="ts">
// Step 1: table state
const table = useTableState({
  defaultSort: { key: 'created_at', direction: 'desc' },
  defaultFilters: { search: null, status: null },
})

// Step 2: query — always pass table.queryParams
const { data, isLoading, isError } = useXQuery(table.queryParams)

// Step 3: mutations
const { mutateAsync: deleteX, isPending: isDeleting } = useDeleteXMutation()
</script>
```

Then in template:
- `<AppDataTable>` with all pagination/sort props wired to `table.*`
- `<AppTableToolbar>` inside the `#toolbar` slot
- `<AppFilterBar>` for filter controls
- `<AppConfirmDialog>` for any destructive action

---

## Mutation Pattern

```ts
// ✅ Correct mutation composable
export function useDeleteXMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteX(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['x'] }),
  })
}

// ✅ Correct usage in page
const { mutateAsync: deleteX, isPending: isDeleting } = useDeleteXMutation()

async function handleDelete(id: number) {
  await deleteX(id)
  confirmDelete.value = null
}
```

---

## Query Key Convention

```ts
// Format: ['module', 'resource', ...params]
queryKey: ['forms', 'list', queryParams]
queryKey: ['forms', 'detail', formId]
queryKey: ['reference', 'users', queryParams]
queryKey: ['reference', 'metrics']

// Invalidation — always use prefix (partial key)
qc.invalidateQueries({ queryKey: ['forms'] })          // all forms queries
qc.invalidateQueries({ queryKey: ['forms', 'list'] })  // list only
```

---

## MaybeRef Pattern

Any composable that accepts a reactive param must use `MaybeRef`:

```ts
import { toValue, type MaybeRef } from 'vue'

export function useXQuery(params: MaybeRef<Partial<TableQueryParams>> = {}) {
  return useQuery({
    queryKey: ['x', 'list', params] as const,
    queryFn: () => fetchX(toValue(params)),   // ← always toValue()
  })
}
```

---

## Status Display

Use `<AppStatusChip>` for all status badges:

```vue
<!-- ✅ Preset status -->
<AppStatusChip status="active" />
<AppStatusChip status="pending" />
<AppStatusChip status="published" />

<!-- ✅ Custom status -->
<AppStatusChip label="In Review" color="info" icon="mdi-eye-outline" />

<!-- ❌ Forbidden -->
<v-chip color="success">Active</v-chip>
```

---

## Async State Requirements

| State | Required output | Component |
|---|---|---|
| Loading | Spinner/skeleton, no content | AppPageLayout `:loading` or AppLoadingState |
| Error | Error message + retry button | AppPageLayout `:error` or AppErrorState |
| Empty | Helpful empty state, no blank space | AppEmptyState or `#empty` slot |
| Mutation in-flight | Button spinner, form disabled | `:loading` on AppButton |
| Success | Invalidate cache, clear dialog | `onSuccess` in mutation |

**No blank screens. No silent failures.**

---

## Module Scaffolding

When creating a new module `src/modules/<name>/`, always create:

```
api/index.ts          # fetchX, createX, updateX, deleteX
composables/index.ts  # useXQuery, useCreateXMutation, ...
data/fixtures.ts      # 5-15 realistic test records
mocks/handlers.ts     # MSW handlers + resetXHandlerState()
pages/XIndexPage.vue  # List page
routes/index.ts       # xRoutes array
tests/composables.test.ts
types/index.ts
```

Register in:
- `src/router/index.ts` — spread `...xRoutes`
- `src/shared/composables/useNavigation.ts` — add nav item
- `src/tests/mocks/server.ts` — spread `...xHandlers`

---

## Forbidden in AI-Generated Code

| Pattern | Reason |
|---|---|
| `import axios from 'axios'` outside api/ | Use `isAxiosError` from `@/shared/api/client` |
| `<v-btn>`, `<v-data-table>`, `<v-text-field>` | Use canonical wrappers |
| `import { VBtn } from 'vuetify/components'` | Use `@/shared/ui` |
| `const props = defineProps<...>()` when props unused in script | Drop the binding |
| `import X from '@/shared/primitives/X.vue'` | Use `@/shared/ui` barrel |
| `import X from '@/shared/table/components/X.vue'` | Use `@/shared/table` barrel |
| Cross-module imports `from '@/modules/other/...'` | Extract to `@/shared/` |
| Ad-hoc confirm dialogs | Use `<AppConfirmDialog>` |
| Inline loading/error HTML | Use `AppPageLayout` props or feedback components |

---

## Reference Implementation

The canonical reference for all patterns is `src/modules/reference/`.

- `pages/UsersExamplePage.vue` — full CRUD table pattern
- `pages/ApprovalWorkflowPage.vue` — status workflow pattern
- `pages/UploadExamplePage.vue` — upload UX pattern
- `pages/ReferenceDashboardPage.vue` — dashboard + metric cards pattern
- `composables/index.ts` — complete query/mutation composable set
- `mocks/handlers.ts` — MSW handler pattern with mutable state
- `tests/composables.test.ts` — composable integration test pattern

When unsure how to implement something, read the reference module first.
