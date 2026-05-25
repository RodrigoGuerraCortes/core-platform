# Vertical Extraction Rules

**Governance document — Core Platform Frontend**  
**Status:** Active from Block 8.4.2 (cookbook freeze)

---

## Philosophy: Vertical-First

> Build in the vertical. Extract to shared only after the second repetition.

The Core Platform uses a **vertical-first** approach to component development. This means:

1. Build the feature in the module that needs it first
2. When a second module needs the same pattern, compare the two implementations
3. Extract a shared primitive only if the pattern is stable and generalizable

**Never pre-extract.** Pre-extraction creates abstractions without evidence, adds cognitive overhead, and locks future modules into premature APIs.

---

## The "Repeat Twice" Rule

A piece of UI or logic becomes a candidate for extraction when:

> The **identical structural pattern** appears in **two independent vertical modules**.

### What counts as "identical structural pattern"

- Same component hierarchy with the same props/slots
- Same interaction model (same events, same state machine)
- Same visual structure (not just similar copy)

### What does NOT count

- Same icon used in two places → not extraction-worthy
- Same color semantics → already handled by Vuetify theme tokens
- Two components that happen to use `AppCard` → not extraction-worthy
- Two pages that both have a table → only extract the *table pattern*, not a domain-specific wrapper

---

## Extraction Process

When a pattern qualifies for extraction (appeared twice):

### Step 1 — Document it
Open a PR that adds an entry to `docs/governance/official-pattern-catalog.md` describing:
- What the pattern is
- Where it appeared twice
- The proposed shared component name and location

### Step 2 — Design the API
Define the props, slots, and emits. Answer:
- What is always the same? (put in the component)
- What varies between usages? (make a prop or slot)
- What should the caller never control? (hardcode it)

### Step 3 — Implement in `src/shared/`
- Component: `src/shared/<category>/<ComponentName>.vue`
- Export from `src/shared/ui/index.ts`
- Add ESLint rule if applicable

### Step 4 — Replace both call sites
Refactor both original usages to use the new shared component.  
**Do not leave the old pattern in place.** If it's shared, it must be used everywhere.

### Step 5 — Add a test
At minimum, one smoke test for the shared component. Props, slots, and emits should be tested if complex.

---

## Governance Boundaries

### `src/shared/` — Platform primitives

- Available to ALL modules
- Must be generic enough for any vertical
- Must have governance approval before creation
- Must be exported from `src/shared/ui/index.ts`
- Must not import from any specific module

### `src/modules/<module>/` — Vertical code

- Private to the module
- May use any `src/shared/` primitive
- Must NOT import from another module's internals
- Can have module-local components that are not shared

### The barrel rule

All shared imports must go through barrels:
```ts
// ✅ Correct
import { AppButton, AppCard } from '@/shared/ui'
import { useTableState } from '@/shared/table'

// ❌ Forbidden (ESLint will catch this)
import AppButton from '@/shared/primitives/AppButton.vue'
import { useTableState } from '@/shared/table/composables/useTableState'
```

---

## What Stays in the Module

These patterns should **NOT** be extracted even if they repeat:

| Pattern | Reason |
|---|---|
| Domain-specific column definitions | Column keys/labels are domain-specific |
| Domain-specific empty state copy | Title/description is content, not structure |
| Domain-specific breadcrumb arrays | Data, not structure |
| Domain-specific MSW handlers | Module-private mocks |
| Domain-specific fixtures | Module-private test data |
| Domain-specific query keys | Would create coupling between modules |

---

## Versioning Shared Primitives

When a shared primitive needs to change its API:

1. **Non-breaking change** (new optional prop, new slot): just add it
2. **Breaking change** (rename prop, change slot name): follow this process:
   - Add the new API alongside the old (deprecated)
   - Update all call sites in one PR
   - Remove the old API in a follow-up PR
   - Never leave deprecated APIs in place for more than one sprint

---

## Speed vs. Abstraction Trade-off

When in doubt, **copy the pattern into the new module first**. It is always faster to extract later than to build a wrong abstraction now.

> Duplication is cheaper than the wrong abstraction.
>
> — Sandi Metz

The two-repetition rule exists precisely because the second implementation reveals what truly varies vs. what is invariant. The first implementation cannot tell you that.

---

## Examples

### Good extraction candidate

`AppDataTable` — appeared in `reference/UsersExamplePage` (pagination + sort + filter) and `dynamic-forms/FormSubmissionsPage` (same pattern, different columns). Extracted with `columns` prop and slot-based cell rendering.

### Bad extraction candidate

`ReferenceUserStatusBadge` — appears only in `reference/` module. Even if needed in `dynamic-forms/` later, wait until that second usage materializes before extracting.

### Premature abstraction (do not do this)

`AppWizardLayout` — tempting after building `OnboardingWizardPage`, but the wizard pattern should be proven in a real vertical (patient intake, condo onboarding) before a shared layout is built. The reference page uses local state; extraction is premature.
