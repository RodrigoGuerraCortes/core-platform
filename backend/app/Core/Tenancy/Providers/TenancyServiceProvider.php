<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Providers;

use App\Core\Tenancy\Context\TenantContext;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Middleware\ResolveTenant;
use App\Core\Tenancy\Middleware\ValidateTenantMembership;
use App\Core\Tenancy\Support\TenantCache;
use App\Core\Tenancy\Support\TenantLogger;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind TenantContext as a scoped singleton — one fresh instance per HTTP request
        // lifecycle. In Octane (Swoole/RoadRunner), scoped bindings are automatically
        // flushed between requests.
        //
        // ⚠️  ASYNC WARNING — This binding is request-scoped. Queue workers do NOT
        // receive this context automatically. Use HasTenantContext trait on queued jobs
        // to serialize the tenant ID at dispatch time and RestoreTenantContext job
        // middleware to restore it at execution time. See Jobs/Concerns/HasTenantContext.
        $this->app->scoped(TenantContextContract::class, fn (): TenantContext => new TenantContext());

        // Alias the concrete class so both TenantContextContract and TenantContext
        // resolve to the same scoped instance. Middleware that type-hints TenantContext
        // directly (e.g. via DI) continues to receive the correct scoped instance.
        $this->app->alias(TenantContextContract::class, TenantContext::class);

        // TenantCache and TenantLogger hold a reference to the shared TenantContextContract
        // instance. Because TenantContextContract is mutated in place (setTenant/clear),
        // singleton bindings here correctly reflect live context state throughout the request.
        $this->app->singleton(TenantCache::class);
        $this->app->singleton(TenantLogger::class);
    }

    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('tenant.resolve', ResolveTenant::class);
        $router->aliasMiddleware('tenant.member', ValidateTenantMembership::class);
    }
}
