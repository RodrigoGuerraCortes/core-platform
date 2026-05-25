# Frontend Governance

Core Platform frontend governance rules. These are enforced at lint time — violations block CI.

---

## Enforcement Stack

| Layer | Tool | Purpose |
|---|---|---|
| Import governance | ESLint `no-restricted-imports` | Block raw Vuetify, direct axios, internal sub-paths |
| Component governance | ESLint `no-restricted-syntax` | Block raw `<v-btn>`, `<v-data-table>`, `<v-text-field>` etc. |
| Type safety | TypeScript strict | No `any`, no unsafe assertions |
| Runtime safety | Zod schemas | Validate all API responses at the boundary |
| Test coverage | Vitest + MSW | Every composable has an integration test |

---

## Governance Scope

These rules apply to all files under `src/modules/` and `src/app/`.

The following paths are **explicitly exempt** and may use raw Vuetify directly:

- `src/shared/primitives/` — these ARE the wrappers
- `src/shared/feedback/`
- `src/shared/layouts/`
- `src/shared/forms/`
- `src/shared/table/components/`
- `src/shared/api/` — may import axios
- `src/modules/**/api/` — may import axios
- `src/**/*.test.ts` — may import internals for testing

---

## Public API Surfaces

| Surface | Import path | Contents |
|---|---|---|
| UI primitives | `@/shared/ui` | AppButton, AppCard, AppSection, AppPageHeader, AppConfirmDialog, AppStatusChip, AppToolbarActions, AppPageLayout, AppEmptyState, AppLoadingState, AppErrorState, AppTextField, AppTextarea, AppSelect, AppCheckbox |
| Table system | `@/shared/table` | AppDataTable, AppTableToolbar, AppFilterBar, useTableState, useFilterState, types |
| API client | `@/shared/api/client` | apiClient (default), isAxiosError |
| Shared types | `@/shared/types` | PaginatedResponse, ApiResponse |

**Never import from sub-paths.** If something you need is not exported from the surface, add it to the barrel — do not reach into internals.

---

## Adding New Rules

1. Add to `eslint.config.js` under the appropriate section
2. Document in `docs/governance/forbidden-patterns.md`
3. Fix any existing violations before committing the rule
4. Tests must still pass at zero errors

---

## Running Governance Checks

```bash
npm run lint          # check only (exits non-zero on any error)
npm run lint:fix      # auto-fix safe violations
npm run typecheck     # TypeScript strict check
npm test              # 152 tests must pass
```
