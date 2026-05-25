# Responsive Shell Rules

Frozen responsive behaviour for Core Platform. These rules apply before CondoFlow Lite and MiniHIS Lite begin.

---

## Breakpoint Definitions (Vuetify defaults)

| Name | Width | Context |
|---|---|---|
| `xs` | < 600px | Mobile portrait |
| `sm` | 600–959px | Mobile landscape / small tablet |
| `md` | 960–1279px | Tablet / small laptop |
| `lg` | 1280–1919px | Desktop |
| `xl` | ≥ 1920px | Wide desktop |

Access via `useDisplay()` from Vuetify.

---

## Sidebar

| Breakpoint | Behaviour |
|---|---|
| `lg+` | Permanent sidebar (always visible, never overlays) |
| `sm`–`md` | Temporary / collapsible (toggle via topbar icon) |
| `xs` | Overlay drawer (closes on navigation) |

Implementation lives in `AppSidebar.vue` — do not re-implement drawer logic in page components.

Sidebar width: **240px** — do not change per module.

---

## Content Width

Page content is rendered inside Vuetify's `v-main`. No max-width is enforced at the shell level.
Individual pages may use `v-container` with `max-width` if the content is form-heavy:

```vue
<!-- For data-dense pages: no container restriction -->
<AppPageLayout>
  <AppDataTable ... />
</AppPageLayout>

<!-- For form pages: constrain to readable width -->
<AppPageLayout>
  <v-container max-width="720">
    <AppTextField ... />
  </v-container>
</AppPageLayout>
```

---

## Topbar

Fixed height: `64px`. Content: tenant name (mobile), breadcrumb (desktop), user avatar.
Implemented in `AppTopbar.vue`. Do not add module-specific content to the topbar.

---

## Table Responsive Strategy

`AppDataTable` (wrapping `v-data-table-server`) handles horizontal scroll on small screens automatically.

Page-level strategy for mobile:
- Tables scroll horizontally — do not hide columns via JS on mobile
- Action columns always appear last and are sticky-right (future enhancement)
- Per-page default is 15; do not override below 10

---

## Detail Page — Two-Column Grid

`AppDetailLayout with-sidebar`:

| Breakpoint | Layout |
|---|---|
| `xs`–`md` | Single column (sidebar below main) |
| `lg+` | Two-column: `1fr 320px` |

Sidebar content stacks below main content on mobile — ensure it reads sensibly in that order.

---

## Action Collapse

`AppEntityActions` collapses `#secondary` and `#danger` into an overflow menu on `sm-` screens.

Rule: **Never put more than one primary action visible on mobile.** Everything else goes in the overflow menu.

---

## Spacing Constants

| Context | Spacing |
|---|---|
| Page padding (shell) | `pa-4` on `xs`, `pa-6` on `md+` |
| Section gap | `mb-6` |
| Card padding | `pa-4` (default), `pa-6` (spacious) |
| Header → content gap | `mt-4` |
| Form field gap | `mb-4` per field |

These come from Vuetify's spacing scale — use utility classes, not custom CSS.

---

## Forbidden Responsive Patterns

- Do not use `display: none` in custom CSS for responsive hiding — use Vuetify's `d-{breakpoint}-{value}` utilities
- Do not implement custom drawer logic outside `AppSidebar.vue`
- Do not override sidebar width per module
- Do not add `position: fixed` elements inside page content
