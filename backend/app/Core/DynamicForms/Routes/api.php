<?php

declare(strict_types=1);

use App\Core\DynamicForms\Http\Controllers\FormController;
use App\Core\DynamicForms\Http\Controllers\FormSubmissionController;
use App\Core\DynamicForms\Http\Controllers\FormVersionController;
use App\Core\Tenancy\Routing\TenantRouteRegistrar;
use Illuminate\Support\Facades\Route;

// Tenant-safe route group — middleware order is enforced by TenantRouteRegistrar (ADR-011).
// Stack: auth:sanctum → tenant.resolve → SubstituteBindings → tenant.member
TenantRouteRegistrar::group(function (): void {

    // ─── Forms ───────────────────────────────────────────────────────────────
    Route::get('/forms', [FormController::class, 'index'])->name('forms.index');
    Route::post('/forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('/forms/{form}', [FormController::class, 'show'])->name('forms.show');
    Route::patch('/forms/{form}', [FormController::class, 'update'])->name('forms.update');
    Route::post('/forms/{form}/publish', [FormController::class, 'publish'])->name('forms.publish');
    Route::post('/forms/{form}/archive', [FormController::class, 'archive'])->name('forms.archive');

    // ─── Form Versions ───────────────────────────────────────────────────────
    Route::get('/forms/{form}/versions', [FormVersionController::class, 'index'])->name('form-versions.index');
    Route::post('/forms/{form}/versions', [FormVersionController::class, 'store'])->name('form-versions.store');
    Route::get('/form-versions/{version}', [FormVersionController::class, 'show'])->name('form-versions.show');

    // ─── Form Submissions ────────────────────────────────────────────────────
    Route::post('/forms/{form}/submit', [FormSubmissionController::class, 'submit'])->name('form-submissions.submit');
    Route::get('/forms/{form}/submissions', [FormSubmissionController::class, 'index'])->name('form-submissions.index');
    Route::get('/submissions/{submission}', [FormSubmissionController::class, 'show'])->name('form-submissions.show');
});
