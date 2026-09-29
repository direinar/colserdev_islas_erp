<?php

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Models\User;
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
        ->assertSeeInOrder(['No.', 'FECHA VENTA', 'FECHA COMPRA', 'No. FC'])
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
