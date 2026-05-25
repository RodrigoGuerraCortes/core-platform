# Query Patterns

TanStack Query conventions for Core Platform.
All server state lives in the query cache. No Pinia for server data.

---

## Query Key Structure

```
['module', 'resource', ...differentiators]
```

| Example | Scope |
|---|---|
| `['forms', 'list', queryParams]` | paginated list, varies by filters |
| `['forms', 'detail', formId]` | single record |
| `['forms', 'versions', formId]` | sub-resource list |
| `['reference', 'metrics']` | derived/computed data |

### Rules

- Always 2+ segments: `['module', 'resource']` minimum
- Params that change the result go at the end
- Use `as const` to preserve tuple type for invalidation matching

```ts
queryKey: ['forms', 'detail', formId] as const
```

---

## MaybeRef Params

Composables that accept reactive params use `MaybeRef<T>`:

```ts
import { toValue, type MaybeRef } from 'vue'

export function useFormsQuery(params: MaybeRef<Partial<TableQueryParams>> = {}) {
  return useQuery({
    queryKey: ['forms', 'list', params] as const,
    queryFn: () => fetchForms(toValue(params)),  // ← unwrap here, not above
    staleTime: 30_000,
  })
}
```

**Never** pass a ref object into the query function. Always `toValue()` inside `queryFn`.

---

## staleTime Conventions

| Data type | staleTime |
|---|---|
| User-facing lists | 30 seconds |
| Detail / single record | 60 seconds |
| Metrics / aggregates | 60 seconds |
| Auth/session | 0 (always fresh) |

---

## Mutation Invalidation

Always invalidate by key prefix on success:

```ts
onSuccess: () => {
  // Invalidate all queries under this module
  qc.invalidateQueries({ queryKey: ['forms'] })

  // Or scope to just the list
  qc.invalidateQueries({ queryKey: ['forms', 'list'] })

  // Multiple resources (e.g. deletion affects metrics)
  qc.invalidateQueries({ queryKey: ['forms'] })
  qc.invalidateQueries({ queryKey: ['reference', 'metrics'] })
}
```

Do not call `qc.refetchQueries()` — `invalidateQueries` is sufficient; TanStack Query will refetch on next access.

---

## Optimistic Updates

Only use for high-frequency interactions (e.g. toggle, reorder). Not required by default.

```ts
onMutate: async (variables) => {
  await qc.cancelQueries({ queryKey: ['x', 'list'] })
  const snapshot = qc.getQueryData(['x', 'list'])
  qc.setQueryData(['x', 'list'], (old) => applyOptimisticChange(old, variables))
  return { snapshot }
},
onError: (_err, _vars, ctx) => {
  if (ctx?.snapshot) qc.setQueryData(['x', 'list'], ctx.snapshot)
},
```

---

## Pagination

All list endpoints use the `TableQueryParams` shape:

```ts
interface TableQueryParams {
  page: number
  per_page: number
  search?: string | null
  sort_by?: string | null
  sort_dir?: 'asc' | 'desc' | null
  [key: string]: unknown  // additional filters
}
```

All list API responses use `PaginatedResponse<T>`:

```ts
interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}
```

Pass `null`/`undefined` filter values through `Object.fromEntries(filter)` to strip them before sending to the API.

---

## Filter State

Use `useTableState` for table-bound filters. Use `useFilterState` for standalone filter bars.

```ts
const table = useTableState({
  defaultSort: { key: 'created_at', direction: 'desc' },
  defaultFilters: { search: null, status: null },
})

// table.queryParams is a computed ref — pass directly to useXQuery
const { data } = useXQuery(table.queryParams)
```

`useTableState` automatically resets `page` to 1 when sort or filters change.
