# Core Module Structure

**Block:** 6 — Platform Engineering & Modular Expansion  
**Status:** Frozen  
**Date:** 2026-05-20

---

## Canonical Directory Layout

Every `Core` module follows this directory structure. Not all directories are required for every module — create only what the module needs. The layout is the authoritative reference for AI-assisted scaffolding.

```
backend/app/Core/{Module}/
│
├── Contracts/                      # Public interfaces exposed to other modules
│   └── {Concept}Contract.php
│
├── Console/
│   └── Commands/                   # Artisan commands owned by this module
│       └── {Action}{Model}Command.php
│
├── DataTransferObjects/            # Typed data bags crossing module boundaries
│   └── {Model}Data.php
│
├── Enums/                          # PHP-backed enums for typed states/roles
│   └── {Concept}.php
│
├── Events/                         # Domain events dispatched by this module
│   └── {Model}{PastTense}.php
│
├── Exceptions/                     # Module-specific exception classes
│   └── {Concept}Exception.php
│
├── Http/
│   ├── Controllers/
│   │   └── {Model}Controller.php
│   ├── Requests/
│   │   ├── Store{Model}Request.php
│   │   └── Update{Model}Request.php
│   └── Resources/
│       └── {Model}Resource.php
│
├── Jobs/
│   ├── Concerns/                   # Traits shared across jobs (e.g., HasTenantContext)
│   │   └── Has{Concept}.php
│   ├── Middleware/                 # Job middleware classes
│   │   └── Restore{Concept}.php
│   └── {Action}{Model}Job.php
│
├── Listeners/                      # Event listeners for events from other modules
│   └── On{EventName}.php
│
├── Middleware/                     # HTTP middleware registered by this module
│   └── {Action}{Concept}.php
│
├── Models/
│   ├── Concerns/                   # Eloquent model traits
│   │   └── BelongsTo{Concept}.php
│   └── {Model}.php
│
├── Observers/                      # Eloquent observers
│   └── {Model}Observer.php
│
├── Policies/
│   └── {Model}Policy.php
│
├── Providers/
│   └── {Module}ServiceProvider.php # Single entry point for this module
│
├── Routes/
│   └── api.php                     # All HTTP routes for this module
│
├── Scopes/                         # Eloquent global/local scopes
│   └── {Concept}Scope.php
│
├── Support/                        # Value objects, resolvers, helpers
│   └── {Concept}.php
│
└── README.md                       # Module documentation (required)
```

---

## Real Example: `Core/Projects`

```
backend/app/Core/Projects/
├── Enums/
│   └── ProjectStatus.php
├── Http/
│   ├── Controllers/
│   │   └── ProjectController.php
│   ├── Requests/
│   │   ├── StoreProjectRequest.php
│   │   └── UpdateProjectRequest.php
│   └── Resources/
│       └── ProjectResource.php
├── Models/
│   └── Project.php
├── Policies/
│   └── ProjectPolicy.php
├── Providers/
│   └── ProjectsServiceProvider.php
├── Routes/
│   └── api.php
└── README.md
```

---

## Real Example: `Core/Tenancy`

```
backend/app/Core/Tenancy/
├── Contracts/
│   └── TenantContextContract.php
├── Context/
│   └── TenantContext.php
├── Exceptions/
│   ├── TenantContextNotResolvedException.php
│   └── TenantNotFoundException.php
├── Jobs/
│   ├── Concerns/
│   │   └── HasTenantContext.php
│   └── Middleware/
│       └── RestoreTenantContext.php
├── Middleware/
│   ├── ResolveTenant.php
│   └── ValidateTenantMembership.php
├── Models/
│   ├── Concerns/
│   │   └── BelongsToTenant.php
│   └── Tenant.php
├── Providers/
│   └── TenancyServiceProvider.php
├── Routing/
│   └── TenantRouteMiddleware.php
├── Scopes/
│   └── TenantScope.php
├── Support/
│   ├── MembershipResolver.php
│   ├── TenantCache.php
│   └── TenantLogger.php
└── (no Routes/api.php — Tenancy exposes no direct HTTP API)
```

---

## Layer Contracts

### `Models/`

- One file per Eloquent model.
- Models are the only place `HasFactory`, `SoftDeletes`, `BelongsToTenant`, and casts are declared.
- No business logic in models — they are data containers with relationship declarations.
- Models must override `newFactory()` if the factory is in a non-default location.

### `Http/Controllers/`

- Controllers are thin. They: validate (via Form Request), call one action, return a resource.
- No Eloquent queries directly in controllers — they belong in scoped queries via the model or a dedicated query class.
- No auth logic in controllers — delegate to policies via `$this->authorize()`.
- Return types must be explicit: `JsonResponse`, `Response`, or a `ResourceCollection`.

### `Http/Requests/`

- Every write endpoint (POST, PUT, PATCH) has its own Form Request class.
- Requests validate input and use `Rule::enum()` for enum fields.
- Requests never accept `tenant_id` as user input.
- `authorize()` returns `true` — authorization is handled by the policy layer.

### `Http/Resources/`

- One `{Model}Resource.php` per model.
- Enum fields must be serialized as their `->value` (string), not as the enum object.
- Timestamps are always included.
- Never expose internal IDs or foreign keys that are not meaningful to the API consumer.

### `Policies/`

- One `{Model}Policy.php` per tenant-owned model.
- Policies inject `TenantContextContract` and `MembershipResolver` via constructor.
- Policy methods are: `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.
- Platform admins receive **no automatic bypass** — the policy must explicitly grant or deny.

### `Providers/`

- One provider per module.
- `register()`: container bindings only.
- `boot()`: routes, policies, event listeners, observers.
- Providers never have business logic.

### `Routes/api.php`

- All routes for the module in one file.
- Uses `TenantRouteMiddleware::STACK` for every tenant-owned route group.
- Route names follow `{module}.{action}` convention.

---

## README.md Requirements

Every module must have a `README.md` at its root containing:

1. **Purpose** — one paragraph describing what the module owns.
2. **Routes** — table of HTTP method, URL, controller action, policy method.
3. **Authorization Model** — table of role vs. allowed actions.
4. **Structure** — annotated directory tree.
5. **Tests** — count per group and what each group covers.
6. **Non-Goals** — explicit list of what the module does NOT do.

---

## References

- [module-conventions.md](module-conventions.md)
- [ADR-004 — Module-Oriented Architecture](../../adr/ADR-004-module-oriented-architecture.md)
