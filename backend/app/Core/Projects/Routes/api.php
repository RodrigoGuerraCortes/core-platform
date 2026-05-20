<?php

declare(strict_types=1);

use App\Core\Projects\Http\Controllers\ProjectController;
use App\Core\Tenancy\Routing\TenantRouteMiddleware;
use Illuminate\Support\Facades\Route;

// Uses the platform-standard tenant middleware stack (ADR-011).
// See docs/features/tenancy/route-model-binding.md for the security rationale.
Route::middleware(TenantRouteMiddleware::STACK)
    ->group(function (): void {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    });
