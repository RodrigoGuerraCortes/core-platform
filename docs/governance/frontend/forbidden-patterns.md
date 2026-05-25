# Forbidden Patterns

These patterns are **ESLint errors** and will fail CI. Do not use them.
Each entry explains the violation and the correct alternative.

---

## Raw Vuetify Components (in module/app code)

### `<v-btn>` — use `<AppButton>`

```vue
<!-- ❌ Forbidden -->
<v-btn color="primary" @click="save">Save</v-btn>

<!-- ✅ Correct -->
<AppButton variant="primary" @click="save">Save</AppButton>
```

### `<v-data-table>` / `<v-data-table-server>` — use `<AppDataTable>`

```vue
<!-- ❌ Forbidden -->
<v-data-table-server :items="rows" />

<!-- ✅ Correct -->
<AppDataTable :columns="columns" :rows="rows" :total="total" ... />
```

### `<v-text-field>` — use `<AppTextField>`

```vue
<!-- ❌ Forbidden -->
<v-text-field v-model="name" label="Name" />

<!-- ✅ Correct -->
<AppTextField v-model="name" label="Name" />
```

### `<v-textarea>` — use `<AppTextarea>`
### `<v-select>` — use `<AppSelect>`
### `<v-checkbox>` — use `<AppCheckbox>`

Same pattern as above. Import from `@/shared/ui`.

---

## Direct Vuetify Component Imports

```ts
// ❌ Forbidden
import { VBtn, VDataTable } from 'vuetify/components'

// ✅ Correct
import { AppButton, AppDataTable } from '@/shared/ui'
// or
import { AppDataTable } from '@/shared/table'
```

---

## Direct `axios` Import Outside API Layer

```ts
// ❌ Forbidden (in pages, composables, stores, validation)
import axios from 'axios'
if (axios.isAxiosError(err)) { ... }

// ✅ Correct
import { isAxiosError } from '@/shared/api/client'
if (isAxiosError(err)) { ... }
```

`axios` may only be imported inside:
- `src/shared/api/`
- `src/modules/**/api/`

---

## Internal Sub-path Imports

```ts
// ❌ Forbidden — reaching into internal paths
import { AppButton } from '@/shared/primitives'
import { AppDataTable } from '@/shared/table/components/AppDataTable.vue'
import { AppLoadingState } from '@/shared/feedback'

// ✅ Correct — use the public barrel only
import { AppButton, AppLoadingState } from '@/shared/ui'
import { AppDataTable } from '@/shared/table'
```

---

## Ad-hoc Confirm Dialogs

```vue
<!-- ❌ Forbidden — every team invents a slightly different confirm dialog -->
<v-dialog v-model="showConfirm" max-width="400">
  <v-card>
    <v-card-title>Are you sure?</v-card-title>
    <v-card-actions>
      <v-btn @click="showConfirm = false">Cancel</v-btn>
      <v-btn color="error" @click="doDelete">Delete</v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>

<!-- ✅ Correct -->
<AppConfirmDialog
  v-model="showConfirm"
  title="Delete item?"
  description="This cannot be undone."
  confirm-label="Delete"
  confirm-variant="danger"
  :loading="isDeleting"
  @confirm="doDelete"
/>
```

---

## Inline Async Loading Blocks

```vue
<!-- ❌ Forbidden — inconsistent loading UX -->
<div v-if="loading" class="text-center pa-8">
  <v-progress-circular indeterminate />
</div>
<div v-else-if="error">Something went wrong</div>
<div v-else>...</div>

<!-- ✅ Correct -->
<AppPageLayout :loading="isLoading" :error="isError">
  ...
</AppPageLayout>
```

---

## Mutation Logic in Page Templates

```vue
<!-- ❌ Forbidden — API calls in template event handlers -->
<AppButton @click="async () => { await axios.delete('/items/' + id) }">Delete</AppButton>

<!-- ✅ Correct — composable + mutation -->
<AppButton :loading="isDeleting" @click="confirmDelete = item">Delete</AppButton>
```

---

## Missing `toValue()` on MaybeRef Params

```ts
// ❌ Forbidden — passes the ref object into the URL
queryFn: () => fetchItem(formId),  // formId is a Ref<number>

// ✅ Correct
queryFn: () => fetchItem(toValue(formId)),
```

---

## Cross-Module Imports

```ts
// ❌ Forbidden — modules must not import from each other
import { useFormsQuery } from '@/modules/dynamic-forms/composables'

// ✅ Correct — shared contracts belong in src/shared/
```
