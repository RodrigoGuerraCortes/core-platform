# Wrappers Policy

All UI components in module and app code must go through canonical wrappers.
Wrappers live in `src/shared/` and are imported via `@/shared/ui` or `@/shared/table`.

---

## Why Wrappers

1. **Consistency** — spacing, density, rounding, and colour palette applied uniformly
2. **Replaceability** — changing Vuetify version only requires updating wrappers, not every page
3. **Governance** — ESLint catches raw Vuetify usage immediately
4. **AI safety** — AI agents have a small, stable set of approved components

---

## Current Wrapper Inventory

### Layout
| Wrapper | Replaces | Key props |
|---|---|---|
| `AppPageLayout` | bare `<v-container>` + manual loading/error | `title`, `description`, `loading`, `error`, `error-message` |
| `AppSection` | `<div class="section">` | `title`, `description`, `divider`, `flush` |
| `AppPageHeader` | ad-hoc `<h1>` + actions row | `title`, `description`, slot: `actions` |

### Primitives
| Wrapper | Replaces | Key props |
|---|---|---|
| `AppButton` | `<v-btn>` | `variant` (primary/secondary/ghost/danger/tonal), `loading`, `size` |
| `AppCard` | `<v-card>` | slots: `header`, `default` |
| `AppStatusChip` | `<v-chip color="...">` | `status` (preset), `label`, `color`, `icon` |
| `AppConfirmDialog` | ad-hoc `<v-dialog>` | `title`, `description`, `confirm-label`, `confirm-variant`, `loading` |
| `AppToolbarActions` | `<div class="d-flex gap-2">` | `align-end` |

### Feedback
| Wrapper | Replaces | When |
|---|---|---|
| `AppLoadingState` | inline `<v-progress-circular>` | full-section loading |
| `AppEmptyState` | ad-hoc empty div | zero results |
| `AppErrorState` | ad-hoc error div | query error |

### Forms
| Wrapper | Replaces |
|---|---|
| `AppTextField` | `<v-text-field>` |
| `AppTextarea` | `<v-textarea>` |
| `AppSelect` | `<v-select>` |
| `AppCheckbox` | `<v-checkbox>` |

### Table
| Wrapper | Replaces |
|---|---|
| `AppDataTable` | `<v-data-table-server>` |
| `AppTableToolbar` | ad-hoc toolbar row |
| `AppFilterBar` | ad-hoc filter controls |

---

## Wrapper Design Principles

1. **Thin** — wrappers add props/slots, not logic
2. **Slot-driven** — prefer slots over prop explosion
3. **No style overrides** — platform density/spacing comes from Vuetify theme
4. **Composable** — wrappers should work together naturally
5. **No new abstractions** — if you need a new wrapper, it must replace something that was repeated 3+ times

---

## Adding a New Wrapper

1. Create the component in `src/shared/primitives/`, `src/shared/feedback/`, or `src/shared/layouts/`
2. Export from the sub-package barrel (`index.ts`)
3. Re-export from `src/shared/ui/index.ts`
4. Add the banned raw element to `eslint.config.js` `no-restricted-syntax`
5. Document in this file and in `forbidden-patterns.md`
6. Fix existing violations before committing

---

## What NOT to Do

- Do not create wrappers for one-off cases — use Vuetify directly in the shared wrapper
- Do not add business logic to wrappers — they must stay presentational
- Do not create wrapper hierarchies deeper than 2 levels
- Do not duplicate Vuetify's own composition props — pass them through
