<?php

declare(strict_types=1);

use App\Core\IdentityAuth\Http\Controllers\AuthController;
use App\Core\IdentityAuth\Http\Controllers\PasswordController;
use App\Core\IdentityAuth\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/token', [TokenController::class, 'issue'])
    ->name('auth.token.issue');

Route::post('/auth/forgot-password', [PasswordController::class, 'forgotPassword'])
    ->name('auth.password.forgot');

Route::post('/auth/reset-password', [PasswordController::class, 'resetPassword'])
    ->name('auth.password.reset');

// Protected routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'currentUser'])
        ->name('auth.me');

    Route::delete('/auth/token/current', [TokenController::class, 'revokeCurrent'])
        ->name('auth.token.revoke-current');
});
