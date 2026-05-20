# Route Conventions

**Block:** 6 — Platform Engineering & Modular Expansion  
**Status:** Frozen  
**Date:** 2026-05-20

---

## Overview

Every module owns its own route file. Routes are registered through the module's `ServiceProvider`. No module routes are placed in the global `routes/api.php` or `routes/web.php`.

---

## Route File Location

```
backend/app/Core/{Module}/Routes/api.php
```

Loaded in the module provider via:

```php
public function boot(): void
{
    $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
}
```

`loadRoutesFrom()` does NOT automatically apply the `api` middleware group. Middleware must be applied explicitly inside the route file.

---

## Tenant-Owned Routes

All routes that access tenant-owned models **must** use `TenantRouteMiddleware::STACK`.

```php
use App\Core\Tenancy\Routing\TenantRouteMiddleware;
use App\Core\Projects\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(TenantRouteMiddleware::STACK)->group(function (): void {
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
});
```

### `TenantRouteMiddleware::STACK`

```php
// App\Core\Tenancy\Routing\TenantRouteMiddleware
public const STACK = [
    'auth:sanctum',
    'tenant.resolve',
    SubstituteBindings::class,
    'tenant.member',
];
```

This is the canonical constant. Never inline or reorder these manually.

**The order is a security invariant.** See [ADR-011](../../adr/ADR-011-tenant-route-model-binding.md) and [Tenant-Safe Route Model Binding](../tenancy/route-model-binding.md) for the full explanation.

---

## Non-Tenant Routes

Routes that do not access tenant-owned models (e.g., platform admin, health checks) use an explicit middleware stack appropriate to their security model.

```php
// Example: platform-admin-only route
Route::middleware(['auth:sanctum', 'platform.admin'])->group(function (): void {
    Route::get('/admin/stats', [AdminController::class, 'stats'])->name('admin.stats');
});
```

These routes must not use `tenant.resolve` or `tenant.member`.

---

## URL Conventions

| Rule | Correct | Forbidden |
|---|---|---|
| Kebab-case URL segments | `/project-templates` | `/projectTemplates`, `/project_templates` |
| Plural resource names | `/projects`, `/invoices` | `/project`, `/invoice` |
| Singular route params | `{project}`, `{invoice}` | `{projects}`, `{proj}` |
| No version prefix in module files | `/projects` | `/v1/projects` |

If API versioning is needed in future, it will be applied at the global routing layer — not inside module route files.

---

## HTTP Method Conventions

| Action | Method | URL | Controller Method |
|---|---|---|---|
| List all | `GET` | `/resources` | `index()` |
| Create | `POST` | `/resources` | `store()` |
| Retrieve one | `GET` | `/resources/{resource}` | `show()` |
| Full replace | `PUT` | `/resources/{resource}` | `update()` |
| Partial update | `PATCH` | `/resources/{resource}` | `update()` |
| Delete | `DELETE` | `/resources/{resource}` | `destroy()` |

Use `PATCH` for partial updates (the standard for JSON API partial updates). Use `PUT` only if the request always sends all fields.

---

## Route Naming

Format: `{module}.{action}`

```php
->name('projects.index')
->name('projects.store')
->name('projects.show')
->name('projects.update')
->name('projects.destroy')
```

For nested resources: `{parent}.{child}.{action}`

```php
->name('projects.tasks.index')
->name('projects.tasks.store')
```

---

## Route Model Binding

Implicit route model binding is the default. The route parameter name must match the camelCase model variable name:

```php
Route::get('/projects/{project}', [ProjectController::class, 'show']);
//                  ^^^^^^^^^
//                  matches: public function show(Project $project)
```

For tenant-owned models, `TenantScope` is applied automatically during binding because `SubstituteBindings` runs after `tenant.resolve` in `STACK`. Cross-tenant models resolve to `null` → 404.

Custom binding logic (e.g., binding by `slug` instead of `id`) must be declared in the model:

```php
public function getRouteKeyName(): string
{
    return 'slug';
}
```

---

## Pagination

- Default page size: `15`
- Use `paginate(15)` on list endpoints, never `get()` or `all()`
- Future: `per_page` query parameter support will be implemented at platform level

---

## Response Codes

| Scenario | Code |
|---|---|
| Successful list | `200` |
| Successful retrieve | `200` |
| Successful create | `201` |
| Successful update | `200` |
| Successful delete | `204` (no content) |
| Validation failure | `422` |
| Unauthenticated | `401` |
| Forbidden (policy) | `403` |
| Not found (or cross-tenant 404) | `404` |
| Missing required header | `400` |

---

## Forbidden Patterns

| Pattern | Reason |
|---|---|
| Routes in `routes/api.php` for module resources | Violates module ownership |
| Inlining middleware array instead of using `STACK` | Allows ordering mistakes |
| Route model binding before `tenant.resolve` | Security invariant violation |
| Prefixing `/api` inside module route files | `loadRoutesFrom()` has no prefix; adding it manually creates double-prefix risk |
| `Route::resource()` without explicit name control | Generates unintended routes; use explicit Route declarations |

---

## References

- [ADR-011 — Tenant-Safe Route Model Binding](../../adr/ADR-011-tenant-route-model-binding.md)
- [Tenant-Safe Route Model Binding](../tenancy/route-model-binding.md)
- [service-provider-conventions.md](service-provider-conventions.md)
- [architecture-guardrails.md](architecture-guardrails.md)
