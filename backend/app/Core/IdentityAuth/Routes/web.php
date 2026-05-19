<?php

declare(strict_types=1);

use App\Core\IdentityAuth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->name('auth.login');

    Route::middleware('auth')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('auth.logout');
    });
});
