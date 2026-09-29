<?php

use App\Http\Controllers\AnticipoBimestralController;
use App\Http\Controllers\BancoController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\CarteraSaldoInicialController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CompraLubricanteController;
use App\Http\Controllers\ComprobanteContableCompraController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FuelPriceController;
use App\Http\Controllers\InventarioAcpmController;
use App\Http\Controllers\InventarioGasolinaController;
use App\Http\Controllers\InventarioLubricanteController;
use App\Http\Controllers\LubricantController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard.index')->name('dashboard');

    Route::middleware('role:'.implode(',', [User::ROLE_ISLERO, User::ROLE_JEFE_PATIOS, User::ROLE_ADMINISTRADOR]))->group(function () {
        Route::get('/turnos/create', [TurnoController::class, 'create'])
            ->name('turnos.create');

        Route::post('/turnos', [TurnoController::class, 'store'])
            ->name('turnos.store');
    });

    Route::middleware('role:'.User::ROLE_ADMINISTRADOR)->group(function () {
        Route::get('/turnos/pendientes', [TurnoController::class, 'pendientes'])
            ->name('turnos.pendientes');

        Route::post('/turnos/{turno}/revisar', [TurnoController::class, 'revisar'])
            ->name('turnos.revisar');

        Route::post('/turnos/{turno}/reabrir', [TurnoController::class, 'reabrir'])
            ->name('turnos.reabrir');
    });

    Route::middleware('role:'.implode(',', [User::ROLE_JEFE_PATIOS, User::ROLE_ADMINISTRADOR]))->group(function () {
        Route::get('/cartera', [CarteraController::class, 'index'])
            ->name('cartera.index');

        Route::post('/cartera', [CarteraController::class, 'store'])
            ->name('cartera.store');

        Route::get('/cartera/exportar', [CarteraController::class, 'exportar'])
            ->name('cartera.exportar');

        Route::get('/compras/create', [CompraController::class, 'create'])
            ->name('compras.create');

        Route::post('/compras', [CompraController::class, 'store'])
            ->name('compras.store');

        Route::get('/compras/{compra}/edit', [CompraController::class, 'edit'])
            ->name('compras.edit');

        Route::put('/compras/{compra}', [CompraController::class, 'update'])
            ->name('compras.update');

        Route::delete('/compras/{compra}', [CompraController::class, 'destroy'])
            ->name('compras.destroy');

        Route::get('/anticipo-bimestral/create', [AnticipoBimestralController::class, 'create'])
            ->name('anticipo-bimestral.create');

        Route::post('/anticipo-bimestral', [AnticipoBimestralController::class, 'store'])
            ->name('anticipo-bimestral.store');

        Route::get('/compras-lubricantes/create', [CompraLubricanteController::class, 'create'])
            ->name('compras-lubricantes.create');

        Route::post('/compras-lubricantes', [CompraLubricanteController::class, 'store'])
            ->name('compras-lubricantes.store');

        Route::delete('/compras-lubricantes/{compraLubricante}', [CompraLubricanteController::class, 'destroy'])
            ->name('compras-lubricantes.destroy');

        Route::get('/comprobante-contable-compras/create', [ComprobanteContableCompraController::class, 'create'])
            ->name('comprobante-contable-compras.create');

        Route::post('/comprobante-contable-compras', [ComprobanteContableCompraController::class, 'store'])
            ->name('comprobante-contable-compras.store');
    });

    Route::middleware('role:'.User::ROLE_ADMINISTRADOR)->group(function () {
        Route::get('/inventarios/lubricantes/create', [InventarioLubricanteController::class, 'create'])
            ->name('inventarios-lubricantes.create');

        Route::get('/inventarios/acpm/create', [InventarioAcpmController::class, 'create'])
            ->name('inventarios-acpm.create');

        Route::get('/inventarios/gasolina/create', [InventarioGasolinaController::class, 'create'])
            ->name('inventarios-gasolina.create');

        Route::resource('fuel-prices', FuelPriceController::class);

        Route::resource('lubricants', LubricantController::class);

        Route::resource('customers', CustomerController::class);

        Route::resource('bancos', BancoController::class);

        Route::get('/cartera/saldos-iniciales/plantilla', [CarteraSaldoInicialController::class, 'plantilla'])
            ->name('cartera-saldos-iniciales.plantilla');

        Route::post('/cartera/saldos-iniciales/importar', [CarteraSaldoInicialController::class, 'importar'])
            ->name('cartera-saldos-iniciales.importar');

        Route::resource('cartera/saldos-iniciales', CarteraSaldoInicialController::class)
            ->only(['index', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['saldos-iniciales' => 'saldoInicial'])
            ->names('cartera-saldos-iniciales');

        Route::resource('proveedores', ProveedorController::class)
            ->parameters(['proveedores' => 'proveedor']);

        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/settings.php';
