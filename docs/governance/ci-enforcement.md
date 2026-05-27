# CI Enforcement Strategy

> Automated governance enforcement via GitHub Actions
> Last updated: 2026-05-25

---

## Purpose

Governance rules must be **automatically enforced** — not manually policed.
This document defines our CI enforcement strategy.

---

## Enforcement Layers

### Layer 1: ESLint (Real-time)

Developers see violations immediately in their editor via ESLint.

**Enforced rules:**
- No cross-vertical imports
- No MSW browser in vertical modules
- No raw Vuetify components
- No axios imports outside API layer
- Barrel import enforcement (`@/shared/ui`)

**Config:** `frontend/eslint.config.js`

### Layer 2: GitHub Actions (Pre-merge)

CI pipeline runs on every PR and blocks merge if governance violations exist.

**Enforced checks:**
- ESLint governance rules
- TypeScript compilation
- Unit tests (Vitest + Pest)
- Pattern scanning (grep-based)
- Documentation completeness

**Config:** `.github/workflows/governance.yml`

### Layer 3: Pre-commit Hooks (Optional)

Teams may add Husky hooks for local enforcement before push.

**Not implemented yet** — ESLint + CI is sufficient for now.

---

## Forbidden Patterns Scanner

The CI workflow includes grep-based pattern detection for violations that ESLint cannot catch:

### Frontend Scans

| Pattern | Violation | Severity |
|---------|-----------|:--------:|
| `msw/browser` in `src/modules/` | Runtime MSW in vertical | ERROR |
| Cross-vertical imports | Architectural coupling | ERROR |
| Hardcoded vertical names in router | Bypassing experience layer | WARN |

### Backend Scans

| Pattern | Violation | Severity |
|---------|-----------|:--------:|
| `use App\Core\CondoFlow` in DynamicForms | Cross-vertical coupling | ERROR |
| `use App\Core\DynamicForms` in CondoFlow | Cross-vertical coupling | ERROR |
| Models without `TenantScope` | Tenant isolation bypass | WARN |

---

## CI Workflow Stages

```
┌─────────────────────────────────────────────────────────────┐
│  1. Frontend Governance                                     │
│     - TypeScript check                                      │
│     - ESLint with governance rules                          │
│     - Vitest unit tests                                     │
│     - Forbidden pattern scan                                │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  2. Backend Governance                                      │
│     - Composer install                                      │
│     - Pest test suite                                       │
│     - Cross-vertical import scan                            │
│     - TenantScope validation                                │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  3. Documentation Check                                     │
│     - Verify required governance docs exist                 │
│     - Check ADR completeness (future)                       │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  4. Summary                                                 │
│     - Report all check statuses                             │
│     - Block merge if any stage fails                        │
└─────────────────────────────────────────────────────────────┘
```

---

## Failure Response

When a CI check fails:

1. **PR is blocked** — cannot merge until fixed
2. **Clear error message** — explains the violation and points to governance docs
3. **Agent/developer fixes** — corrects the violation
4. **Re-run CI** — validates the fix

---

## Adding New Governance Rules

To add a new rule:

1. **Document the rule** in `docs/governance/`
2. **Add ESLint enforcement** (if applicable) in `eslint.config.js`
3. **Add CI scan** (if ESLint cannot detect it) in `governance.yml`
4. **Test locally** — verify rule catches violations
5. **Commit** — rule is now enforced for all future PRs

---

## Escape Hatches

### Legitimate Exceptions

Some files legitimately violate rules:

| File | Allowed Violation | Why |
|------|------------------|-----|
| `src/mocks/browser.ts` | MSW browser import | Setup file for MSW |
| `src/router/index.ts` | Module route imports | Router aggregates routes |
| `src/shared/primitives/**` | Raw Vuetify | Wrapper implementations |
| Test files (`*.test.ts`) | Cross-imports | Tests may access internals |

Exceptions are defined via ESLint file-specific overrides.

### Emergency Override

If CI blocks a critical hotfix incorrectly:

1. Add `eslint-disable-next-line` with a comment explaining why
2. Create a follow-up issue to fix properly
3. Document in PR description

**This should be rare.** Most "exceptions" are actually violations.

---

## Metrics (Future)

Track governance health:

- **Violation rate** — how often CI blocks PRs
- **Time to fix** — how long violations take to resolve
- **Rule coverage** — % of governance rules automated

---

## Related

- [runtime-modes.md](./runtime-modes.md) — What each mode allows
- [ownership-matrix.md](./ownership-matrix.md) — Who owns what
- [data-source-governance.md](./data-source-governance.md) — Legal data sources
