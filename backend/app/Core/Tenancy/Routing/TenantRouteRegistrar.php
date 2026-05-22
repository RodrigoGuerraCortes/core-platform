<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Routing;

use Closure;
use Illuminate\Support\Facades\Route;

/**
 * Centralized tenant-safe route registration.
 *
 * Use this class in every module Route/api.php instead of manually referencing
 * TenantRouteMiddleware::STACK. This makes it impossible to forget or misordering
 * the platform middleware stack.
 *
 * Usage:
 *
 *   TenantRouteRegistrar::group(function (): void {
 *       Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
 *       Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
 *   });
 *
 * Middleware stack applied (order is a security invariant — ADR-011):
 *   1. auth:sanctum       — authenticates the user
 *   2. tenant.resolve     — resolves X-Tenant-Id, populates TenantContextContract
 *   3. SubstituteBindings — route model binding fires with TenantScope active
 *   4. tenant.member      — validates user belongs to the resolved tenant
 *
 * @see TenantRouteMiddleware::STACK
 * @see docs/features/tenancy/route-model-binding.md
 * @see docs/adr/ADR-011-tenant-route-model-binding.md
 */
final class TenantRouteRegistrar
{
    /**
     * Register a group of tenant-safe API routes.
     *
     * All routes inside the closure will have the full platform tenant middleware
     * stack applied in the correct, immutable security order.
     */
    public static function group(Closure $routes): void
    {
        Route::middleware(TenantRouteMiddleware::STACK)->group($routes);
    }
}
