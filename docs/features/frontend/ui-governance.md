# Frontend UI Governance

**Status:** Active  
**Applies to:** All Core Platform modules, and all applications built on the platform (CondoFlow, MiniHIS, Reference App, future enterprise systems)

---

## Why this layer exists

Without a governance layer, each developer makes local decisions:

- One page uses `v-btn color="primary" variant="flat"`, another uses `v-btn color="primary" variant="tonal"`, a third invents a new combination.
- Loading states are inline skeleton loaders in some pages, spinners in others, and blank screens in a few.
- Form inputs vary in density, validation display, and disabled behaviour.

Over time, the frontend becomes **inconsistent by default** and **consistent only by accident**.

`shared/ui` exists to invert this. The platform provides a set of explicit, deterministic primitives. Modules consume the primitives. Vuetify is an implementation detail that modules never see.

---

## Architecture

```
frontend/src/
  shared/
    ui/           ← THE ONLY import surface for modules
      index.ts    ← re-exports everything below
    primitives/   ← AppButton, AppCard
    feedback/     ← AppLoadingState, AppEmptyState, AppErrorState
    layouts/      ← AppPageLayout
    forms/        ← AppTextField, AppTextarea, AppSelect, AppCheckbox
```

**The rule is simple:**

```ts
// ✅ Allowed — import only from @/shared/ui
import { AppButton, AppPageLayout, AppTextField } from '@/shared/ui'

// ❌ Forbidden — internal sub-path
import AppButton from '@/shared/primitives/AppButton.vue'

// ❌ Forbidden — direct Vuetify usage in modules
import { VBtn } from 'vuetify/components'
```

---

## Primitives reference

### AppPageLayout

**The only sanctioned page-level wrapper.** Every CRUD/admin page must use it.

```vue
<AppPageLayout
  title="Forms"
  description="Create and manage forms for this tenant."
  :loading="isLoading"
  :error="isError"
  error-message="Could not load forms."
  :breadcrumbs="[{ title: 'Dashboard', to: { name: 'dashboard' } }, { title: 'Forms' }]"
>
  <template #actions>
    <AppButton prepend-icon="mdi-plus" @click="create">New Form</AppButton>
  </template>

  <!-- page content here -->
</AppPageLayout>
```

Props:

| Prop | Type | Default | Purpose |
|---|---|---|---|
| `title` | `string` | required | H1 page title |
| `description` | `string` | — | Subtitle below title |
| `loading` | `boolean` | `false` | Shows `AppLoadingState` in body |
| `error` | `boolean` | `false` | Shows `AppErrorState` in body |
| `errorMessage` | `string` | — | Custom error message |
| `breadcrumbs` | `Breadcrumb[]` | — | Breadcrumb trail above title |

Slots: `default` (body content), `actions` (top-right header area).

---

### AppButton

**Semantic variants** — do not mix color and variant directly.

```vue
<AppButton variant="primary" @click="save">Save</AppButton>
<AppButton variant="secondary" @click="cancel">Cancel</AppButton>
<AppButton variant="ghost" icon="mdi-close" aria-label="Close" />
<AppButton variant="danger" :loading="isDeleting" @click="destroy">Delete</AppButton>
<AppButton variant="tonal" prepend-icon="mdi-eye">Preview</AppButton>
```

| Variant | Color | Vuetify variant | Use for |
|---|---|---|---|
| `primary` | primary | flat | Primary CTA |
| `secondary` | default | outlined | Secondary action |
| `ghost` | default | text | Low-emphasis action, icon buttons |
| `danger` | error | flat | Destructive action |
| `tonal` | primary | tonal | Soft emphasis |

**Forbidden:**
```vue
<!-- ❌ Never use VBtn directly -->
<v-btn color="primary" variant="flat">Save</v-btn>

<!-- ❌ Never mix arbitrary color + variant on AppButton -->
<AppButton color="green" variant="outlined">Custom</AppButton>
```

---

### AppCard

Standard content container. Replaces `<v-card variant="outlined" rounded="lg">`.

```vue
<AppCard title="Details" subtitle="Last updated 2 hours ago">
  <!-- content -->

  <template #actions>
    <AppButton variant="ghost">Edit</AppButton>
  </template>
</AppCard>
```

Props: `title`, `subtitle`, `loading`, `flush` (removes padding for tables/full-bleed content).

---

### AppLoadingState

```vue
<!-- Default: 3 list-item-two-line skeletons -->
<AppLoadingState />

<!-- Card grid: 2 card skeletons -->
<AppLoadingState :rows="2" type="card" />
```

**Forbidden:**
```vue
<!-- ❌ Never inline skeleton loaders -->
<v-skeleton-loader v-for="n in 3" :key="n" type="list-item-two-line" />
```

---

### AppEmptyState

```vue
<AppEmptyState
  icon="mdi-file-document-multiple-outline"
  title="No forms yet"
  description="Create your first form to get started."
>
  <template #action>
    <AppButton prepend-icon="mdi-plus" @click="create">Create Form</AppButton>
  </template>
</AppEmptyState>
```

---

### AppErrorState

```vue
<AppErrorState
  title="Could not load forms"
  message="Check your connection and try again."
>
  <template #action>
    <AppButton variant="ghost" @click="refetch">Retry</AppButton>
  </template>
</AppErrorState>
```

---

### Form inputs

All form inputs share the same contract:

```vue
<AppTextField
  v-model="name"
  label="Form Name"
  :errors="fieldErrors.name"
  :disabled="isPending"
/>

<AppTextarea
  v-model="description"
  label="Description"
  :rows="4"
  :errors="fieldErrors.description"
/>

<AppSelect
  v-model="status"
  label="Status"
  :items="['draft', 'active', 'archived']"
  :errors="fieldErrors.status"
/>

<AppCheckbox
  v-model="required"
  label="Required"
/>
```

`errors` accepts `string[]` — maps directly to Laravel's `422` error shape `Record<string, string[]>`.

**Forbidden:**
```vue
<!-- ❌ Never use Vuetify form inputs directly in modules -->
<v-text-field density="comfortable" variant="outlined" ... />
```

---

## Allowed Vuetify usage in modules

Modules may still use Vuetify **layout and structural components** that have no shared/ui equivalent:

- `v-divider` — simple dividers
- `v-chip` — status badges (not buttons)
- `v-dialog` / `v-menu` — overlays (no shared/ui wrapper yet)
- `v-list` / `v-list-item` — structured lists
- `v-alert` (closable inline alerts only) — for transient save/publish errors
- `v-icon` — icons
- `v-spacer`, `v-row`, `v-col` — layout helpers
- `v-breadcrumbs` (inside AppPageLayout only — already handled)

When in doubt: if it has a shared/ui equivalent, use the shared/ui component.

---

## Governance rules

| Rule | Reason |
|---|---|
| All modules import from `@/shared/ui` only | Single discoverable surface; sub-paths are internal |
| Never use `VBtn` directly in module code | AppButton enforces semantic variants |
| Never inline loading/error/empty states | AppLoadingState / AppErrorState / AppEmptyState ensure UX consistency |
| Never write ad-hoc page headers | AppPageLayout is the only page layout contract |
| Form inputs must use AppTextField/AppTextarea/AppSelect/AppCheckbox | Consistent validation display aligned with Laravel 422 responses |
| No business logic in shared/ui components | These are pure presentation primitives |
| Do not add `class` overrides to shared/ui components from modules | If spacing/density needs changing, discuss and update the primitive |

---

## Adding new primitives

Before adding a new primitive to `shared/ui`:

1. The pattern must appear in **at least two separate modules** before abstraction is justified.
2. The primitive must have **a minimal, explicit API surface** — prefer fewer props.
3. Do not abstract every Vuetify prop — only the ones that enforce consistency.
4. Add an entry to this document.

**Candidates for future primitives:**

| Primitive | Description | Trigger |
|---|---|---|
| `AppDataTable` | Standard data table with pagination | When a second module needs tabular data |
| `AppDialog` | Standard confirmation/form dialog | When dialog patterns diverge across modules |
| `AppStatusChip` | Typed status badge (draft/active/archived) | Already duplicated — extract after current sprint |
| `AppFormSection` | Labelled form section group | When form pages grow multi-section |
| `AppBreadcrumbs` | Standalone breadcrumb (currently embedded in AppPageLayout) | If needed outside page headers |
| `AppMenu` | Standard dropdown menu | When action menus appear in 2+ modules |

---

## For AI-assisted development

When generating new module pages or components, always:

1. Wrap pages in `<AppPageLayout>`.
2. Use `AppButton` with one of the five semantic variants.
3. Bind `isLoading` → `:loading` on AppPageLayout.
4. Bind `isError` → `:error` on AppPageLayout.
5. Use `AppEmptyState` for zero-item states.
6. Import everything from `@/shared/ui`.

Never generate direct Vuetify component usage for anything covered by this layer.
