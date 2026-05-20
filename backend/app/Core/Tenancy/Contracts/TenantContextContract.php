<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Contracts;

use App\Core\Tenancy\Models\Tenant;

/**
 * Contract for the request-scoped organizational runtime context.
 *
 * Domain services, actions, queries, and controllers MUST type-hint
 * this contract — never the concrete TenantContext class.
 *
 * The canonical access pattern is:
 *
 *   app(TenantContextContract::class)->tenantId()
 *
 * ⚠️  ASYNC WARNING — Queue workers and scheduled commands do NOT
 * automatically inherit this context. TenantContext is request-scoped.
 * If domain logic that calls this contract is dispatched to a queue,
 * the tenant identifier must be explicitly serialized into the job
 * and re-initialized inside the job's handle() method.
 * See Block 2 queue propagation task.
 */
interface TenantContextContract
{
    public function setTenant(Tenant $tenant): void;

    public function tenant(): ?Tenant;

    /**
     * Returns the resolved tenant's primary key, or null if not yet resolved.
     *
     * NOTE: This returns int for BIGINT PKs. If the platform adopts ULIDs
     * in a future block, this return type must be updated to int|string.
     */
    public function tenantId(): ?int;

    public function isResolved(): bool;

    public function clear(): void;
}
