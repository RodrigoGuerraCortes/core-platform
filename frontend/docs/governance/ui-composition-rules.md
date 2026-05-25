# UI Composition Rules

Rules for structuring page templates. Every page should feel platform-native.

---

## Page Structure

```
AppPageLayout
├── #actions slot — primary CTAs (New, Export, etc.)
├── AppSection (optional grouping)
│   ├── #header-end slot — section-level actions
│   └── default slot — content
└── AppDataTable (for list pages)
    └── #toolbar slot
        └── AppTableToolbar
            ├── #actions slot — filter + secondary actions
            └── AppFilterBar
```

---

## Spacing Rules

| Context | Rule |
|---|---|
| Between sections | `mb-6` (provided by `AppSection` default) |
| Between cards in a grid | `gap-4` via `v-row dense` |
| Inside a card | Vuetify default padding — do not override |
| Page content top | `AppPageLayout` handles — do not add extra `mt` |
| Action button group | `gap-2` via `d-flex gap-2` |

Do not add `mt-*` or `mb-*` to page layout roots — `AppPageLayout` provides this.

---

## Header / Actions Alignment

Primary page actions go in `AppPageLayout #actions`:

```vue
<AppPageLayout title="Users">
  <template #actions>
    <AppButton variant="primary" prepend-icon="mdi-plus">New User</AppButton>
  </template>
  ...
</AppPageLayout>
```

Section-level actions go in `AppSection #header-end`:

```vue
<AppSection title="Recent Activity">
  <template #header-end>
    <AppButton variant="ghost" size="small">View all</AppButton>
  </template>
  ...
</AppSection>
```

---

## Table Placement

A table should be the **only** primary content on a list page. Do not place a table inside a card. `AppDataTable` has its own surface.

```vue
<!-- ✅ Correct — table is direct child of layout -->
<AppPageLayout title="Users" :loading="isLoading">
  <AppDataTable ... />
</AppPageLayout>

<!-- ❌ Forbidden — table inside a card creates double-border visual noise -->
<AppPageLayout title="Users">
  <AppCard>
    <AppDataTable ... />
  </AppCard>
</AppPageLayout>
```

---

## Filter Placement

Filters always go inside `AppTableToolbar #actions`:

```vue
<AppDataTable ...>
  <template #toolbar>
    <AppTableToolbar title="Users" :count="total">
      <template #actions>
        <AppFilterBar :model-value="table.filters.value" :fields="filterFields"
          @update:model-value="table.setFilters" />
        <AppButton variant="primary" @click="openCreate">New</AppButton>
      </template>
    </AppTableToolbar>
  </template>
</AppDataTable>
```

Filter bar goes **before** action buttons, left to right: filters → bulk actions → primary CTA.

---

## Status Chips

Always use `<AppStatusChip>` — never inline `<v-chip>` for status display.

```vue
<!-- ✅ Correct -->
<template #col-status="{ row }">
  <AppStatusChip :status="row.status" />
</template>

<!-- ❌ Forbidden -->
<template #col-status="{ row }">
  <v-chip :color="row.status === 'active' ? 'success' : 'error'">{{ row.status }}</v-chip>
</template>
```

---

## Action Columns

Row actions go in the `#actions` slot of `AppDataTable`:

```vue
<template #actions="{ row }">
  <AppButton variant="ghost" size="small" icon="mdi-pencil" @click="edit(row)" />
  <AppButton variant="ghost" size="small" icon="mdi-delete-outline" @click="confirmDelete = row" />
</template>
```

Limit to 2-3 icon actions per row. If more are needed, use a `v-menu` overflow button.

---

## Detail Pages

Detail pages (single record view) use `AppPageHeader` + `AppSection` groups:

```vue
<AppPageLayout :loading="isLoading" :error="isError">
  <AppPageHeader :title="item.name" :description="item.description">
    <template #actions>
      <AppButton variant="secondary" @click="edit">Edit</AppButton>
      <AppButton variant="danger" @click="confirmDelete = item">Delete</AppButton>
    </template>
  </AppPageHeader>

  <AppSection title="Details" divider>
    <!-- fields -->
  </AppSection>

  <AppSection title="Activity" divider>
    <!-- timeline -->
  </AppSection>
</AppPageLayout>
```

---

## Responsive Behaviour

- Tables collapse to cards on mobile via Vuetify's built-in responsive classes
- Filter bars collapse to a filter button on `xs` breakpoint — handled by `AppFilterBar`
- Page headers stack vertically on mobile — handled by `AppPageLayout`

Do not write custom breakpoint CSS for layout — use Vuetify grid/flex utilities.
