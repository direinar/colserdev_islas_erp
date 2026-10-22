<?php

use App\Http\Controllers\AnticipoBimestralController;
use App\Http\Controllers\BancoController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\CarteraSaldoInicialController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CompraLubricanteController;
use App\Http\Controllers\ComprobanteContableCompraController;
use App\Http\Controllers\ControlTanqueController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FuelPriceController;
use App\Http\Controllers\InformeAnticipoBimestralController;
use App\Http\Controllers\InformeCierreController;
use App\Http\Controllers\InformeComprobanteComprasController;
use App\Http\Controllers\InformeComprobanteRecaudosController;
use App\Http\Controllers\InformeComprobanteVentasController;
use App\Http\Controllers\InformeControlCajaController;
use App\Http\Controllers\InformeMargenMayoristaController;
use App\Http\Controllers\InformeRecaudosCarteraController;
use App\Http\Controllers\InformeSaldosController;
use App\Http\Controllers\InventarioAcpmController;
use App\Http\Controllers\InventarioGasolinaController;
use App\Http\Controllers\InventarioLubricanteController;
use App\Http\Controllers\InventarioSaldoInicialController;
use App\Http\Controllers\LubricantController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    $administracion = 'role:'.implode(',', User::rolesAdministrativos());

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:'.implode(',', User::roles()))->group(function () {
        Route::get('/turnos/create', [TurnoController::class, 'create'])
            ->name('turnos.create');

        Route::post('/turnos', [TurnoController::class, 'store'])
            ->name('turnos.store');

        Route::post('/turnos/abrir', [TurnoController::class, 'abrir'])
            ->name('turnos.abrir');

        Route::post('/turnos/{turno}/cerrar', [TurnoController::class, 'cerrar'])
            ->name('turnos.cerrar');
    });

    Route::middleware($administracion)->group(function () {
        Route::get('/turnos/pendientes', [TurnoController::class, 'pendientes'])
            ->name('turnos.pendientes');

        Route::post('/turnos/{turno}/revisar', [TurnoController::class, 'revisar'])
            ->name('turnos.revisar');

        Route::post('/turnos/{turno}/reabrir', [TurnoController::class, 'reabrir'])
            ->name('turnos.reabrir');

        Route::post('/turnos/{turno}/cambio-contador', [TurnoController::class, 'cambioContador'])
            ->name('turnos.cambio-contador');
    });

    // El jefe de patios, además de la planilla de turnos, solo accede a Cartera - Estado de cuenta y a Control de tanques.
    Route::middleware('role:'.implode(',', [User::ROLE_JEFE_PATIOS, ...User::rolesAdministrativos()]))->group(function () {
        Route::get('/cartera', [CarteraController::class, 'index'])
            ->name('cartera.index');

        Route::post('/cartera', [CarteraController::class, 'store'])
            ->name('cartera.store');

        Route::get('/cartera/exportar', [CarteraController::class, 'exportar'])
            ->name('cartera.exportar');

        Route::get('/control-tanques', [ControlTanqueController::class, 'index'])
            ->name('control-tanques.index');

        Route::post('/control-tanques/aforos', [ControlTanqueController::class, 'guardarAforos'])
            ->name('control-tanques.aforos');

        Route::get('/control-tanques/exportar', [ControlTanqueController::class, 'exportar'])
            ->name('control-tanques.exportar');

        Route::get('/control-tanques/pdf', [ControlTanqueController::class, 'pdf'])
            ->name('control-tanques.pdf');
    });

    Route::middleware($administracion)->group(function () {
        Route::get('/compras/create', [CompraController::class, 'create'])
            ->name('compras.create');

        Route::get('/compras/exportar', [CompraController::class, 'exportar'])
            ->name('compras.exportar');

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

        Route::get('/informes/anticipo-bimestral', [InformeAnticipoBimestralController::class, 'index'])
            ->name('informes.anticipo-bimestral.index');

        Route::get('/informes/anticipo-bimestral/exportar', [InformeAnticipoBimestralController::class, 'exportar'])
            ->name('informes.anticipo-bimestral.exportar');

        Route::get('/informes/recaudos-cartera', [InformeRecaudosCarteraController::class, 'index'])
            ->name('informes.recaudos-cartera.index');

        Route::get('/informes/recaudos-cartera/exportar', [InformeRecaudosCarteraController::class, 'exportar'])
            ->name('informes.recaudos-cartera.exportar');

        Route::get('/informes/saldos', [InformeSaldosController::class, 'index'])
            ->name('informes.saldos.index');

        Route::get('/informes/saldos/exportar', [InformeSaldosController::class, 'exportar'])
            ->name('informes.saldos.exportar');

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

        Route::get('/inventarios/lubricantes/create', [InventarioLubricanteController::class, 'create'])
            ->name('inventarios-lubricantes.create');

        Route::get('/inventarios/lubricantes/control', [InventarioLubricanteController::class, 'control'])
            ->name('inventarios-lubricantes.control');

        Route::get('/inventarios/lubricantes/exportar', [InventarioLubricanteController::class, 'exportar'])
            ->name('inventarios-lubricantes.exportar');

        Route::get('/inventarios/lubricantes/control/exportar', [InventarioLubricanteController::class, 'exportarControl'])
            ->name('inventarios-lubricantes.control.exportar');

        Route::get('/inventarios/saldos-iniciales', [InventarioSaldoInicialController::class, 'index'])
            ->name('inventarios-saldos-iniciales.index');

        Route::post('/inventarios/saldos-iniciales', [InventarioSaldoInicialController::class, 'store'])
            ->name('inventarios-saldos-iniciales.store');

        Route::post('/control-tanques/saldo-inicial', [ControlTanqueController::class, 'guardarSaldoInicial'])
            ->name('control-tanques.saldo-inicial');

        Route::get('/informes/comprobante-contable-compras', [InformeComprobanteComprasController::class, 'index'])
            ->name('informes.comprobante-contable-compras.index');

        Route::get('/informes/comprobante-contable-compras/exportar', [InformeComprobanteComprasController::class, 'exportar'])
            ->name('informes.comprobante-contable-compras.exportar');

        Route::post('/informes/comprobante-contable-compras/cuentas', [InformeComprobanteComprasController::class, 'guardarCuentas'])
            ->name('informes.comprobante-contable-compras.cuentas');

        Route::post('/informes/anticipo-bimestral/ajustes', [InformeAnticipoBimestralController::class, 'guardarAjustes'])
            ->name('informes.anticipo-bimestral.ajustes');

        Route::post('/informes/anticipo-bimestral/margenes', [InformeAnticipoBimestralController::class, 'guardarMargen'])
            ->name('informes.anticipo-bimestral.margenes');

        Route::get('/informes/control-caja', [InformeControlCajaController::class, 'index'])
            ->name('informes.control-caja.index');

        Route::get('/informes/control-caja/exportar', [InformeControlCajaController::class, 'exportar'])
            ->name('informes.control-caja.exportar');

        Route::get('/informes/comprobante-ventas', [InformeComprobanteVentasController::class, 'index'])
            ->name('informes.comprobante-ventas.index');

        Route::get('/informes/comprobante-ventas/exportar', [InformeComprobanteVentasController::class, 'exportar'])
            ->name('informes.comprobante-ventas.exportar');

        Route::post('/informes/comprobante-ventas/cuentas', [InformeComprobanteVentasController::class, 'guardarCuentas'])
            ->name('informes.comprobante-ventas.cuentas');

        Route::get('/informes/margen-mayorista', [InformeMargenMayoristaController::class, 'index'])
            ->name('informes.margen-mayorista.index');

        Route::get('/informes/margen-mayorista/exportar', [InformeMargenMayoristaController::class, 'exportar'])
            ->name('informes.margen-mayorista.exportar');

        Route::post('/informes/margen-mayorista/parametros', [InformeMargenMayoristaController::class, 'guardarParametros'])
            ->name('informes.margen-mayorista.parametros');

        Route::get('/informes/comprobante-recaudos', [InformeComprobanteRecaudosController::class, 'index'])
            ->name('informes.comprobante-recaudos.index');

        Route::get('/informes/comprobante-recaudos/exportar', [InformeComprobanteRecaudosController::class, 'exportar'])
            ->name('informes.comprobante-recaudos.exportar');

        Route::post('/informes/comprobante-recaudos/cuentas', [InformeComprobanteRecaudosController::class, 'guardarCuentas'])
            ->name('informes.comprobante-recaudos.cuentas');

        Route::get('/informes/cierre', [InformeCierreController::class, 'index'])
            ->name('informes.cierre.index');

        Route::get('/informes/cierre/exportar', [InformeCierreController::class, 'exportar'])
            ->name('informes.cierre.exportar');

        Route::post('/informes/cierre/contrato', [InformeCierreController::class, 'guardarContrato'])
            ->name('informes.cierre.contrato');

        Route::get('/inventarios/acpm/create', [InventarioAcpmController::class, 'create'])
            ->name('inventarios-acpm.create');

        Route::get('/inventarios/acpm/exportar', [InventarioAcpmController::class, 'exportar'])
            ->name('inventarios-acpm.exportar');

        Route::get('/inventarios/gasolina/create', [InventarioGasolinaController::class, 'create'])
            ->name('inventarios-gasolina.create');

        Route::get('/inventarios/gasolina/exportar', [InventarioGasolinaController::class, 'exportar'])
            ->name('inventarios-gasolina.exportar');

        Route::resource('fuel-prices', FuelPriceController::class);

        Route::get('/lubricants/exportar', [LubricantController::class, 'exportar'])
            ->name('lubricants.exportar');

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

        Route::get('/proveedores/exportar', [ProveedorController::class, 'exportar'])
            ->name('proveedores.exportar');

        Route::resource('proveedores', ProveedorController::class)
            ->parameters(['proveedores' => 'proveedor']);

        Route::resource('users', UserController::class);
    });
});

require __DIR__.'/settings.php';
