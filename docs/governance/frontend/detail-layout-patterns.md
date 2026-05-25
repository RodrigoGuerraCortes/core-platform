# Detail Layout Patterns

Canonical structure for entity detail pages on Core Platform.

---

## Component: AppDetailLayout

Use `AppDetailLayout` as the root of every entity detail page.

```vue
<AppDetailLayout
  title="Alice Chen"
  subtitle="alice@example.com"
  :breadcrumbs="breadcrumbs"
  :loading="isLoading"
  :error="isError"
  with-sidebar
  sticky-header
>
  <template #status><AppStatusChip status="active" /></template>
  <template #metadata><AppEntityMeta :items="metaItems" /></template>
  <template #actions><AppEntityActions>...</AppEntityActions></template>
  <template #tabs><v-tabs v-model="tab">...</v-tabs></template>

  <!-- Main content -->
  <AppSection title="Details">...</AppSection>

  <template #sidebar>...</template>
  <template #activity><AppActivityTimeline>...</AppActivityTimeline></template>
</AppDetailLayout>
```

---

## Section Order

Every detail page must follow this vertical order:

1. Breadcrumbs (top of sticky header)
2. Status chip + Title + Subtitle
3. Metadata strip (AppEntityMeta)
4. Tabs (if tabbed)
5. `[divider]`
6. Main content (default slot)
7. Sidebar (right rail, desktop only)
8. Activity / Audit timeline (full width, always last)

**Never invert this order.** Consistency is the governance contract.

---

## Sticky Header

`sticky-header` is on by default. The header section sticks at `top: 0` with `z-index: 10`.

- Background colour is `bg-background` — ensure the page background matches.
- Do not add additional sticky elements inside a detail page.

---

## Two-Column Layout

```
:with-sidebar="true"
```

On `lg+` screens: `1fr 320px` grid.
On `< lg`: sidebar collapses below main content (single column).

Sidebar content must be secondary information only — metadata cards, related links, quick stats.
**Never put primary actions in the sidebar.**

---

## AppEntityHeader

Use inside a card or directly in the main content area to present entity identity:

```vue
<AppEntityHeader
  name="Alice Chen"
  identifier="#USER-001"
  icon="mdi-account-outline"
  icon-color="primary"
>
  <template #status><AppStatusChip status="active" /></template>
  <template #meta><AppEntityMeta :items="metaItems" /></template>
  <template #actions><AppEntityActions>...</AppEntityActions></template>
</AppEntityHeader>
```

---

## AppEntityActions — Mobile Collapse

`AppEntityActions` hides `#secondary` and `#danger` slots on mobile, putting them in an overflow menu (`mdi-dots-vertical`).

The `#primary` slot is always visible.

Rule: **One primary action maximum.** Secondary actions go in `#secondary`. Destructive in `#danger`.

---

## Metadata Strip — AppEntityMeta

Use the declarative `items` prop for metadata rows:

```ts
const metaItems: MetaItem[] = [
  { label: 'Department', value: 'Engineering', icon: 'mdi-domain' },
  { label: 'Joined', value: '12 Jan 2025', icon: 'mdi-calendar-outline' },
]
```

Never build ad-hoc `<div class="d-flex">` metadata rows.

---

## Activity Timeline

Always use `AppActivityTimeline` + `AppTimelineItem` for audit/activity feeds.

```vue
<template #activity>
  <AppSection title="Activity" divider>
    <AppActivityTimeline :empty="events.length === 0">
      <AppTimelineItem
        v-for="(e, i) in events"
        :key="e.id"
        :icon="e.icon"
        :label="e.label"
        :actor="e.actor"
        :timestamp="e.created_at"
        :last="i === events.length - 1"
      />
    </AppActivityTimeline>
  </AppSection>
</template>
```

`last` prop removes the connector line on the final item.

---

## Reference Implementation

See `src/modules/reference/pages/UserDetailPage.vue` and `ApprovalDetailPage.vue` for complete canonical examples.
