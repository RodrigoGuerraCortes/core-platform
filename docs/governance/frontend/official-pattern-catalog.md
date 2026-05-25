# Official Pattern Catalog

**Core Platform Frontend — Canonical Pattern Reference**  
**Status:** Frozen as of Block 8.4.2 (2026-05-25)

This catalog is the single source of truth for all officially approved UI and interaction patterns. Every pattern listed here has a working reference implementation in `src/modules/reference/`.

---

## How to Use This Catalog

1. **Starting a new feature?** Find the closest pattern below and follow its recipe.
2. **Need a variation?** Check if the existing primitive's props/slots handle it before creating anything new.
3. **Something missing?** Follow the [extraction rules](./vertical-extraction-rules.md) before adding a new pattern.

---

## Pattern Index

| # | Pattern | Reference Page | Status |
|---|---|---|---|
| 1 | [CRUD / Data Table](#1-crud--data-table) | `UsersExamplePage` | Frozen |
| 2 | [Detail Page](#2-detail-page) | `UserDetailPage` | Frozen |
| 3 | [Approval / Workflow](#3-approval--workflow) | `ApprovalWorkflowPage` | Frozen |
| 4 | [File Upload](#4-file-upload) | `UploadExamplePage` | Frozen |
| 5 | [Multi-step Wizard](#5-multi-step-wizard) | `OnboardingWizardPage` | Frozen |
| 6 | [Permission Visibility](#6-permission-visibility) | `PermissionsExamplePage` | Frozen |
| 7 | [Empty States](#7-empty-states) | `EmptyStatesExamplePage` | Frozen |
| 8 | [Form Authoring](#8-form-authoring) | `FormEditorPage` | Frozen |
| 9 | [Async State (Loading / Error)](#9-async-state-loading--error) | All pages | Frozen |
| 10 | [Filters](#10-filters) | `UsersExamplePage` | Frozen |
| 11 | [Table Toolbar](#11-table-toolbar) | `UsersExamplePage` | Frozen |
| 12 | [Activity Timeline](#12-activity-timeline) | `UserDetailPage` | Frozen |

---

## 1. CRUD / Data Table

**Route:** `/reference/users`  
**File:** `src/modules/reference/pages/UsersExamplePage.vue`

### Recipe

```ts
// 1. Define columns
const columns: TableColumn<MyEntity>[] = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'status', label: 'Status' },
]

// 2. Wire state
const table = useTableState()
const { data, isLoading, isError } = useMyEntityQuery(table.queryParams)
```

```vue
<!-- 3. Render -->
<AppDataTable
  :columns="columns"
  :rows="data?.data ?? []"
  :total="data?.meta.total ?? 0"
  :page="table.page.value"
  :per-page="table.perPage.value"
  :loading="isLoading"
  :error="isError"
  @update:page="table.setPage"
  @update:per-page="table.setPerPage"
>
  <template #col-status="{ value }">
    <AppStatusChip :status="value" />
  </template>
  <template #actions="{ row }">
    <AppButton icon="mdi-eye-outline" variant="ghost" size="small" />
    <AppButton icon="mdi-delete-outline" variant="ghost" size="small" />
  </template>
</AppDataTable>
```

### Rules
- Always use `AppDataTable` — never `v-data-table` directly
- Server-side pagination via `update:page` / `update:perPage` events
- Row actions via `#actions="{ row }"` slot
- Column customization via `#col-{key}` slots

---

## 2. Detail Page

**Route:** `/reference/users/:id`  
**File:** `src/modules/reference/pages/UserDetailPage.vue`

### Recipe

```vue
<AppDetailLayout
  :title="entity?.name"
  :breadcrumbs="breadcrumbs"
  :loading="isLoading"
  :error="isError"
  with-sidebar
  sticky-header
>
  <template #status><AppStatusChip :status="entity.status" /></template>
  <template #subtitle><p>{{ entity.subtitle }}</p></template>
  <template #metadata><AppEntityMeta :items="metaItems" /></template>
  <template #actions>
    <AppEntityActions>
      <template #primary><AppButton variant="primary">Edit</AppButton></template>
      <template #danger>
        <AppPermissionBoundary permission="entity.delete">
          <AppButton variant="danger">Delete</AppButton>
        </AppPermissionBoundary>
      </template>
    </AppEntityActions>
  </template>
  <template #tabs>
    <v-tabs v-model="activeTab" density="compact">
      <v-tab value="details">Details</v-tab>
      <v-tab value="audit">Audit Log</v-tab>
    </v-tabs>
  </template>

  <!-- Main content (default slot) -->
  <v-window v-model="activeTab">
    <v-window-item value="details">...</v-window-item>
    <v-window-item value="audit">...</v-window-item>
  </v-window>

  <template #sidebar>
    <AppCard>Quick stats</AppCard>
  </template>

  <template #activity>
    <AppSection title="Activity">
      <AppActivityTimeline>...</AppActivityTimeline>
    </AppSection>
  </template>
</AppDetailLayout>
```

### Tabs
Always include: **Details**, **Audit Log**  
Add as needed: Permissions, Related Resources, Settings

---

## 3. Approval / Workflow

**Route:** `/reference/approvals`  
**File:** `src/modules/reference/pages/ApprovalWorkflowPage.vue`

### Recipe
- Status displayed via `AppStatusChip`
- Transitions gated by `AppPermissionBoundary` or `v-if="can('permission')"` + disabled tooltip
- Destructive transitions (reject/delete) confirmed via `AppConfirmDialog`
- Mutations use `useMutation` with `onSuccess: () => queryClient.invalidateQueries(...)`

### Status Flow
```
pending → approved | rejected
draft   → pending  → approved | rejected
```

---

## 4. File Upload

**Route:** `/reference/upload`  
**File:** `src/modules/reference/pages/UploadExamplePage.vue`

### Recipe
- `useUploadManager` composable owns file list + progress state
- Per-file progress via `v-progress-linear`
- Retry button per failed file
- Remove button before/after upload
- Drop zone + file input trigger

---

## 5. Multi-step Wizard

**Route:** `/reference/onboarding`  
**File:** `src/modules/reference/pages/OnboardingWizardPage.vue`

### Recipe

```ts
const currentStep = ref(0)
const isSubmitting = ref(false)
const isDone = ref(false)

function validateStep(index: number): boolean {
  errors.value = {}
  // ... field checks
  return Object.keys(errors.value).length === 0
}

function goNext(): void {
  if (isLastStep.value) { submit(); return }
  if (!validateStep(currentStep.value)) return
  currentStep.value++
}
```

```vue
<v-window v-model="currentStep" :touch="false">
  <v-window-item :value="0"><!-- Step 1 --></v-window-item>
  <v-window-item :value="1"><!-- Step 2 --></v-window-item>
</v-window>

<!-- Shared footer -->
<div class="d-flex justify-space-between">
  <AppButton variant="ghost" :disabled="isFirstStep" @click="goBack">Back</AppButton>
  <AppButton variant="primary" :loading="isSubmitting" @click="goNext">
    {{ isLastStep ? 'Submit' : 'Continue' }}
  </AppButton>
</div>
```

### Required steps
1. Validate current step before advancing
2. Show progress indicator
3. Review step (last) summarizes all data
4. Success state after submission
5. Cancel dialog confirms discard

---

## 6. Permission Visibility

**Route:** `/reference/permissions`  
**File:** `src/modules/reference/pages/PermissionsExamplePage.vue`

### Four Modes

| Mode | When | Implementation |
|---|---|---|
| **Hidden** | Sensitive/destructive actions the user has no access to | `v-if="can('permission')"` |
| **Disabled + Tooltip** | Actions users can see but not perform — awareness matters | `<v-tooltip>` + `:disabled="!can(...)"` |
| **Read-only field** | Form data always visible; edit access gated | `:readonly="!can('...')"` |
| **Locked workflow** | Multi-party workflow; advance gated per role | Disabled advance button + tooltip |

### Rule
No inline role string comparisons in templates:
```vue
<!-- ❌ Forbidden -->
<AppButton v-if="user.role === 'admin'">Delete</AppButton>

<!-- ✅ Correct -->
<AppButton v-if="can('users.delete')">Delete</AppButton>
```

Use `usePermission()` in script and `AppPermissionBoundary` in templates.

---

## 7. Empty States

**Route:** `/reference/empty-states`  
**File:** `src/modules/reference/pages/EmptyStatesExamplePage.vue`

### Official Presets

| Preset | Icon | Use when |
|---|---|---|
| `no-results` | `mdi-magnify` | Search returned nothing |
| `no-records` | `mdi-inbox-outline` | Collection is empty |
| `permission` | `mdi-lock-outline` | User lacks access |
| `archived` | `mdi-archive-outline` | Items exist but archived |
| `disconnected` | `mdi-cloud-off-outline` | Service unavailable |
| `filtered` | `mdi-filter-off-outline` | Filters produced no results |
| `onboarding` | `mdi-rocket-launch-outline` | First-time / getting started |

### Domain variants
```vue
<AppEmptyState
  preset="no-records"
  title="No users yet"
  description="Invite your first team member to get started."
>
  <template #action>
    <AppButton variant="primary" prepend-icon="mdi-account-plus-outline">
      Invite User
    </AppButton>
  </template>
</AppEmptyState>
```

### Flat mode
```vue
<!-- Inside a card — use :flat to avoid double border -->
<AppEmptyState preset="no-records" title="No items" flat />
```

---

## 8. Form Authoring

**Route:** `/forms/:id/edit`  
**File:** `src/modules/dynamic-forms/pages/FormEditorPage.vue`

### Recipe
- `useDraftSchema` — local reactive schema, field operations, persistence-ready
- `useFormQuery` + `useFormVersionsQuery` — load form + latest version
- Watch both `isLoading` states before seeding schema (prevents race condition)
- Field registry: `FIELD_REGISTRY` maps `FieldType` → `{component, defaultProps}`

---

## 9. Async State (Loading / Error)

Applied consistently across all pages.

### Pattern

```vue
<AppLoadingState v-if="isLoading" :rows="5" type="table-row" />
<AppErrorState v-else-if="isError" message="Could not load data.">
  <template #action>
    <AppButton @click="refetch">Retry</AppButton>
  </template>
</AppErrorState>
<template v-else>
  <!-- Data -->
</template>
```

### Types
`AppLoadingState` accepts `type`:
- `table-row` — multiple skeleton rows
- `heading` — title + subtitle skeletons
- `card` — card-shaped skeleton
- `text` — paragraph lines

---

## 10. Filters

**File:** `src/shared/table/components/AppFilterBar.vue`

### Recipe

```vue
<AppFilterBar :filters="filters" :state="table.filters" @change="table.setFilter" />
```

Where `filters` is an array of `FilterConfig`:
```ts
const filters: FilterConfig[] = [
  { key: 'status', label: 'Status', type: 'select', options: [...] },
  { key: 'role', label: 'Role', type: 'select', options: [...] },
]
```

---

## 11. Table Toolbar

**File:** `src/shared/table/components/AppTableToolbar.vue`

### Recipe

```vue
<AppTableToolbar
  v-model:search="table.search.value"
  :total="data?.meta.total"
  placeholder="Search users..."
>
  <template #actions>
    <AppButton variant="primary" prepend-icon="mdi-plus">New</AppButton>
  </template>
</AppTableToolbar>
```

---

## 12. Activity Timeline

**File:** `src/shared/timeline/AppActivityTimeline.vue`

### Recipe

```vue
<AppActivityTimeline :empty="events.length === 0">
  <AppTimelineItem
    v-for="(event, idx) in events"
    :key="event.id"
    :icon="event.icon"
    :icon-color="event.iconColor"
    :label="event.label"
    :actor="event.actor"
    :timestamp="event.timestamp"
    :last="idx === events.length - 1"
  />
</AppActivityTimeline>
```

---

## Appendix: Shared Component Inventory

| Component | Category | Barrel |
|---|---|---|
| `AppButton` | Primitives | `@/shared/ui` |
| `AppCard` | Primitives | `@/shared/ui` |
| `AppSection` | Primitives | `@/shared/ui` |
| `AppPageHeader` | Primitives | `@/shared/ui` |
| `AppConfirmDialog` | Primitives | `@/shared/ui` |
| `AppStatusChip` | Primitives | `@/shared/ui` |
| `AppToolbarActions` | Primitives | `@/shared/ui` |
| `AppPermissionBoundary` | Primitives | `@/shared/ui` |
| `AppLoadingState` | Feedback | `@/shared/ui` |
| `AppEmptyState` | Feedback | `@/shared/ui` |
| `AppErrorState` | Feedback | `@/shared/ui` |
| `AppPageLayout` | Layouts | `@/shared/ui` |
| `AppDetailLayout` | Layouts | `@/shared/ui` |
| `AppTextField` | Forms | `@/shared/ui` |
| `AppTextarea` | Forms | `@/shared/ui` |
| `AppSelect` | Forms | `@/shared/ui` |
| `AppCheckbox` | Forms | `@/shared/ui` |
| `AppEntityHeader` | Entity | `@/shared/ui` |
| `AppEntityMeta` | Entity | `@/shared/ui` |
| `AppEntityActions` | Entity | `@/shared/ui` |
| `AppActivityTimeline` | Timeline | `@/shared/ui` |
| `AppTimelineItem` | Timeline | `@/shared/ui` |
| `AppDataTable` | Table | `@/shared/table` |
| `AppTableToolbar` | Table | `@/shared/table` |
| `AppFilterBar` | Table | `@/shared/table` |
| `useTableState` | Table | `@/shared/table` |
| `useFilterState` | Table | `@/shared/table` |
| `usePermission` | Auth | `@/shared/composables/usePermission` |
| `useNavigation` | Nav | `@/shared/composables/useNavigation` |
