<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FuelPriceController;
use App\Http\Controllers\LubricantController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard.index')->name('dashboard');

    Route::middleware('role:' . implode(',', [User::ROLE_ISLERO, User::ROLE_JEFE_PATIOS, User::ROLE_ADMINISTRADOR]))->group(function () {
        Route::get('/turnos/create', [TurnoController::class, 'create'])
            ->name('turnos.create');

        Route::post('/turnos', [TurnoController::class, 'store'])
            ->name('turnos.store');
    });

    Route::middleware('role:' . implode(',', [User::ROLE_JEFE_PATIOS, User::ROLE_ADMINISTRADOR]))->group(function () {
        Route::get('/cartera', [CarteraController::class, 'index'])
            ->name('cartera.index');
    });

    Route::middleware('role:' . User::ROLE_ADMINISTRADOR)->group(function () {
        Route::resource('fuel-prices', FuelPriceController::class);

        Route::resource('lubricants', LubricantController::class);

        Route::resource('customers', CustomerController::class);

        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/settings.php';
