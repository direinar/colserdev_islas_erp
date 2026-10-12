<?php

use App\Models\CarteraMovimiento;
use App\Models\CompraLubricante;
use App\Models\Customer;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

function movimientoCartera(Customer $cliente, string $fecha, float $cargo, float $abono, string $origen = CarteraMovimiento::ORIGEN_CREDITO_DIRECTO): void
{
    CarteraMovimiento::create([
        'customer_id' => $cliente->id, 'origen' => $origen, 'fecha' => $fecha,
        'vr_neto_cargo' => $cargo, 'abonos' => $abono,
    ]);
}

/**
 * Arkatec debe 300.000 al 30/09 (el cargo de octubre no cuenta), BTS tiene un anticipo
 * de 50.000, Consorcio no tiene movimientos y Distribuciones solo su saldo inicial.
 */
function carteraConSaldos(): void
{
    $arkatec = Customer::create(['name' => 'ARKATEC', 'document' => '900111']);
    $bts = Customer::create(['name' => 'BTS', 'document' => '900222']);
    Customer::create(['name' => 'CONSORCIO', 'document' => '900333']);
    $distribuciones = Customer::create(['name' => 'DISTRIBUCIONES', 'document' => '900444']);

    movimientoCartera($arkatec, '2026-09-10', 500000, 0);
    movimientoCartera($arkatec, '2026-09-20', 0, 200000, CarteraMovimiento::ORIGEN_RECAUDO_ISLAS);
    movimientoCartera($arkatec, '2026-10-05', 100000, 0);
    movimientoCartera($bts, '2026-09-15', 0, 50000, CarteraMovimiento::ORIGEN_RECAUDO_ADMIN);
    movimientoCartera($distribuciones, '2026-09-01', 1000000, 0, CarteraMovimiento::ORIGEN_SALDO_INICIAL);
}

test('el informe de saldos de cartera da el saldo de cada cliente a la fecha de corte', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    carteraConSaldos();

    $response = $this->actingAs($admin)
        ->get(route('informes.saldos.index', ['corte' => '2026-09-30']))
        ->assertOk()
        ->assertSee('INFORME DE SALDOS DE CARTERA')
        ->assertSee('Estado de cuenta');

    $datos = $response->viewData('datos');

    // Por defecto solo los clientes con saldo; el anticipo queda negativo.
    expect(array_map(fn (array $c): array => [$c['cliente'], $c['saldo']], $datos['clientes']))->toBe([
        ['ARKATEC', 300000.0],
        ['BTS', -50000.0],
        ['DISTRIBUCIONES', 1000000.0],
    ])
        ->and($datos)->toMatchArray(['por_cobrar' => 1300000.0, 'anticipos' => -50000.0, 'neto' => 1250000.0, 'con_saldo' => 3]);

    $todos = $this->actingAs($admin)
        ->get(route('informes.saldos.index', ['corte' => '2026-09-30', 'todos' => 1]))
        ->viewData('datos');

    expect(array_column($todos['clientes'], 'cliente'))->toBe(['ARKATEC', 'BTS', 'CONSORCIO', 'DISTRIBUCIONES']);
});

test('el informe de saldos de canastilla da las existencias de cada referencia a la fecha de corte', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Lubricant::create(['reference' => 'MOBIL 20W50', 'sale_price' => 42000, 'iva' => 0, 'total' => 42000, 'cost_price' => 30000, 'active' => true]);
    Lubricant::create(['reference' => 'UREA', 'sale_price' => 1700, 'iva' => 0, 'total' => 1700, 'cost_price' => 1500, 'active' => true]);
    Lubricant::create(['reference' => 'ADITIVO VIEJO', 'sale_price' => 5000, 'iva' => 950, 'total' => 5950, 'cost_price' => 3000, 'active' => false]);

    CompraLubricante::create(['fecha' => '2026-09-20', 'nombre' => 'MOBIL 20W50', 'no_fc' => 'L-1', 'unidades' => 10, 'valor_unitario' => 30000, 'vr_sin_iva' => 300000, 'iva' => 0, 'total' => 300000]);
    CompraLubricante::create(['fecha' => '2026-10-02', 'nombre' => 'MOBIL 20W50', 'no_fc' => 'L-3', 'unidades' => 10, 'valor_unitario' => 36000, 'vr_sin_iva' => 360000, 'iva' => 0, 'total' => 360000]);
    CompraLubricante::create(['fecha' => '2026-09-21', 'nombre' => 'UREA', 'no_fc' => 'L-2', 'unidades' => 50, 'valor_unitario' => 1500, 'vr_sin_iva' => 75000, 'iva' => 0, 'total' => 75000]);

    $turno = Turno::create(['fecha' => '2026-09-25', 'numero_turno' => 40]);
    $turno->lubricantes()->create(['cantidad' => 3, 'producto' => 'MOBIL 20W50', 'valor_sin_iva' => 126000, 'iva' => 0, 'total' => 126000]);
    $turno->lubricantes()->create(['cantidad' => 5, 'producto' => 'UREA', 'valor_sin_iva' => 8500, 'iva' => 0, 'total' => 8500]);

    $datos = $this->actingAs($admin)
        ->get(route('informes.saldos.index', ['seccion' => 'canastilla', 'corte' => '2026-09-30']))
        ->assertOk()
        ->assertSee('INFORME DE SALDOS DE CANASTILLA')
        ->assertSee('inactiva')
        ->viewData('datos');

    expect($datos['referencias'])->toBe([
        ['referencia' => 'ADITIVO VIEJO', 'activa' => false, 'unidades' => 0.0, 'promedio' => 0.0, 'valor' => 0.0],
        ['referencia' => 'MOBIL 20W50', 'activa' => true, 'unidades' => 7.0, 'promedio' => 30000.0, 'valor' => 210000.0],
        ['referencia' => 'UREA', 'activa' => true, 'unidades' => 45.0, 'promedio' => 1500.0, 'valor' => 67500.0],
    ])
        ->and($datos['unidades'])->toBe(52.0)
        ->and($datos['valor'])->toBe(277500.0);
});

test('el informe de precios de canastilla lista solo las referencias activas', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Lubricant::create(['reference' => 'UREA', 'sale_price' => 1700, 'iva' => 0, 'total' => 1700, 'cost_price' => 1500, 'active' => true]);
    Lubricant::create(['reference' => '1 - ADITIVO GASOLINA AUTOS', 'sale_price' => 13445, 'iva' => 2555, 'total' => 16000, 'cost_price' => 10000, 'active' => true]);
    Lubricant::create(['reference' => 'ADITIVO VIEJO', 'sale_price' => 5000, 'iva' => 950, 'total' => 5950, 'cost_price' => 3000, 'active' => false]);

    $datos = $this->actingAs($admin)
        ->get(route('informes.saldos.index', ['seccion' => 'precios']))
        ->assertOk()
        ->assertSee('INFORME DE PRECIOS DE VENTA DE CANASTILLA')
        ->assertDontSee('ADITIVO VIEJO')
        ->viewData('datos');

    expect($datos)->toBe([
        ['referencia' => '1 - ADITIVO GASOLINA AUTOS', 'precio' => 13445.0, 'iva' => 2555.0, 'total' => 16000.0],
        ['referencia' => 'UREA', 'precio' => 1700.0, 'iva' => 0.0, 'total' => 1700.0],
    ]);
});

test('el islero no tiene acceso a los informes de saldos', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $this->actingAs($islero)->get(route('informes.saldos.index'))->assertForbidden();
});

test('los saldos de cartera se exportan a Excel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    carteraConSaldos();

    $response = $this->actingAs($admin)
        ->get(route('informes.saldos.exportar', ['seccion' => 'cartera', 'corte' => '2026-09-30']))
        ->assertOk()
        ->assertDownload('saldos-cartera-2026-09-30.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'saldos').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('A1')->getValue())->toBe('INFORME DE SALDOS DE CARTERA')
        ->and($hoja->getCell('A5')->getValue())->toBe('ARKATEC')
        ->and($hoja->getCell('B5')->getValue())->toBe('900111')
        ->and((float) $hoja->getCell('C6')->getValue())->toBe(-50000.0)
        ->and($hoja->getCell('A8')->getValue())->toBe('SALDO NETO DE CARTERA')
        ->and((float) $hoja->getCell('C8')->getValue())->toBe(1250000.0);
});
