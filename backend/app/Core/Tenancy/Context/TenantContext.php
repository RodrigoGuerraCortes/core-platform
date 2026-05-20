<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Context;

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;

/**
 * Request-scoped runtime tenant context.
 *
 * This is the ONLY legitimate way to access the current tenant
 * inside domain logic, actions, queries, and controllers.
 *
 * Canonical access pattern:
 *   app(TenantContextContract::class)->tenantId()
 *
 * Domain services MUST type-hint TenantContextContract, not this class.
 *
 * ⚠️  ASYNC WARNING — Queue workers and scheduled commands do NOT
 * inherit this context automatically. This class is scoped to the
 * HTTP request lifecycle only. Never call app(TenantContextContract::class)
 * inside a queued job without first explicitly initializing the context
 * from a serialized tenant identifier. See Block 2 queue propagation.
 */
class TenantContext implements TenantContextContract
{
    private ?Tenant $tenant = null;

    public function setTenant(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function tenantId(): ?int
    {
        return $this->tenant?->id;
    }

    public function isResolved(): bool
    {
        return $this->tenant !== null;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }
}
