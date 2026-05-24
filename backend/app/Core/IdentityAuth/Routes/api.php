<?php

declare(strict_types=1);

use App\Core\IdentityAuth\Http\Controllers\AuthController;
use App\Core\IdentityAuth\Http\Controllers\EmailVerificationController;
use App\Core\IdentityAuth\Http\Controllers\PasswordController;
use App\Core\IdentityAuth\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

// ── SPA session authentication (Sanctum stateful) ─────────────────────────────
// Login/logout are session-based. No tokens are issued.
// EnsureFrontendRequestsAreStateful (added via $middleware->statefulApi() in
// bootstrap/app.php) provides session + CSRF support on stateful domains.
Route::post('/auth/login', [AuthController::class, 'login'])
    ->name('auth.session.login');

// Public routes
Route::post('/auth/token', [TokenController::class, 'issue'])
    ->name('auth.token.issue');

Route::post('/auth/forgot-password', [PasswordController::class, 'forgotPassword'])
    ->name('auth.password.forgot');

Route::post('/auth/reset-password', [PasswordController::class, 'resetPassword'])
    ->name('auth.password.reset');

// Protected routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->name('auth.session.logout');
    Route::get('/auth/me', [AuthController::class, 'currentUser'])
        ->name('auth.me');

    Route::delete('/auth/token/current', [TokenController::class, 'revokeCurrent'])
        ->name('auth.token.revoke-current');

    Route::get('/auth/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('auth.email.verify');

    Route::post('/auth/resend-verification', [EmailVerificationController::class, 'resendVerification'])
        ->name('auth.email.resend-verification');
});
