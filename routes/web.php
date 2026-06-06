<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\FuelPriceController;
use App\Http\Controllers\LubricantController;
use App\Http\Controllers\CustomerController;

Route::get('/', function () {
    return view('dashboard.index');
});

Route::get('/turnos/create', [TurnoController::class, 'create'])
    ->name('turnos.create');

Route::resource('fuel-prices', FuelPriceController::class);

Route::resource('lubricants', LubricantController::class);

Route::resource('customers', CustomerController::class);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
