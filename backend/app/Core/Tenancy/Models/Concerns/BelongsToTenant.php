<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models\Concerns;

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as tenant-owned and enforces organizational isolation.
 *
 * Usage:
 *
 *   class Project extends Model
 *   {
 *       use BelongsToTenant;
 *   }
 *
 * Effects:
 * 1. Registers TenantScope as a global scope — all SELECT queries are
 *    automatically filtered to the current tenant.
 * 2. Auto-fills tenant_id on model creation when TenantContext is resolved.
 *    If tenant_id is already set explicitly, it is not overwritten.
 * 3. Exposes a tenant() BelongsTo relationship.
 *
 * Requirements:
 * - The model's table MUST have a tenant_id column (BIGINT, FK to tenants).
 * - Routes querying this model MUST apply the tenant.resolve middleware.
 *
 * Bypass (platform/internal operations only):
 *
 *   Model::withoutGlobalScope(TenantScope::class)->get()
 *
 * This is the ONLY legitimate bypass. It must be:
 * - Explicit at every call site
 * - Visible in code review
 * - Reserved for platform tooling, support operations, or migrations
 *
 * NEVER:
 * - Use withoutGlobalScopes() (plural) — removes all scopes silently
 * - Assume platform admins bypass this scope automatically — they do not
 * - Call this from queue workers without re-initializing TenantContext first
 *
 * ⚠️  ASYNC WARNING — The auto-fill (creating event) and TenantScope both
 * read from TenantContextContract, which is request-scoped. Queue workers
 * do NOT have an active context. See Block 3 queue propagation strategy.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (self $model): void {
            // Auto-fill tenant_id when not explicitly provided and context is resolved.
            // If tenant_id is already set (e.g. platform code or explicit assignment),
            // it is never overwritten here.
            if ($model->tenant_id === null) {
                $context = app(TenantContextContract::class);

                if ($context->isResolved()) {
                    $model->tenant_id = $context->tenantId();
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
