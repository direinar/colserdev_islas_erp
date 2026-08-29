<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AnticipoBimestralController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ComprobanteContableCompraController;
use App\Http\Controllers\CompraLubricanteController;
use App\Http\Controllers\FuelPriceController;
use App\Http\Controllers\LubricantController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\InventarioAcpmController;
use App\Http\Controllers\InventarioGasolinaController;
use App\Http\Controllers\InventarioLubricanteController;
use App\Http\Controllers\ProveedorController;
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

    Route::middleware('role:' . User::ROLE_ADMINISTRADOR)->group(function () {
        Route::get('/turnos/pendientes', [TurnoController::class, 'pendientes'])
            ->name('turnos.pendientes');

        Route::post('/turnos/{turno}/revisar', [TurnoController::class, 'revisar'])
            ->name('turnos.revisar');
    });

    Route::middleware('role:' . implode(',', [User::ROLE_JEFE_PATIOS, User::ROLE_ADMINISTRADOR]))->group(function () {
        Route::get('/cartera', [CarteraController::class, 'index'])
            ->name('cartera.index');

        Route::post('/cartera', [CarteraController::class, 'store'])
            ->name('cartera.store');

        Route::get('/compras/create', [CompraController::class, 'create'])
            ->name('compras.create');

        Route::post('/compras', [CompraController::class, 'store'])
            ->name('compras.store');

        Route::get('/anticipo-bimestral/create', [AnticipoBimestralController::class, 'create'])
            ->name('anticipo-bimestral.create');

        Route::post('/anticipo-bimestral', [AnticipoBimestralController::class, 'store'])
            ->name('anticipo-bimestral.store');

        Route::get('/compras-lubricantes/create', [CompraLubricanteController::class, 'create'])
            ->name('compras-lubricantes.create');

        Route::post('/compras-lubricantes', [CompraLubricanteController::class, 'store'])
            ->name('compras-lubricantes.store');

        Route::get('/comprobante-contable-compras/create', [ComprobanteContableCompraController::class, 'create'])
            ->name('comprobante-contable-compras.create');

        Route::post('/comprobante-contable-compras', [ComprobanteContableCompraController::class, 'store'])
            ->name('comprobante-contable-compras.store');
    });

    Route::middleware('role:' . User::ROLE_ADMINISTRADOR)->group(function () {
        Route::get('/inventarios/lubricantes/create', [InventarioLubricanteController::class, 'create'])
            ->name('inventarios-lubricantes.create');

        Route::post('/inventarios/lubricantes', [InventarioLubricanteController::class, 'store'])
            ->name('inventarios-lubricantes.store');

        Route::get('/inventarios/aditivo-motos/create', [InventarioLubricanteController::class, 'createAditivoMotos'])
            ->name('inventarios-aditivo-motos.create');

        Route::post('/inventarios/aditivo-motos', [InventarioLubricanteController::class, 'storeAditivoMotos'])
            ->name('inventarios-aditivo-motos.store');

        Route::get('/inventarios/urea-automotriz/create', [InventarioLubricanteController::class, 'createUreaAutomotriz'])
            ->name('inventarios-urea-automotriz.create');

        Route::post('/inventarios/urea-automotriz', [InventarioLubricanteController::class, 'storeUreaAutomotriz'])
            ->name('inventarios-urea-automotriz.store');

        Route::get('/inventarios/acpm/create', [InventarioAcpmController::class, 'create'])
            ->name('inventarios-acpm.create');

        Route::post('/inventarios/acpm', [InventarioAcpmController::class, 'store'])
            ->name('inventarios-acpm.store');

        Route::get('/inventarios/gasolina/create', [InventarioGasolinaController::class, 'create'])
            ->name('inventarios-gasolina.create');

        Route::post('/inventarios/gasolina', [InventarioGasolinaController::class, 'store'])
            ->name('inventarios-gasolina.store');

        Route::resource('fuel-prices', FuelPriceController::class);

        Route::resource('lubricants', LubricantController::class);

        Route::resource('customers', CustomerController::class);

        Route::resource('proveedores', ProveedorController::class)
            ->parameters(['proveedores' => 'proveedor']);

        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/settings.php';
