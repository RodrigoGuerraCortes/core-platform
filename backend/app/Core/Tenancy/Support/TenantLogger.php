<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Support;

use App\Core\Tenancy\Contracts\TenantContextContract;

/**
 * Provides tenant metadata for log enrichment.
 *
 * Returns a context array suitable for use with Laravel's Log::withContext()
 * or any structured logging system.
 *
 * Usage in HTTP context (automatic via ResolveTenant middleware):
 *   Log::withContext(app(TenantLogger::class)->context());
 *
 * Usage in queue context (manual, after tenant restoration):
 *   Log::withContext(app(TenantLogger::class)->context());
 *   // Call this at the start of the job's handle() method.
 *
 * Usage in domain code (ad-hoc enrichment):
 *   Log::withContext(app(TenantLogger::class)->context())->info('Processing...');
 *
 * Returns an empty array when no context is resolved — never throws.
 * This allows safe use in platform-level code where tenant may not be set.
 */
class TenantLogger
{
    public function __construct(private readonly TenantContextContract $context) {}

    /**
     * Returns log context fields for the current tenant.
     * Returns an empty array when no tenant context is resolved.
     *
     * @return array{tenant_id?: int, tenant_slug?: string}
     */
    public function context(): array
    {
        if (! $this->context->isResolved()) {
            return [];
        }

        $tenant = $this->context->tenant();

        return [
            'tenant_id'   => $tenant->id,
            'tenant_slug' => $tenant->slug,
        ];
    }
}
