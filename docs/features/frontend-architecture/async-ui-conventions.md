# Async UI Conventions

**Block:** 6.3 — Frontend Architecture & Governance  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Async UI behavior is a consistency problem. Without conventions, each module handles loading, errors, and pagination differently. This document freezes the approved patterns for all async UI states across the platform.

---

## The Four Async States

Every data-fetching surface must explicitly handle all four states:

| State | Description | Required UI |
|---|---|---|
| **Loading** | Request in flight, no data yet | Loading indicator |
| **Success** | Data available | Render content |
| **Empty** | Request succeeded, but zero results | Empty state message |
| **Error** | Request failed | Error message + retry action |

Omitting any state is a governance violation. A blank screen is not acceptable for loading or error states.

---

## Standard Page Loading Pattern

```vue
<script setup lang="ts">
import { useProjectList } from '../composables/useProjectList'

const { projects, pagination, isLoading, isError, error, refetch } = useProjectList()
</script>

<template>
  <div>
    <PageHeader title="Projects" />

    <LoadingSpinner v-if="isLoading" />

    <ErrorAlert
      v-else-if="isError"
      :message="error?.message ?? 'Failed to load projects.'"
      @retry="refetch()"
    />

    <EmptyState
      v-else-if="projects.length === 0"
      message="No projects found."
    />

    <ProjectTable
      v-else
      :projects="projects"
      :pagination="pagination"
    />
  </div>
</template>
```

The `v-if` / `v-else-if` / `v-else` chain is the canonical structure. Use it consistently — no creativity with alternate approaches.

---

## Skeleton Loading (Optional Upgrade)

For content-heavy pages, replace `LoadingSpinner` with skeleton placeholders. Skeletons are preferred when:

- The page has a complex, structured layout
- The load time is expected to exceed ~500ms frequently

```vue
<template>
  <div>
    <template v-if="isLoading">
      <ProjectTableSkeleton />
    </template>
    <ProjectTable v-else-if="projects.length" :projects="projects" />
    <!-- ...other states -->
  </div>
</template>
```

`LoadingSpinner` is acceptable as a default. Skeletons are opt-in.

---

## Smooth Pagination

When paginating, the UI must not flash a loading state between page changes. Use `placeholderData: keepPreviousData` in TanStack Query (see query-conventions.md).

The previous page's data remains visible while the next page loads. The loading indicator is subtle (opacity reduction or a small spinner in the pagination control), not a full-page loading overlay.

```vue
<template>
  <ProjectTable
    :projects="projects"
    :class="{ 'opacity-50': isFetching && !isLoading }"
  />
  <Pagination
    :meta="pagination"
    :loading="isFetching"
    @change="page = $event"
  />
</template>
```

- `isLoading` — true only on first load (no cached data)
- `isFetching` — true on any background refetch or page change

---

## Error Display Levels

Errors appear at three scopes. Display at the most specific scope possible:

| Scope | Component | Trigger |
|---|---|---|
| **Field-level** | Inline below input | Form validation errors |
| **Form-level** | Alert above submit button | Non-field API errors on submit |
| **Page-level** | Full section replacement | Failed page-load queries |
| **Global** | Toast notification | Background mutations (create, delete) |

Never display a raw error object (`{{ error }}`). Always extract `.message` or display a fallback string.

---

## Toast Notifications for Mutations

Successful and failed mutations (create, update, delete) produce global toast notifications via `useNotificationStore`:

```typescript
// In a composable or page
const { push } = useNotificationStore()

useMutation({
  mutationFn: createProject,
  onSuccess: () => {
    push({ type: 'success', message: 'Project created successfully.' })
    router.push({ name: 'projects.index' })
  },
  onError: (error: ApiError) => {
    push({ type: 'error', message: error.message })
  },
})
```

Toast messages must be human-readable strings. Never surface technical error details (stack traces, SQL errors, internal IDs) in toasts.

---

## Retry Strategy

- Query failures: TanStack Query retries once automatically (configured in `queryClient.ts`)
- Manual retry: `ErrorAlert` component always provides a retry button that calls `refetch()`
- Mutation failures: no automatic retry — the user must resubmit the form explicitly

```vue
<ErrorAlert
  message="Failed to load projects."
  @retry="refetch()"
/>
```

The `@retry` handler must always be provided on page-level error states.

---

## Disabled States During Submission

While a form is submitting (`isSubmitting: true`):

- The submit button is disabled and shows a loading indicator
- All form inputs are disabled (prevent double-submission)
- Navigation away from the form triggers a confirmation dialog if there is unsaved data

```vue
<template>
  <form @submit.prevent="submit">
    <v-text-field :disabled="isSubmitting" v-model="form.name" />

    <v-btn
      type="submit"
      :loading="isSubmitting"
      :disabled="isSubmitting"
    >
      Save
    </v-btn>
  </form>
</template>
```

---

## Optimistic vs Pessimistic UI

| Mutation type | Recommended approach |
|---|---|
| Create (new record) | Pessimistic — wait for confirmation, then show |
| Update (field toggle, status change) | Optimistic allowed — with rollback |
| Delete | Pessimistic — confirm dialog, wait for success |
| Bulk operations | Pessimistic — show progress indicator |

See query-conventions.md for the optimistic update rollback pattern.

---

## Concurrent Requests

Do not fire multiple independent queries from a page's `<script setup>` without coordination. When two queries are needed for a page:

1. Use dependent queries (`enabled`) if one depends on the other
2. Use parallel query composables if they are truly independent — TanStack Query deduplicates automatically

Never use `Promise.all()` with raw API functions in a page component. Compose composables instead.

---

## Async Navigation Guards

When a page requires data before rendering (e.g., permission checks, resource existence), use a `beforeEnter` route guard — not a loading state inside the component:

```typescript
// router/guards/requireProject.ts

export async function requireProject(to: RouteLocationNormalized) {
  const project = await fetchProject(to.params.id as string)
  if (!project) {
    return { name: 'not-found' }
  }
}
```

This prevents partially-rendered pages. Guards are thin — they check and redirect, not preload full datasets.

---

## Banned Patterns

- Blank/white screen during loading (no indicator shown)
- `try/catch` blocks in page components swallowing errors silently
- Full-page loading overlay on pagination changes
- Raw `{{ error }}` displayed in templates
- `setTimeout()` used to simulate or delay loading states
- `Promise.all()` with API functions directly in a component
- Mutation success/failure handling with `window.alert()` or `console.log()`
- Disabled forms that give no visual indication they are disabled
