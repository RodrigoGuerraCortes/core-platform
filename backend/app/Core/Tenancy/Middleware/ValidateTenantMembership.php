<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Middleware;

use App\Core\Tenancy\Contracts\TenantContextContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates that the authenticated user belongs to the resolved tenant.
 *
 * Required middleware ordering on any route using this middleware:
 *   1. tenant.resolve  (ResolveTenant — resolves TenantContext)
 *   2. auth:sanctum    (or equivalent — identifies the user)
 *   3. tenant.member   (this middleware)
 *
 * Responsibilities:
 * - Use TenantContext to retrieve the resolved tenant
 * - Check that the authenticated user is a member of the tenant
 * - Reject with 403 if not a member
 *
 * NOT responsible for:
 * - Authentication (401 responses) — that is auth middleware's job
 * - RBAC or permission checks
 * - Role validation beyond membership existence
 */
class ValidateTenantMembership
{
    public function __construct(private readonly TenantContextContract $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->tenant();

        if ($tenant === null) {
            abort(400, 'Tenant context not resolved.');
        }

        $user = $request->user();

        if ($user === null) {
            // This is a misconfigured route — authentication middleware must
            // precede tenant.member in the middleware stack.
            // WHO (auth) must be established before WHERE (tenancy) is enforced.
            throw new \RuntimeException(
                'ValidateTenantMembership requires an authenticated user. '
                . 'Ensure an authentication middleware (e.g. auth:sanctum) '
                . 'runs before tenant.member in the route middleware stack.'
            );
        }

        $isMember = $tenant->users()
            ->where('user_id', $user->getKey())
            ->exists();

        if (! $isMember) {
            abort(403, 'You are not a member of this tenant.');
        }

        return $next($request);
    }
}
