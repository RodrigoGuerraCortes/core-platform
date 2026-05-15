<?php

declare(strict_types=1);

use App\Core\IdentityAuth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'currentUser'])
        ->name('auth.me');
});
