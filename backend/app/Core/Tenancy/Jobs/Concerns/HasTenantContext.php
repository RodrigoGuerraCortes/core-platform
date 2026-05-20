<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Jobs\Concerns;

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Jobs\Middleware\RestoreTenantContext;

/**
 * Enables safe tenant context propagation for queued jobs.
 *
 * Usage:
 *
 *   class ProcessTenantReport implements ShouldQueue
 *   {
 *       use HasTenantContext;
 *
 *       public function __construct()
 *       {
 *           $this->captureTenantContext(); // Call explicitly in constructor
 *       }
 *
 *       public function handle(): void
 *       {
 *           // TenantContextContract is restored by RestoreTenantContext middleware
 *           $tenantId = app(TenantContextContract::class)->tenantId();
 *       }
 *   }
 *
 * Lifecycle:
 *  1. captureTenantContext() is called at dispatch time — serializes tenant_id into the job
 *  2. Job is queued and picked up by a worker
 *  3. RestoreTenantContext middleware re-fetches the Tenant from DB and restores context
 *  4. Job's handle() runs with TenantContext resolved
 *  5. RestoreTenantContext clears TenantContext in a finally block (worker safety)
 *
 * Rules:
 * - ALWAYS call captureTenantContext() in the job constructor
 * - NEVER pass the Tenant model directly — only the ID is serialized
 * - NEVER read TenantContextContract in constructors — it is not available in workers
 *
 * Overriding middleware (when additional job middleware is needed):
 *
 *   public function middleware(): array
 *   {
 *       return [...$this->tenantContextMiddleware(), new RateLimited('api')];
 *   }
 *
 * ⚠️  ASYNC WARNING — This trait is the ONLY sanctioned way to propagate
 * tenant context into queued jobs. Do NOT read headers, session, or auth
 * state inside job handlers to determine the tenant.
 */
trait HasTenantContext
{
    /**
     * The tenant ID captured at dispatch time.
     * Serialized by Laravel's queue infrastructure. Must be public.
     */
    public ?int $tenantId = null;

    /**
     * Captures the current tenant ID from TenantContextContract.
     * MUST be called in the job's constructor while the HTTP context is active.
     */
    public function captureTenantContext(): void
    {
        $this->tenantId = app(TenantContextContract::class)->tenantId();
    }

    /**
     * Returns the job middleware stack including RestoreTenantContext.
     * Override this method in the job class to add additional middleware.
     */
    public function middleware(): array
    {
        return $this->tenantContextMiddleware();
    }

    /**
     * Returns [RestoreTenantContext] for composing into overridden middleware() methods.
     *
     * @return array<RestoreTenantContext>
     */
    protected function tenantContextMiddleware(): array
    {
        return [new RestoreTenantContext()];
    }
}
