<?php

declare(strict_types=1);

use App\Core\Projects\Http\Controllers\ProjectController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

// All project routes require authentication, tenant resolution, and tenant membership.
// Middleware order follows ADR-011:
//
//   auth:sanctum → tenant.resolve → SubstituteBindings → tenant.member
//
// SubstituteBindings MUST run AFTER tenant.resolve so that TenantContext is active
// when route model binding resolves {project}. TenantScope filters the query to the
// current tenant, making cross-tenant access return 404 (not 403).
//
// Placing SubstituteBindings before tenant.resolve is a critical security defect.
// See ADR-011 for full rationale.
Route::middleware(['auth:sanctum', 'tenant.resolve', SubstituteBindings::class, 'tenant.member'])
    ->group(function (): void {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    });
