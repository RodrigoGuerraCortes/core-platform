<?php

declare(strict_types=1);

use App\Core\Projects\Http\Controllers\ProjectController;
use App\Core\Tenancy\Routing\TenantRouteRegistrar;
use Illuminate\Support\Facades\Route;

// Tenant-safe route group — middleware order is enforced by TenantRouteRegistrar (ADR-011).
// Stack: auth:sanctum → tenant.resolve → SubstituteBindings → tenant.member
TenantRouteRegistrar::group(function (): void {
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
});
