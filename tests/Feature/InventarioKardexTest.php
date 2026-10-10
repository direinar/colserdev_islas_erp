<?php

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Models\User;
use App\Services\KardexInventarioService;
use Illuminate\Support\Facades\Route;

function compraCombustible(array $atributos = []): Compra
{
    return Compra::create($atributos + [
        'fecha' => '2026-09-25',
        'factura' => '1006',
        'vr_total_fra' => 40000000,
        'gasolina' => 3000,
        'acpm' => 1000,
        'total' => 4000,
        'distribucion_gasolina' => 30000000,
        'distribucion_acpm' => 10000000,
    ]);
}

test('el inventario de gasolina valora las salidas al costo promedio y trae la venta y el precio de la planilla', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $compra = compraCombustible();

    Turno::create([
        'fecha' => '2026-09-25',
        'numero_turno' => 4,
        'precio_corriente' => 16400,
        'lecturas_galones_corriente' => 60,
        'tirillas_valor_corriente' => 984000,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('inventarios-gasolina.create'))
        ->assertOk();

    [$filaCompra, $filaVenta] = $response->viewData('kardex')['rows'];

    expect($filaCompra)
        ->toMatchArray(['tipo' => 'compra', 'fecha_compra' => '2026-09-25', 'entradas' => 3000.0, 'valor_entradas' => 30000000.0])
        ->and($filaVenta)
        ->toMatchArray([
            'tipo' => 'venta',
            'numero_turno' => 4,
            'fecha_venta' => '2026-09-25',
            'salidas' => 60.0,
            'valor_salidas' => 600000.0,
            'saldo_unidades' => 2940.0,
            'valor_saldo' => 29400000.0,
            'promedio' => 10000.0,
            'valor_venta' => 984000.0,
            'precio' => 16400.0,
        ]);

    $response
        ->assertSeeInOrder(['PLANILLA', 'FECHA', 'COMPRA', 'No.', 'No. FC'])
        ->assertDontSee('FECHA VENTA')
        ->assertDontSee('FECHA COMPRA')
        ->assertSee('>3.000<', false)
        ->assertSee('>60,00<', false)
        ->assertSee('>2.940,00<', false)
        ->assertSee('>30.000.000<', false)
        ->assertSee('>984.000<', false)
        ->assertSee('>16.400<', false)
        ->assertSee(route('compras.create', ['desde' => '2026-09-25', 'hasta' => '2026-09-25']).'#compra-'.$compra->id)
        ->assertDontSee('type="date"', false)
        ->assertDontSee('Agregar fila')
        ->assertDontSee('remove-row');
});

test('el inventario no acepta registros propios: no hay rutas para guardar', function (string $ruta) {
    expect(Route::has($ruta))->toBeFalse();
})->with([
    'inventarios-gasolina.store',
    'inventarios-acpm.store',
    'inventarios-lubricantes.store',
]);

test('el inventario de ACPM toma los galones, la venta y el precio de ACPM y omite compras sin ACPM', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    compraCombustible();
    compraCombustible(['factura' => 'SOLO-GAS', 'acpm' => 0, 'distribucion_acpm' => 0]);

    Turno::create([
        'fecha' => '2026-09-25',
        'numero_turno' => 4,
        'precio_acpm' => 10700,
        'lecturas_galones_corriente' => 60,
        'lecturas_galones_acpm' => 60,
        'tirillas_valor_acpm' => 642000,
    ]);

    $rows = $this->actingAs($admin)
        ->get(route('inventarios-acpm.create'))
        ->assertOk()
        ->viewData('kardex')['rows'];

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toMatchArray(['factura' => '1006', 'entradas' => 1000.0, 'valor_entradas' => 10000000.0])
        ->and($rows[1])->toMatchArray(['salidas' => 60.0, 'valor_salidas' => 600000.0, 'valor_venta' => 642000.0, 'precio' => 10700.0]);
});

test('el inventario de canastilla suma por planilla las ventas del producto y toma las compras de ese producto', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Lubricant::create(['reference' => '17-MOBIL SUPER 20W50', 'sale_price' => 42000, 'iva' => 0, 'total' => 42000, 'cost_price' => 32300, 'active' => true]);
    Lubricant::create(['reference' => '3-UREA', 'sale_price' => 1700, 'iva' => 0, 'total' => 1700, 'cost_price' => 1500, 'active' => true]);

    CompraLubricante::create(['fecha' => '2026-09-20', 'nombre' => '17-MOBIL SUPER 20W50', 'no_fc' => 'L-1', 'unidades' => 10, 'valor_unitario' => 30000, 'vr_sin_iva' => 300000, 'iva' => 0, 'total' => 300000]);
    CompraLubricante::create(['fecha' => '2026-09-20', 'nombre' => '3-UREA', 'no_fc' => 'L-2', 'unidades' => 50, 'valor_unitario' => 1500, 'vr_sin_iva' => 75000, 'iva' => 0, 'total' => 75000]);

    $turno = Turno::create(['fecha' => '2026-09-25', 'numero_turno' => 6]);
    $turno->lubricantes()->create(['cantidad' => 1, 'producto' => '17-MOBIL SUPER 20W50', 'valor_sin_iva' => 42000, 'iva' => 0, 'total' => 42000]);
    $turno->lubricantes()->create(['cantidad' => 2, 'producto' => '17-MOBIL SUPER 20W50', 'valor_sin_iva' => 84000, 'iva' => 0, 'total' => 84000]);
    $turno->lubricantes()->create(['cantidad' => 1, 'producto' => '3-UREA', 'valor_sin_iva' => 1700, 'iva' => 0, 'total' => 1700]);

    $rows = $this->actingAs($admin)
        ->get(route('inventarios-lubricantes.create', ['producto' => '17-MOBIL SUPER 20W50']))
        ->assertOk()
        ->assertSee(route('compras-lubricantes.create', ['desde' => '2026-09-20', 'hasta' => '2026-09-20']))
        ->viewData('kardex')['rows'];

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toMatchArray(['factura' => 'L-1', 'entradas' => 10.0, 'valor_entradas' => 300000.0])
        ->and($rows[1])->toMatchArray([
            'numero_turno' => 6,
            'salidas' => 3.0,
            'valor_salidas' => 90000.0,
            'saldo_unidades' => 7.0,
            'valor_venta' => 126000.0,
            'precio' => 42000.0,
        ]);
});

test('el inventario de canastilla registra el valor de la venta sin IVA', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Lubricant::create(['reference' => '1 - ADITIVO GASOLINA AUTOS', 'sale_price' => 13445, 'iva' => 2555, 'total' => 16000, 'cost_price' => 10000, 'active' => true]);
    CompraLubricante::create(['fecha' => '2026-09-20', 'nombre' => '1 - ADITIVO GASOLINA AUTOS', 'no_fc' => 'A-1', 'unidades' => 10, 'valor_unitario' => 10000, 'vr_sin_iva' => 100000, 'iva' => 19000, 'total' => 119000]);

    $turno = Turno::create(['fecha' => '2026-09-25', 'numero_turno' => 7]);
    $turno->lubricantes()->create(['cantidad' => 2, 'producto' => '1 - ADITIVO GASOLINA AUTOS', 'valor_sin_iva' => 26890, 'iva' => 5110, 'total' => 32000]);

    $rows = $this->actingAs($admin)
        ->get(route('inventarios-lubricantes.create'))
        ->assertOk()
        ->viewData('kardex')['rows'];

    expect($rows[1])->toMatchArray(['salidas' => 2.0, 'valor_venta' => 26890.0, 'precio' => 13445.0]);
});

test('el control de canastilla consolida todas las referencias y sus totales son la suma de los de cada referencia', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Lubricant::create(['reference' => '17-MOBIL SUPER 20W50', 'sale_price' => 42000, 'iva' => 0, 'total' => 42000, 'cost_price' => 32300, 'active' => true]);
    Lubricant::create(['reference' => '3-UREA', 'sale_price' => 1700, 'iva' => 0, 'total' => 1700, 'cost_price' => 1500, 'active' => true]);

    CompraLubricante::create(['fecha' => '2026-09-20', 'nombre' => '17-MOBIL SUPER 20W50', 'no_fc' => 'L-1', 'unidades' => 10, 'valor_unitario' => 30000, 'vr_sin_iva' => 300000, 'iva' => 0, 'total' => 300000]);
    CompraLubricante::create(['fecha' => '2026-09-21', 'nombre' => '3-UREA', 'no_fc' => 'L-2', 'unidades' => 50, 'valor_unitario' => 1500, 'vr_sin_iva' => 75000, 'iva' => 0, 'total' => 75000]);
    CompraLubricante::create(['fecha' => '2026-09-22', 'nombre' => '17-MOBIL SUPER 20W50', 'no_fc' => 'L-3', 'unidades' => 10, 'valor_unitario' => 36000, 'vr_sin_iva' => 360000, 'iva' => 0, 'total' => 360000]);
    // Referencia que ya no está en el catálogo: igual debe entrar al control.
    CompraLubricante::create(['fecha' => '2026-09-23', 'nombre' => 'REFERENCIA RETIRADA', 'no_fc' => 'L-4', 'unidades' => 4, 'valor_unitario' => 5000, 'vr_sin_iva' => 20000, 'iva' => 0, 'total' => 20000]);

    $turno = Turno::create(['fecha' => '2026-09-25', 'numero_turno' => 6]);
    $turno->lubricantes()->create(['cantidad' => 3, 'producto' => '17-MOBIL SUPER 20W50', 'valor_sin_iva' => 126000, 'iva' => 0, 'total' => 126000]);
    $turno->lubricantes()->create(['cantidad' => 5, 'producto' => '3-UREA', 'valor_sin_iva' => 8500, 'iva' => 0, 'total' => 8500]);

    $response = $this->actingAs($admin)
        ->get(route('inventarios-lubricantes.control'))
        ->assertOk()
        ->assertSee('INVENTARIO CANASTILLA – CONTROL')
        ->assertSee('REFERENCIA')
        ->assertDontSee('name="producto"', false);

    $kardex = $response->viewData('kardex');

    expect(array_column($kardex['rows'], 'referencia'))->toBe([
        '17-MOBIL SUPER 20W50',
        '3-UREA',
        '17-MOBIL SUPER 20W50',
        'REFERENCIA RETIRADA',
        '17-MOBIL SUPER 20W50',
        '3-UREA',
    ])
        ->and($kardex['rows'][4])->toMatchArray(['tipo' => 'venta', 'salidas' => 3.0, 'valor_salidas' => 99000.0, 'promedio' => 33000.0])
        ->and($kardex['rows'][5])->toMatchArray(['tipo' => 'venta', 'salidas' => 5.0, 'valor_salidas' => 7500.0, 'saldo_unidades' => 66.0]);

    $service = app(KardexInventarioService::class);
    $sumaPorReferencia = collect(['17-MOBIL SUPER 20W50', '3-UREA', 'REFERENCIA RETIRADA'])
        ->map(fn (string $referencia): array => $service->canastilla($referencia)['totales'])
        ->reduce(fn (array $suma, array $totales): array => array_map(
            fn (float $a, float $b): float => $a + $b,
            $suma,
            $totales,
        ), array_fill_keys(array_keys($kardex['totales']), 0.0));

    expect($kardex['totales'])->toEqualWithDelta(array_combine(array_keys($kardex['totales']), $sumaPorReferencia), 0.001)
        ->and($kardex['totales'])->toMatchArray([
            'entradas' => 74.0,
            'salidas' => 8.0,
            'saldo_unidades' => 66.0,
            'valor_entradas' => 755000.0,
            'valor_salidas' => 106500.0,
            'valor_saldo' => 648500.0,
            'valor_venta' => 134500.0,
        ]);
});
