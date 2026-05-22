# State Management

**Block:** 6.3 — Frontend Architecture & Governance  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

State is the most common source of frontend architectural debt. The default answer to "where should this live?" is almost always "in a composable or the query cache — not in a store."

Pinia stores are used **only for true global client state** that is not tied to server data.

---

## State Ownership Hierarchy

Before reaching for a Pinia store, work through this hierarchy:

```
1. Local component state (ref/reactive inside component)
   → Use when: state is purely UI, scoped to one component

2. Composable state (ref inside a composable)
   → Use when: state is shared within a feature flow but not globally

3. TanStack Query cache (useQuery / useMutation)
   → Use when: state is server data (lists, records, paginated results)

4. Pinia store
   → Use when: state is global, client-side, and not fetched from the server
```

If the data came from an API call, it belongs in the **query cache**, not Pinia.

---

## Approved Pinia Stores

Only these global stores are approved. New stores require documented justification.

### `useAuthStore`

```typescript
// shared/stores/auth.ts

import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', () => {
  const accessToken = ref<string | null>(null)
  const user = ref<AuthUser | null>(null)

  function setToken(token: string) {
    accessToken.value = token
  }

  function clearSession() {
    accessToken.value = null
    user.value = null
  }

  return { accessToken, user, setToken, clearSession }
}, {
  persist: true,  // Persisted to localStorage via pinia-plugin-persistedstate
})
```

Responsibility: holds the bearer token and the resolved current user. Nothing else.

### `useTenantStore`

```typescript
// shared/stores/tenant.ts

import { defineStore } from 'pinia'

export const useTenantStore = defineStore('tenant', () => {
  const tenantId = ref<string | null>(null)
  const tenantName = ref<string | null>(null)

  function setTenant(id: string, name: string) {
    tenantId.value = id
    tenantName.value = name
  }

  return { tenantId, tenantName, setTenant }
})
```

Responsibility: holds the active tenant context. Consumed by `useTenantContext()` composable and injected by the API interceptor.

### `useNotificationStore`

```typescript
// shared/stores/notifications.ts

export const useNotificationStore = defineStore('notifications', () => {
  const queue = ref<Notification[]>([])

  function push(notification: Notification) {
    queue.value.push({ ...notification, id: crypto.randomUUID() })
  }

  function dismiss(id: string) {
    queue.value = queue.value.filter(n => n.id !== id)
  }

  return { queue, push, dismiss }
})
```

Responsibility: cross-cutting notification/toast queue only.

---

## What Does NOT Go In Pinia

| Data type | Where it lives |
|---|---|
| Project list | TanStack Query cache |
| Single project detail | TanStack Query cache |
| Paginated results | TanStack Query cache |
| Form field values | Composable `reactive()` |
| UI loading state | TanStack Query `isLoading` |
| Modal open/close | Local `ref<boolean>` in component |
| Selected table rows | Local `ref<string[]>` in component or composable |
| Active tab | Local `ref<string>` in component |
| Filter values | Composable `ref` (passed as query key param) |
| User permissions | `useAuthStore.user.permissions` (already in store) |

---

## Module-Level State Rules

Modules must not create their own Pinia stores. Module state lives in:
- Composable `ref`s for local feature state
- TanStack Query cache for server data

If a module needs to share state across its own components (e.g., multi-step wizard), it uses a composable with a module-scoped singleton pattern:

```typescript
// modules/projects/composables/useProjectWizard.ts

// Module-scoped singleton — created once, shared within the wizard flow
let wizardState: ReturnType<typeof createWizardState> | null = null

function createWizardState() {
  return {
    step: ref(1),
    formData: reactive<Partial<CreateProjectPayload>>({}),
    reset() {
      step.value = 1
      Object.assign(formData, {})
    },
  }
}

export function useProjectWizard() {
  if (!wizardState) {
    wizardState = createWizardState()
  }
  return wizardState
}
```

The wizard state is destroyed by calling `reset()` on navigation away — not automatically.

---

## Store Structure Requirements

All Pinia stores must:

1. Use the **composition API style** (`defineStore('id', () => { ... })`) — not the options API style
2. Return only what consumers need — no leaking of internal implementation refs
3. Be typed — all state refs and return values have explicit TypeScript types
4. Be located in `shared/stores/` — no stores inside module directories

---

## Reactive State Across Components

When multiple components in the same module need to share reactive state (not server data), the composable may return a singleton:

```typescript
export function useActiveFilters() {
  // This ref is module-scoped (not component-scoped) because it is defined
  // at module level, not inside the function body.
  return filters  // shared ref
}
```

Use this pattern deliberately. Unintended singletons cause stale state bugs across navigation.

---

## Banned Patterns

- Storing API response data in a Pinia store (use the query cache)
- `localStorage.getItem()` / `setItem()` calls outside of `useAuthStore`
- Module-level Pinia stores (`modules/projects/stores/projectStore.ts`)
- `provide/inject` for global state (use Pinia stores instead)
- Mutating Pinia store state directly from a component (use store actions/methods)
- Reactive global objects (`const globalState = reactive({})`) defined at the module level in utility files
