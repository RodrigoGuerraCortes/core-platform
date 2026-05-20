<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Providers;

use App\Core\Tenancy\Context\TenantContext;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Middleware\ResolveTenant;
use App\Core\Tenancy\Middleware\ValidateTenantMembership;
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
        // ⚠️  ASYNC WARNING — This binding is request-scoped. Queue workers and
        // scheduled commands do NOT receive this context automatically. Any queued
        // job that needs tenant context must serialize the tenant ID explicitly
        // and re-initialize TenantContext inside its handle() method.
        // This will be addressed in Block 2 queue propagation.
        $this->app->scoped(TenantContextContract::class, fn (): TenantContext => new TenantContext());

        // Alias the concrete class so both TenantContextContract and TenantContext
        // resolve to the same scoped instance. Middleware that type-hints TenantContext
        // directly (e.g. via DI) continues to receive the correct scoped instance.
        $this->app->alias(TenantContextContract::class, TenantContext::class);
    }

    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('tenant.resolve', ResolveTenant::class);
        $router->aliasMiddleware('tenant.member', ValidateTenantMembership::class);
    }
}
