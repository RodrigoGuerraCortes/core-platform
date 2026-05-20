# Service Provider Conventions

**Block:** 6 — Platform Engineering & Modular Expansion  
**Status:** Frozen  
**Date:** 2026-05-20

---

## Overview

A `ServiceProvider` is the **single entry point** for every module. It wires the module into the Laravel application container. No module logic runs at request time unless the provider bootstrapped it.

---

## One Provider Per Module

Every module has exactly one `ServiceProvider`:

```
App\Core\{Module}\Providers\{Module}ServiceProvider
```

Examples:
```
App\Core\Tenancy\Providers\TenancyServiceProvider
App\Core\Projects\Providers\ProjectsServiceProvider
App\Core\IdentityAuth\IdentityAuthServiceProvider
```

The provider is the only class that must be registered in `bootstrap/providers.php`.

---

## Registration in `bootstrap/providers.php`

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Core\IdentityAuth\IdentityAuthServiceProvider::class,
    App\Core\Tenancy\Providers\TenancyServiceProvider::class,
    App\Core\Projects\Providers\ProjectsServiceProvider::class,
    // New modules are appended here
];
```

Order matters for providers that depend on each other. `TenancyServiceProvider` must always precede any module that uses `TenantContextContract`.

---

## Provider Structure

```php
<?php

declare(strict_types=1);

namespace App\Core\{Module}\Providers;

use Illuminate\Support\ServiceProvider;

final class {Module}ServiceProvider extends ServiceProvider
{
    /**
     * Container bindings only.
     * Never load routes, register policies, or attach listeners here.
     */
    public function register(): void
    {
        // container bindings
    }

    /**
     * Bootstrap: routes, policies, observers, event listeners.
     * Never put container bindings here.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        Gate::policy(SomeModel::class, SomePolicy::class);
    }
}
```

---

## `register()` — Container Bindings Only

`register()` must contain **only** container bindings. The application is not fully booted when `register()` runs — routes, events, and policies are not yet available.

### Container Binding Types

| Binding | Method | When to Use |
|---|---|---|
| Request-scoped (new instance per request) | `$this->app->scoped()` | Stateful objects that must be fresh per HTTP request or job (e.g., `TenantContext`, `MembershipResolver`) |
| Application-lifetime singleton | `$this->app->singleton()` | Stateless services shared across the entire process (e.g., `TenantCache`, `TenantLogger`) |
| Transient (new instance per resolve) | `$this->app->bind()` | Lightweight objects where sharing would cause state bleed |
| Interface alias | `$this->app->alias()` | Allows resolving a concrete class by interface name |

### Examples

```php
public function register(): void
{
    // Request-scoped: fresh instance per HTTP request / job
    $this->app->scoped(TenantContextContract::class, fn (): TenantContext => new TenantContext());
    $this->app->alias(TenantContextContract::class, TenantContext::class);

    $this->app->scoped(MembershipResolver::class);

    // Application-lifetime: shared across all requests in the same process
    $this->app->singleton(TenantCache::class);
    $this->app->singleton(TenantLogger::class);
}
```

### Choosing `scoped()` vs `singleton()`

`scoped()` is equivalent to `singleton()` within a single HTTP request or queued job, but is reset at the end of each request. Use it for any object that holds per-request state (tenant context, caches keyed to the current request, user identity).

`singleton()` persists across the whole PHP process (long-running workers, Octane). Use it only for truly stateless infrastructure objects.

**When in doubt, use `scoped()`.**

---

## `boot()` — Bootstrapping

`boot()` runs after all providers have been registered. Use it for:

### Routes

```php
$this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
```

Never register routes in `register()`. Laravel requires the router to be booted first.

### Policies

```php
use Illuminate\Support\Facades\Gate;
use App\Core\Projects\Models\Project;
use App\Core\Projects\Policies\ProjectPolicy;

Gate::policy(Project::class, ProjectPolicy::class);
```

Every tenant-owned model must have a policy. Register all policies in `boot()`.

### Middleware Aliases

Middleware aliases are registered in the `TenancyServiceProvider` because they are platform infrastructure, not module-specific. Module providers do not register new middleware aliases unless they own that middleware.

```php
// In TenancyServiceProvider only:
$this->app->make(\Illuminate\Routing\Router::class)->aliasMiddleware('tenant.resolve', ResolveTenant::class);
$this->app->make(\Illuminate\Routing\Router::class)->aliasMiddleware('tenant.member', ValidateTenantMembership::class);
```

### Event Listeners

```php
use Illuminate\Support\Facades\Event;

Event::listen(ProjectCreated::class, NotifyProjectMembersListener::class);
```

### Observers

```php
Project::observe(ProjectObserver::class);
```

---

## What Providers Must NOT Do

| Forbidden | Reason |
|---|---|
| Perform DB queries | Provider boots before request context exists |
| Access `Auth::user()` | No authenticated user during boot |
| Load routes in `register()` | Router not yet ready |
| Register policies in `register()` | Gate not yet ready |
| Contain business logic | Providers are wiring, not execution |
| `$this->app->make()` inside `register()` | Triggers early resolution, creates race conditions |
| Define module config in `register()` without a dedicated config file | Use `$this->mergeConfigFrom()` for optional config |

---

## Provider Ordering Rules

1. `AppServiceProvider` — always first
2. `TenancyServiceProvider` — must precede any module using `TenantContextContract`
3. Module providers — order is unimportant among peers unless one module's provider depends on another module's container bindings

If a new module depends on `TenantContextContract`, it must be registered after `TenancyServiceProvider`. This is always satisfied by the declaration order in `bootstrap/providers.php`.

---

## Checklist for New Module Provider

- [ ] Class is `final`
- [ ] Class is in `App\Core\{Module}\Providers\`
- [ ] `register()` contains only container bindings
- [ ] `boot()` calls `loadRoutesFrom()` if module has routes
- [ ] `boot()` calls `Gate::policy()` for each module model
- [ ] No DB queries anywhere in provider
- [ ] Registered in `bootstrap/providers.php` after `TenancyServiceProvider` (if tenant-aware)

---

## References

- [module-conventions.md](module-conventions.md)
- [route-conventions.md](route-conventions.md)
- [ADR-002 — Laravel Primary Platform](../../adr/ADR-002-laravel-primary-platform.md)
