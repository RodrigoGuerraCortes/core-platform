# Future Scaffolding

**Block:** 6 — Platform Engineering & Modular Expansion  
**Status:** Planned (not implemented)  
**Date:** 2026-05-20

---

## Overview

This document defines the planned scaffolding system for Core Platform. Its purpose is to make the conventions in [module-conventions.md](module-conventions.md), [core-module-structure.md](core-module-structure.md), and [architecture-guardrails.md](architecture-guardrails.md) enforceable and reproducible — reducing the cost of starting a new module from scratch.

**Nothing in this document is implemented.** This is a specification for a future development phase.

---

## Goals

1. **Reduce module bootstrapping time** from hours to minutes.
2. **Eliminate class-of errors** that require conventions to be held in someone's head.
3. **Make forbidden patterns impossible** — the generator never emits them.
4. **Encode architecture decisions** into generated artifacts, not just documentation.

---

## Planned Generators

### 1. `make:core-module {ModuleName}`

Scaffolds a complete empty Core module with the canonical directory layout.

**Generates:**
```
backend/app/Core/{ModuleName}/
├── Contracts/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Jobs/
│   ├── Concerns/
│   └── Middleware/
├── Middleware/
├── Models/
│   └── Concerns/
├── Policies/
├── Providers/
│   └── {ModuleName}ServiceProvider.php   ← pre-wired with loadRoutesFrom + Gate stubs
├── Routes/
│   └── api.php                           ← pre-wired with TenantRouteMiddleware::STACK
├── Scopes/
├── Support/
└── README.md                             ← pre-filled with correct sections
```

**Also:**
- Registers `{ModuleName}ServiceProvider` in `bootstrap/providers.php` automatically
- Creates `tests/Feature/{ModuleName}/` directory
- Prints checklist of remaining manual steps (create model, migration, factory)

**Invariants encoded:**
- Provider always uses `TenantRouteMiddleware::STACK` — never a manual array
- `register()` and `boot()` stubbed with correct separation
- `README.md` sections match the required template

---

### 2. `make:tenant-model {Module}/{Model}`

Scaffolds a tenant-owned Eloquent model with all required pieces.

**Generates:**
- `Models/{Model}.php` — with `BelongsToTenant`, `HasFactory`, `SoftDeletes` and `$fillable` stub
- Migration — `{timestamp}_create_{table}_table.php` — with `tenant_id` FK, `softDeletes`, `index('tenant_id')`
- `database/factories/{Model}Factory.php` — with `newFactory()` override in model
- `Policies/{Model}Policy.php` — pre-wired with `TenantContextContract`, `MembershipResolver`, role checks
- `Http/Controllers/{Model}Controller.php` — thin controller with `authorize()` and `paginate(15)` on `index()`
- `Http/Requests/Store{Model}Request.php` — `authorize()` returns `true`, `rules()` stub
- `Http/Requests/Update{Model}Request.php` — same, with `sometimes` for all fields
- `Http/Resources/{Model}Resource.php` — stub with `id`, `tenant_id`, `created_at`, `updated_at`
- Route entries added to `Routes/api.php` — 5 standard routes using `{model}` binding
- `Gate::policy()` registration added to provider's `boot()`
- `tests/Feature/{Module}/{Model}ApiTest.php` — skeleton with all required test groups (isolation, authorization, runtime invariants)

**Invariants encoded:**
- `tenant_id` never in `$fillable` or request `rules()`
- `protected $attributes = ['status' => 'active']` if status enum is generated
- `paginate(15)` in `index()`, never `all()` or `get()`
- `TenantScope::class` in `withoutGlobalScope()` calls — never `withoutGlobalScopes()`
- Policy has no `is_platform_admin` bypass

---

### 3. `make:tenant-enum {Module}/{Enum} {case1,case2,...}`

Generates a PHP-backed string enum.

**Generates:**
- `Enums/{Enum}.php` — backed enum with provided cases
- Adds `Rule::enum({Enum}::class)` to the relevant request stub (if `--request` flag provided)
- Adds `'field' => {Enum}::class` to `casts()` in the model

---

### 4. `make:tenant-job {Module}/{Job}`

Generates a queued job with `HasTenantContext` pre-applied.

**Generates:**
- `Jobs/{Job}Job.php` — with `HasTenantContext`, `captureTenantContext()` in constructor, `middleware()` returning `[new RestoreTenantContext()]`
- Test skeleton in `tests/Feature/{Module}/{Job}JobTest.php`

**Invariants encoded:**
- `finally` block in the `handle()` stub (reminder comment — not code, as `RestoreTenantContext` handles cleanup)
- Constructor calls `$this->captureTenantContext()` — never forgotten

---

## Static Analysis Rules (PHPStan)

Future PHPStan custom rules to enforce guardrails at CI level:

| Rule | Guards Against |
|---|---|
| `NoWithoutGlobalScopesRule` | Detects calls to `withoutGlobalScopes()` (plural) anywhere in `app/` |
| `TenantIdInRequestRule` | Detects `tenant_id` as a key in any Form Request `rules()` return array |
| `SubstituteBindingsOrderRule` | Detects route middleware arrays where `SubstituteBindings` precedes `tenant.resolve` |
| `NoPlatformAdminBypassRule` | Detects `is_platform_admin` checks inside Policy classes |
| `NoDirectDomainCouplingRule` | Detects `use App\Domain\{A}\...` in `App\Domain\{B}\...` (cross-domain import) |
| `TenantOwnedModelHasPolicyRule` | Warns when a model using `BelongsToTenant` has no registered policy |

---

## Route Linter

A future Artisan command `route:lint-tenant-safety` that:

1. Iterates all registered routes
2. Identifies routes with `{model}` parameters where the model uses `BelongsToTenant`
3. Checks that the route's middleware stack contains `tenant.resolve` before `SubstituteBindings`
4. Reports violations as errors (for CI) or warnings (for local dev)

This would provide a runtime safety net complementing the static PHPStan rules.

---

## CI Guardrails

Future CI checks to run on every PR:

```yaml
# .github/workflows/platform-guardrails.yml
steps:
  - name: Run PHPStan
    run: ./vendor/bin/phpstan analyse --level=8
  - name: Lint tenant route safety
    run: php artisan route:lint-tenant-safety
  - name: Run full test suite
    run: ./vendor/bin/pest tests/Unit/ tests/Feature/ --no-coverage
  - name: Assert no forbidden patterns
    run: grep -rn "withoutGlobalScopes()" app/ && exit 1 || exit 0
```

---

## Developer Experience Target

When the scaffolding system is complete, adding a new tenant-owned module should take:

| Task | Time |
|---|---|
| Scaffold module structure | < 30 seconds (`make:core-module`) |
| Scaffold tenant-owned model | < 30 seconds (`make:tenant-model`) |
| Write business validation rules | Manual — 15–30 minutes |
| Write business-specific tests | Manual — 30–60 minutes |
| Pass platform convention checks | Automatic (CI + PHPStan) |

**Total to first passing test:** < 1 hour, with all architecture invariants guaranteed by the generator.

---

## Implementation Sequence (When Ready)

When the decision is made to implement scaffolding, the recommended sequence is:

1. **PHPStan rules first** — static analysis pays immediate dividends on existing code
2. **`make:core-module`** — directory scaffolding is simple and high-ROI
3. **`make:tenant-model`** — most complex generator, unlocks consistent module creation
4. **Route linter** — runtime safety net for generated and manual routes
5. **`make:tenant-job`** — lower frequency but important for async-safe modules
6. **CI integration** — wire all tools into PR checks

---

## References

- [module-conventions.md](module-conventions.md)
- [core-module-structure.md](core-module-structure.md)
- [architecture-guardrails.md](architecture-guardrails.md)
- [ADR-007 — AI-Native Engineering](../../adr/ADR-007-ai-native-engineering.md)
- [ADR-009 — Agent Orchestration Roadmap](../../adr/ADR-009-agent-orchestration-roadmap.md)
