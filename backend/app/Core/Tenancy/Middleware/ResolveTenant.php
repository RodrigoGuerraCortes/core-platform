<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Middleware;

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Support\TenantLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant from the X-Tenant-Id request header.
 *
 * Responsibilities:
 * - Read the X-Tenant-Id header
 * - Validate tenant existence (rejects missing, invalid, soft-deleted)
 * - Initialize TenantContext
 *
 * Platform route bypass:
 * Platform routes (admin, health, internal tooling) bypass tenant resolution
 * simply by NOT including the tenant.resolve middleware in their stack.
 * There is no dedicated platform middleware group — bypass is explicit by omission.
 *
 * NOT responsible for:
 * - Membership validation
 * - Permission checks
 * - Business authorization
 */
class ResolveTenant
{
    public function __construct(
        private readonly TenantContextContract $context,
        private readonly TenantLogger $logger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-Id');

        if ($tenantId === null) {
            abort(400, 'Missing X-Tenant-Id header.');
        }

        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            abort(404, 'Tenant not found.');
        }

        $this->context->setTenant($tenant);

        // Enrich all subsequent log entries for this request with tenant metadata.
        // This enriches the shared log context for the duration of the request only.
        Log::withContext($this->logger->context());

        return $next($request);
    }
}
