# Async State Management

Canonical rules for handling async states in every UI surface.
**No blank screens. No silent failures. Every state must be handled.**

---

## The Four Required States

Every component that fetches data must handle all four states:

| # | State | Trigger | Required output |
|---|---|---|---|
| 1 | **Loading** | `isLoading === true` | Spinner or skeleton |
| 2 | **Error** | `isError === true` | Error message + Retry button |
| 3 | **Empty** | success, but zero records | Helpful empty state |
| 4 | **Success** | data is present | Render the content |

---

## Page-Level Async (use AppPageLayout)

For full-page data dependencies:

```vue
<AppPageLayout
  title="Users"
  :loading="isLoading"
  :error="isError"
  error-message="Could not load users."
>
  <!-- Empty state -->
  <div v-if="!data?.data.length" class="text-center py-12">
    <p>No users yet.</p>
    <AppButton @click="openCreate">Add first user</AppButton>
  </div>

  <!-- Content (only rendered when not loading and not error) -->
  <AppDataTable v-else ... />
</AppPageLayout>
```

`AppPageLayout` handles rendering loading/error in place of content automatically.

---

## Table-Level Async (use AppDataTable)

`AppDataTable` accepts `:loading` and `:error` props directly:

```vue
<AppDataTable
  :rows="data?.data ?? []"
  :total="data?.meta.total ?? 0"
  :loading="isLoading"
  :error="isError"
  ...
>
  <template #empty-action>
    <AppButton @click="openCreate">Create first item</AppButton>
  </template>
  <template #error-action>
    <AppButton @click="refetch">Retry</AppButton>
  </template>
</AppDataTable>
```

---

## Section-Level Async (use AppLoadingState / AppErrorState)

For async content within a section (not the whole page):

```vue
<AppSection title="Metrics">
  <AppLoadingState v-if="isLoading" message="Loading metrics..." />
  <AppErrorState v-else-if="isError" message="Could not load metrics.">
    <template #action>
      <AppButton variant="ghost" size="small" @click="refetch">Retry</AppButton>
    </template>
  </AppErrorState>
  <MetricsGrid v-else :data="metrics" />
</AppSection>
```

---

## Mutation In-Flight State

Mutations must disable the triggering control and show a spinner:

```vue
<!-- Button that triggers a mutation -->
<AppButton
  variant="primary"
  :loading="isPending"
  :disabled="isPending"
  @click="save"
>
  Save
</AppButton>

<!-- Confirm dialog with mutation -->
<AppConfirmDialog
  v-model="showConfirm"
  :loading="isDeleting"
  @confirm="doDelete"
/>
```

Never leave the UI interactive during a mutation.

---

## When to Use Which Error Pattern

| Scenario | Pattern |
|---|---|
| Page fails to load entirely | `AppPageLayout :error` |
| A table fails to load | `AppDataTable :error` |
| A widget/section fails | `AppErrorState` inline |
| A mutation fails | Toast notification (snackbar) + form stays open |
| Validation error (422) | Inline field errors via `mapApiErrors` |
| Auth error (401) | Global redirect (handled by apiClient interceptor) |
| Gone (410) | Full-page `AppErrorState` with "Form Closed" message |

---

## Retry Behaviour

- **Query errors**: always provide a retry button that calls `refetch()`
- **Mutation errors**: keep the form/dialog open so user can retry
- **Page-level errors**: `AppPageLayout` exposes `@retry` event; wire to `refetch()`

---

## Toast Notifications

Use snackbar toasts for mutation success/failure only:

```ts
// On mutation success
onSuccess: () => {
  notify({ message: 'User deleted.', type: 'success' })
}

// On mutation error
onError: () => {
  notify({ message: 'Could not delete user. Please try again.', type: 'error' })
}
```

Do not use toasts for query errors — those get inline error states with retry.

---

## Optimistic Updates

Only implement optimistic updates for:
- Toggle / boolean actions (e.g. enable/disable, archive)
- Reorder interactions

Not required for: delete, create, bulk operations.
