# Empty State Rules

Every empty state on Core Platform must use `AppEmptyState`.
Ad-hoc empty states (blank divs, custom text blocks, inline `v-if` with paragraph) are forbidden.

---

## Preset Scenarios

Use the `preset` prop for all standard cases:

| Preset | When to use |
|---|---|
| `no-results` | A search query returned zero records |
| `no-records` | The collection is genuinely empty (first-time) |
| `permission` | User cannot see this content due to access rules |
| `archived` | Items exist but are all archived |
| `disconnected` | A backend service / integration is offline |
| `filtered` | Active filters produced zero results |
| `onboarding` | First-time setup prompt for a new feature |

```vue
<!-- ✅ Correct — preset handles copy and icon -->
<AppEmptyState preset="no-results">
  <template #action>
    <AppButton variant="ghost" @click="table.clearFilters">Clear filters</AppButton>
  </template>
</AppEmptyState>

<!-- ✅ Custom copy overriding a preset -->
<AppEmptyState
  preset="no-records"
  title="No forms yet"
  description="Create your first form to start collecting data."
>
  <template #action>
    <AppButton variant="primary" @click="openCreate">New Form</AppButton>
  </template>
</AppEmptyState>

<!-- ❌ Forbidden — ad-hoc empty state -->
<div v-if="items.length === 0" class="text-center py-12">
  <p>No items found</p>
</div>
```

---

## CTA Placement Rules

- Primary CTA goes in the `#action` slot
- Only one CTA per empty state — do not put two buttons
- For `no-results` / `filtered`: CTA should clear or adjust the filter
- For `no-records` / `onboarding`: CTA should open the creation flow
- For `permission` / `archived` / `disconnected`: CTA is optional (retry, contact admin)

---

## AppDataTable Integration

`AppDataTable` has built-in `#empty-action` and `#error-action` slots that use `AppEmptyState` internally. Use those slots rather than conditionally rendering `AppEmptyState` outside the table.

```vue
<AppDataTable ...>
  <template #empty-action>
    <AppButton @click="table.clearFilters">Clear filters</AppButton>
  </template>
</AppDataTable>
```

---

## `flat` Prop

Use `:flat="true"` when the empty state sits inside a card or section that already provides the visual boundary. Use the default (outlined card) when the empty state is standalone.

---

## Icon Consistency

Do not override the icon unless the preset icon is clearly wrong for the context. The preset icons were chosen to match the semantics of each scenario.

Approved custom icons:
- Domain-specific entities (e.g. `mdi-form-outline` for forms)
- Feature-specific onboarding (e.g. `mdi-hospital-building` for HIS)

Forbidden: random decorative icons that don't communicate meaning.
