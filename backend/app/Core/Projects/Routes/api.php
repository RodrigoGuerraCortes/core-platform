<?php

declare(strict_types=1);

use App\Core\Projects\Http\Controllers\ProjectController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

// All project routes require authentication, tenant resolution, and tenant membership.
// Middleware order: auth → resolve tenant → validate membership → bind models.
//
// SubstituteBindings is placed LAST so that route model binding for {project} fires
// AFTER TenantContext is set by tenant.resolve. Without this explicit ordering,
// {project} binds without TenantScope active, creating cross-tenant data leakage.
//
// Cross-tenant access via {project} route model binding returns 404 (not 403) —
// the existence of a resource in another tenant is never revealed.
Route::middleware(['auth:sanctum', 'tenant.resolve', 'tenant.member', SubstituteBindings::class])
    ->group(function (): void {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    });
