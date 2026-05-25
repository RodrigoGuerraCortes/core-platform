<?php

declare(strict_types=1);

use App\Core\CondoFlow\Http\Controllers\BuildingController;
use App\Core\CondoFlow\Http\Controllers\DashboardController;
use App\Core\CondoFlow\Http\Controllers\ResidentController;
use App\Core\CondoFlow\Http\Controllers\TicketController;
use App\Core\CondoFlow\Http\Controllers\UnitController;
use App\Core\Tenancy\Routing\TenantRouteRegistrar;
use Illuminate\Support\Facades\Route;

TenantRouteRegistrar::group(function (): void {
    Route::prefix('condoflow')->name('condoflow.')->group(function (): void {
        // Dashboard
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // Buildings
        Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings.index');
        Route::post('/buildings', [BuildingController::class, 'store'])->name('buildings.store');
        Route::get('/buildings/{building}', [BuildingController::class, 'show'])->name('buildings.show');
        Route::patch('/buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
        Route::delete('/buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy');

        // Units
        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::get('/units/{unit}', [UnitController::class, 'show'])->name('units.show');
        Route::patch('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

        // Residents
        Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
        Route::post('/residents', [ResidentController::class, 'store'])->name('residents.store');
        Route::get('/residents/{resident}', [ResidentController::class, 'show'])->name('residents.show');
        Route::patch('/residents/{resident}', [ResidentController::class, 'update'])->name('residents.update');
        Route::delete('/residents/{resident}', [ResidentController::class, 'destroy'])->name('residents.destroy');

        // Tickets
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    });
});
