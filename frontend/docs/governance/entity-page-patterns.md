# Entity Page Patterns

Canonical patterns for entity-level detail pages.

---

## When to Use AppDetailLayout vs AppPageLayout

| Scenario | Layout |
|---|---|
| List / index page | `AppPageLayout` |
| Create / edit form page | `AppPageLayout` |
| Single entity detail | `AppDetailLayout` |
| Dashboard / overview | `AppPageLayout` |

The key distinction: if the page **centres on one named entity** with a status, metadata, and actions — use `AppDetailLayout`.

---

## Entity Header Composition

Always compose entity headers using these three components together:

```vue
<AppEntityHeader name="Alice Chen" identifier="#USR-001" icon="mdi-account-outline">
  <template #status><AppStatusChip status="active" /></template>
  <template #meta>
    <AppEntityMeta :items="[
      { label: 'Role', value: 'Admin', icon: 'mdi-shield-outline' },
      { label: 'Email', value: 'alice@example.com' },
    ]" />
  </template>
  <template #actions>
    <AppEntityActions>
      <template #primary><AppButton variant="primary">Edit</AppButton></template>
      <template #danger><AppButton variant="danger">Delete</AppButton></template>
    </AppEntityActions>
  </template>
</AppEntityHeader>
```

---

## AppPermissionBoundary — Gating Actions

Wrap any action that requires a permission:

```vue
<!-- Hide entirely if no permission -->
<AppPermissionBoundary permission="users.delete">
  <AppButton variant="danger" @click="confirmDelete = true">Delete</AppButton>
</AppPermissionBoundary>

<!-- Disable instead of hide -->
<AppPermissionBoundary permission="forms.publish" mode="disable">
  <AppButton variant="primary">Publish</AppButton>
</AppPermissionBoundary>
```

Permission strings are namespaced: `module.action` (e.g. `users.delete`, `forms.publish`).

In development / demo, `usePermission()` returns `false` for all checks unless the user has `is_platform_admin: true`. Use `AppPermissionBoundary` consistently even in reference pages — it demonstrates the pattern, and the demo shows all actions because the mock user is a platform admin.

---

## Tabs

Use tabs only when there are 2+ distinct content areas for the same entity.
Do not use tabs for just one content area.

```vue
<template #tabs>
  <v-tabs v-model="activeTab" density="compact">
    <v-tab value="details">Details</v-tab>
    <v-tab value="history">History</v-tab>
    <v-tab value="settings">Settings</v-tab>
  </v-tabs>
</template>

<!-- In main content slot -->
<v-window v-model="activeTab" class="mt-4">
  <v-window-item value="details">...</v-window-item>
  <v-window-item value="history">...</v-window-item>
  <v-window-item value="settings">...</v-window-item>
</v-window>
```

---

## Sidebar Rules

Sidebar accepts secondary/supporting content only:
- Quick stat cards
- Related entity links
- Tags / labels
- System metadata (created_at, updated_at, created_by)

Never put:
- Primary actions in the sidebar
- Main content in the sidebar
- Forms in the sidebar

---

## Activity Timeline Rules

1. Place the timeline in the `#activity` slot — always **below** main content
2. Use `AppSection title="Activity" divider` as the wrapper
3. Use `AppTimelineItem :last="true"` on the final item
4. When no activity: pass `:empty="true"` to `AppActivityTimeline`
5. Timestamp format: `{ dateStyle: 'medium', timeStyle: 'short' }`
6. Events are static data — timeline is NOT realtime

---

## Reference Detail Pages

- `src/modules/reference/pages/UserDetailPage.vue` — full user detail with tabs, sidebar, timeline
- `src/modules/reference/pages/ApprovalDetailPage.vue` — approval with comment thread, gated actions
