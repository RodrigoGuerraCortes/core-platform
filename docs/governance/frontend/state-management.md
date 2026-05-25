# State Management

Rules for managing state in Core Platform frontend.

---

## State Taxonomy

| State type | Where it lives | Tool |
|---|---|---|
| Server data (lists, details) | Query cache | TanStack Query |
| Auth session | Pinia `useAuthStore` | Pinia |
| Tenant context | Pinia `useTenantStore` | Pinia |
| Table UI (page, sort, filters) | `useTableState` composable | `ref` |
| Form input | `ref` / `reactive` | Vue |
| Dialog open/close | `ref<T | null>` | Vue |
| Upload queue | `useUploadManager` composable | `ref` |

---

## Server Data — TanStack Query

Use `useQuery` for all data fetched from the API. Never store server data in Pinia.

```ts
// ✅ Correct — server data in query cache
const { data, isLoading, isError } = useFormsQuery(params)

// ❌ Forbidden — server data in Pinia
const formStore = useFormStore()
formStore.fetchForms()
```

---

## Auth State — Pinia

`useAuthStore` is the only Pinia store that holds data. It manages:
- `currentUser` — authenticated user object
- `isAuthenticated` — boolean
- `isLoading` — login in-flight flag

Do not create additional stores for feature data.

---

## Table UI State — useTableState

All paginated tables use `useTableState`. It owns:
- `page` / `perPage`
- `sort` (key + direction)
- `filters` (typed object)
- `queryParams` — computed ref combining all above

```ts
const table = useTableState({
  defaultSort: { key: 'name', direction: 'asc' },
  defaultFilters: { search: null, status: null },
})

// Reset page on filter change is automatic.
```

---

## Dialog State — ref<T | null>

Use a typed ref to hold the item being acted on. `null` = dialog closed.

```ts
// ✅ Canonical dialog state pattern
const confirmDelete = ref<User | null>(null)
const editItem = ref<User | null>(null)

// In template:
// <AppConfirmDialog :model-value="!!confirmDelete" @confirm="doDelete" />
// <EditDialog v-if="editItem" :item="editItem" @close="editItem = null" />
```

Never use a bare `boolean` ref for dialogs — you lose the selected item reference.

---

## Local Component State

Use `ref` for primitive values, `reactive` for form objects.

```ts
// Primitives
const isExpanded = ref(false)
const searchQuery = ref('')

// Form objects
const form = reactive({
  name: '',
  email: '',
  role: 'member' as UserRole,
})
```

---

## What NOT to Store in Pinia

- Paginated lists — belongs in query cache
- Form data — belongs in `reactive` local state
- UI state (open modals, active tabs) — belongs in `ref` local state
- Derived/computed data — belongs in `computed`

---

## Reactivity Rules

1. Never mutate props — use `defineEmits` + `emit`
2. Never mutate query cache data — use mutations
3. Prefer `computed` over `watch` for derived state
4. Use `watch` only for side effects (e.g. reset form when dialog opens)
5. `watchEffect` is rarely needed — prefer explicit `watch`
