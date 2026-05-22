<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Exceptions;

/**
 * Thrown when a tenant-owned model is queried without an active TenantContext.
 *
 * This is a DEVELOPER/RUNTIME error, not a user-facing error.
 * It indicates a misconfigured route (missing ResolveTenant middleware) or
 * an in-process query that bypassed the expected tenant lifecycle.
 *
 * To intentionally query across all tenants for platform/internal operations,
 * use the explicit bypass:
 *
 *   Model::withoutGlobalScope(TenantScope::class)->get()
 *
 * NEVER use withoutGlobalScopes() (plural) — that silently removes all scopes.
 */
class TenantContextNotResolvedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'TenantScope: A tenant-owned model was queried without an active tenant context. '
            . 'Ensure ResolveTenant middleware is applied to the route, or explicitly bypass '
            . 'the scope with ->withoutGlobalScope(TenantScope::class) for platform/internal '
            . 'cross-tenant queries. See Core/Tenancy/README.md — Bypass Strategy.'
        );
    }
}
