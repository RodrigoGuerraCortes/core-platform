# Module Conventions

**Block:** 6 — Platform Engineering & Modular Expansion  
**Status:** Frozen  
**Date:** 2026-05-20

---

## Overview

Every piece of application code belongs to a **module**. Modules are self-contained vertical slices — they own their models, routes, policies, providers, and tests. No global technical layers (no root-level `Controllers/`, `Services/`, `Repositories/`).

---

## Module Tiers

| Tier | Namespace | Purpose |
|---|---|---|
| `Core` | `App\Core\{Module}` | Platform infrastructure (Tenancy, Auth, Projects, etc.) |
| `Domain` | `App\Domain\{Module}` | Business-specific domain modules |
| `Shared` | `App\Shared\{Name}` | Pure value objects, contracts shared across all tiers — **no Eloquent, no HTTP** |

### Dependency Rules

```
Domain  →  Core    ✓ allowed
Domain  →  Shared  ✓ allowed
Core    →  Shared  ✓ allowed
Domain  →  Domain  ✗ FORBIDDEN — use events, DTOs, or contracts
Core    →  Core    ✗ FORBIDDEN — use contracts (Core/Tenancy may be imported by others as a special case; prefer interface injection)
```

If a Domain module needs data from another Domain module, it must communicate via a dispatched event or a published contract interface — never by directly importing the other module's model or service.

---

## What Constitutes a Module

A module is a feature or domain bounded context. It must:

1. Have a single `ServiceProvider` as its entry point.
2. Be registered in `bootstrap/providers.php`.
3. Own everything it needs: models, routes, policies, requests, resources, events, jobs.
4. Expose public API only via contracts, events, or HTTP resources.

A module must NOT:

- Reach into another module's models directly (exception: `Core\Tenancy` models via trait injection).
- Register things in global config without owning that config key.
- Store state outside its own scope (no static properties, no global singletons that hold business data).

---

## Naming Conventions

### Namespace

```
App\Core\{PascalCaseModuleName}\{Subdirectory}\{ClassName}
```

Examples:
```
App\Core\Tenancy\Models\Tenant
App\Core\Projects\Http\Controllers\ProjectController
App\Domain\Billing\Models\Invoice
```

### Directories (singular noun, PascalCase)

| Contents | Directory |
|---|---|
| Eloquent models | `Models/` |
| Model traits | `Models/Concerns/` |
| Global Eloquent scopes | `Scopes/` |
| Artisan commands | `Console/Commands/` |
| Queued jobs | `Jobs/` |
| Job middleware | `Jobs/Middleware/` |
| Job concerns/traits | `Jobs/Concerns/` |
| HTTP controllers | `Http/Controllers/` |
| Form requests | `Http/Requests/` |
| API resources | `Http/Resources/` |
| Middleware | `Middleware/` |
| Policies | `Policies/` |
| Service providers | `Providers/` |
| Route files | `Routes/` |
| Enums | `Enums/` |
| Events | `Events/` |
| Listeners | `Listeners/` |
| Observers | `Observers/` |
| Contracts (interfaces) | `Contracts/` |
| Support classes (helpers, resolvers, value objects) | `Support/` |
| Module-specific exceptions | `Exceptions/` |
| Data transfer objects | `DataTransferObjects/` |

### File Naming

| Type | Naming | Example |
|---|---|---|
| Controller | `{Model}Controller.php` | `ProjectController.php` |
| Form request | `{Verb}{Model}Request.php` | `StoreProjectRequest.php`, `UpdateProjectRequest.php` |
| API resource | `{Model}Resource.php` | `ProjectResource.php` |
| Policy | `{Model}Policy.php` | `ProjectPolicy.php` |
| Enum | `{ConceptName}.php` | `ProjectStatus.php` |
| Event | `{Model}{PastTense}.php` | `ProjectCreated.php` |
| Job | `{Verb}{Model}Job.php` | `SyncProjectJob.php` |
| Exception | `{Concept}Exception.php` | `TenantNotFoundException.php` |
| Scope | `{Concept}Scope.php` | `TenantScope.php` |
| Contract | `{Concept}Contract.php` | `TenantContextContract.php` |
| Support class | Descriptive noun | `MembershipResolver.php`, `TenantCache.php` |

---

## Module Checklist

When creating a new module, verify:

- [ ] Namespace follows `App\Core\{Module}` or `App\Domain\{Module}`
- [ ] Single `ServiceProvider` registered in `bootstrap/providers.php`
- [ ] Routes registered in `Routes/api.php` via `loadRoutesFrom()` in provider
- [ ] Policy registered via `Gate::policy()` in provider `boot()`
- [ ] If tenant-owned: model uses `BelongsToTenant` trait
- [ ] If tenant-owned: routes use `TenantRouteMiddleware::STACK`
- [ ] If tenant-owned: policy injects `TenantContextContract` and `MembershipResolver`
- [ ] If queued jobs need tenant: job uses `HasTenantContext` trait
- [ ] Factory created for every model
- [ ] Feature tests in `tests/Feature/{Module}/`
- [ ] `README.md` in module root with purpose, routes, authorization model, test count

---

## References

- [ADR-004 — Module-Oriented Architecture](../../adr/ADR-004-module-oriented-architecture.md)
- [core-module-structure.md](core-module-structure.md)
- [service-provider-conventions.md](service-provider-conventions.md)
