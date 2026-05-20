<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Jobs\Middleware;

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use Closure;

/**
 * Job middleware that restores TenantContext for tenant-aware queued jobs.
 *
 * This middleware is the queue-side counterpart to the ResolveTenant HTTP middleware.
 * It provides the same tenant context guarantee inside worker processes.
 *
 * Lifecycle (per job execution):
 *  1. Read tenant_id from the job (serialized at dispatch time by HasTenantContext)
 *  2. Fetch Tenant from DB — fails clearly if tenant was deleted
 *  3. Restore TenantContext via TenantContextContract
 *  4. Execute the job ($next)
 *  5. ALWAYS clear TenantContext in a finally block — critical for worker reuse safety
 *
 * Worker safety guarantee:
 * Laravel queue workers process multiple jobs in the same PHP process. Without
 * the finally block, a failed job could leave a stale TenantContext that leaks
 * into the next job processed by the same worker. The finally block prevents this.
 *
 * Failure behavior:
 * If the tenant was deleted after the job was dispatched, this middleware throws
 * a RuntimeException, causing the job to fail immediately (no silent data exposure).
 *
 * Jobs without a tenantId (platform-level jobs):
 * If tenantId is null (job did not call captureTenantContext() or was dispatched
 * from a platform context), the middleware skips restoration and runs the job
 * without tenant context. The job's handle() is then responsible for ensuring
 * it does not query tenant-owned models without explicit scope bypass.
 */
class RestoreTenantContext
{
    public function handle(object $job, Closure $next): void
    {
        $context = app(TenantContextContract::class);
        $tenantId = $job->tenantId ?? null;

        if ($tenantId !== null) {
            $tenant = Tenant::find($tenantId);

            if ($tenant === null) {
                throw new \RuntimeException(
                    "RestoreTenantContext: tenant #{$tenantId} not found during job restoration. "
                    . 'The tenant may have been soft-deleted after this job was dispatched. '
                    . 'Implement a deleted() handler on the job to handle this case gracefully.'
                );
            }

            $context->setTenant($tenant);
        }

        try {
            $next($job);
        } finally {
            // CRITICAL: always clear context after job execution.
            // Workers reuse the same PHP process across multiple jobs.
            // Stale context from a previous job would leak into the next job.
            $context->clear();
        }
    }
}
