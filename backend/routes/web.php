<?php

use App\Core\Shared\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ─── Health Endpoints ─────────────────────────────────────────────────────────
// /up is handled by Laravel's built-in health route (see bootstrap/app.php).
// /health and /health/detailed are custom platform endpoints.
Route::get('/health', HealthController::class);
Route::get('/health/detailed', [HealthController::class, 'detailed']);
