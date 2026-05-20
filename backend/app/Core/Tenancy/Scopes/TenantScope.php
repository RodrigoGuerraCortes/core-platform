<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Scopes;

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Exceptions\TenantContextNotResolvedException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Eloquent scope that isolates queries to the current tenant.
 *
 * Applied automatically to any model that uses the BelongsToTenant trait.
 *
 * Behavior:
 * - Reads tenant ID from TenantContextContract (request-scoped)
 * - Appends WHERE tenant_id = {currentTenantId} to every SELECT query
 * - Throws TenantContextNotResolvedException when no context is resolved
 *
 * Failure strategy:
 * No tenant context = developer/runtime error. The scope NEVER silently
 * returns all rows — that would be a cross-tenant data leak.
 *
 * Explicit bypass (platform/internal operations only):
 *
 *   Model::withoutGlobalScope(TenantScope::class)->get()
 *
 * Rules:
 * - NEVER use withoutGlobalScopes() (plural) — it removes all scopes silently
 * - NEVER bypass automatically for platform admins
 * - Bypass must always be visible at the call site
 *
 * ⚠️  ASYNC WARNING — This scope reads TenantContextContract which is
 * request-scoped. In queue workers and console commands, the context is
 * always null. Never dispatch a queued job that queries tenant-owned models
 * without first serializing the tenant ID and re-initializing the context.
 * See Block 3 queue propagation.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContextContract::class);

        if (! $context->isResolved()) {
            throw new TenantContextNotResolvedException();
        }

        $builder->where($model->qualifyColumn('tenant_id'), $context->tenantId());
    }
}
