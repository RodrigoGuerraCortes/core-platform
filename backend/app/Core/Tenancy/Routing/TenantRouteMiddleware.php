<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Routing;

use Illuminate\Routing\Middleware\SubstituteBindings;

/**
 * Platform-standard middleware stack for tenant-owned API routes.
 *
 * Usage:
 *
 *   Route::middleware(TenantRouteMiddleware::STACK)->group(function (): void {
 *       Route::get('/projects', [ProjectController::class, 'index']);
 *       Route::get('/projects/{project}', [ProjectController::class, 'show']);
 *   });
 *
 * Ordering is security-critical — see ADR-011 and docs/features/tenancy/route-model-binding.md.
 *
 * Required order:
 *   1. auth:sanctum       — establishes authenticated user identity
 *   2. tenant.resolve     — resolves X-Tenant-Id, populates TenantContextContract
 *   3. SubstituteBindings — route model binding fires with TenantScope active (cross-tenant → 404)
 *   4. tenant.member      — validates the authenticated user belongs to the resolved tenant
 *
 * ⚠️  NEVER place SubstituteBindings before tenant.resolve.
 *     Doing so allows cross-tenant entity resolution via URL manipulation.
 */
final class TenantRouteMiddleware
{
    /**
     * The canonical middleware stack for tenant-owned API routes.
     *
     * Use this constant in every module that registers routes with tenant-owned
     * route model binding. Do not construct a custom array — it may silently
     * introduce the wrong ordering.
     *
     * @var array<int, class-string|string>
     */
    public const STACK = [
        'auth:sanctum',
        'tenant.resolve',
        SubstituteBindings::class,
        'tenant.member',
    ];
}
