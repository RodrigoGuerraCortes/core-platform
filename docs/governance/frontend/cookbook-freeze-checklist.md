# Cookbook Freeze Checklist

**Status: FROZEN as of Block 8.4.2**  
**Date:** 2026-05-25  
**Frozen by:** AI-Native Engineering — Block 8.4.2

This document certifies which patterns are **officially canonical** for the Core Platform frontend. Any future divergence from these patterns requires a new ADR and explicit team approval.

---

## Frozen Canonical Patterns

### ✅ CRUD / Data Table
- **Page:** `UsersExamplePage.vue`
- **Components:** `AppDataTable`, `AppTableToolbar`, `AppFilterBar`
- **Composables:** `useTableState`, `useFilterState`
- **Pattern:** TanStack Query → `useReferenceUsersQuery` → `AppDataTable` → slot-based column rendering
- **Actions:** Row actions via `#actions="{ row }"` slot
- **Pagination:** Server-side via `update:page` / `update:perPage` events
- **Status:** FROZEN

### ✅ Detail Page
- **Page:** `UserDetailPage.vue`, `ApprovalDetailPage.vue`
- **Components:** `AppDetailLayout`, `AppEntityMeta`, `AppEntityActions`, `AppStatusChip`, `AppPermissionBoundary`, `AppActivityTimeline`
- **Tabs:** `v-tabs` + `v-window` inside `AppDetailLayout` default slot
- **Sidebar:** `#sidebar` slot with `AppCard` quick-stats
- **Audit Log:** `v-list` with timestamped change entries in `#audit` tab
- **Status:** FROZEN

### ✅ Approval / Workflow
- **Page:** `ApprovalWorkflowPage.vue`, `ApprovalDetailPage.vue`
- **Pattern:** Status chip → action gating → `AppConfirmDialog` for destructive transitions
- **Optimistic mutation:** `useMutation` with `onSuccess` → `queryClient.invalidateQueries`
- **Status:** FROZEN

### ✅ File Upload
- **Page:** `UploadExamplePage.vue`
- **Pattern:** `useUploadManager` composable → per-file progress `ref` array → `v-progress-linear`
- **Status:** FROZEN

### ✅ Multi-step Wizard
- **Page:** `OnboardingWizardPage.vue`
- **Pattern:** `currentStep` ref → `v-window` per step → `validateStep(n)` before advance → `isSubmitting` flag → success state
- **Footer:** Shared Back / Continue footer driven by `isLastStep` / `isFirstStep`
- **Cancel:** `v-dialog` confirm with "Discard" → router push
- **Status:** FROZEN

### ✅ Permission Visibility
- **Page:** `PermissionsExamplePage.vue`
- **Patterns (4):**
  1. **Hidden** — `v-if="can('permission.string')"` — for destructive/sensitive actions
  2. **Disabled + Tooltip** — `<v-tooltip>` wrapping disabled `<AppButton>` — when users need to know the action exists
  3. **Readonly field** — `:readonly="!can('...')"` + `persistent-hint` — never hide form data
  4. **Locked workflow** — `disabled` on advance button + tooltip explaining requirement
- **Rule:** No inline role strings in templates. All checks through `can()` / `canAny()` / `AppPermissionBoundary`
- **Status:** FROZEN

### ✅ Empty States
- **Component:** `AppEmptyState` with `preset` prop
- **Page:** `EmptyStatesExamplePage.vue`
- **Official presets:** `no-results`, `no-records`, `permission`, `archived`, `disconnected`, `filtered`, `onboarding`
- **Domain variants:** Use preset + override `title`/`description`/`#action` slot
- **Flat mode:** `:flat="true"` when nested inside a card
- **Status:** FROZEN

### ✅ Async States (Loading / Error)
- **Components:** `AppLoadingState`, `AppErrorState`
- **Pattern:** `v-if="isLoading"` → `AppLoadingState` → `v-else-if="isError"` → `AppErrorState` → `v-else` → data
- **Status:** FROZEN

### ✅ Form Patterns
- **Components:** `AppTextField`, `AppTextarea`, `AppSelect`, `AppCheckbox`
- **Validation:** Per-field `errors` object, cleared before revalidation
- **Submit:** `isSubmitting` flag wrapping async call; never double-submit
- **Error mapping:** `mapApiErrors()` for Laravel 422 → field errors
- **Status:** FROZEN

---

## Forbidden Future Divergence

These patterns are **banned** by ESLint and governance:

| Banned | Required Alternative |
|---|---|
| `<v-btn>` in modules | `<AppButton variant="...">` |
| `<v-data-table>` in modules | `<AppDataTable>` |
| `axios.get(...)` in modules | `apiClient` from `@/shared/api/client` |
| `import ... from '@/shared/ui/primitives/...'` | `import ... from '@/shared/ui'` |
| Ad-hoc empty state divs | `<AppEmptyState preset="...">` |
| Inline role checks (`user.role === 'admin'`) | `can()` / `canAny()` / `AppPermissionBoundary` |
| Custom `position: sticky` headers | `<AppDetailLayout sticky-header>` |
| Manual `overflow-x` table wrappers | Built into `AppDataTable` |

---

## Extraction Rules

A pattern becomes a shared primitive when:
1. It appears in **2 or more vertical modules** with the same structure
2. It has been reviewed and accepted by the team
3. A governance doc entry is written before extraction

Extracted primitives live in `src/shared/` and are exported from the barrel (`src/shared/ui/index.ts`).

---

## When New Primitives Are Allowed

New primitives require ALL of the following:
- [ ] The pattern repeats in at least 2 real vertical modules (not just cookbook)
- [ ] The pattern cannot be achieved by configuring existing primitives
- [ ] An ADR or governance PR is opened
- [ ] ESLint rule is added to enforce usage if applicable
- [ ] The primitive is added to the official pattern catalog

---

## Cookbook Freeze Certification

All patterns above have been implemented, lint-clean, and test-verified.  
The cookbook is frozen. Future work builds **vertical systems** using these patterns.  
New patterns are added only when extracted from real vertical implementations.
