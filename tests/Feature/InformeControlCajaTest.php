<?php

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\ComprobanteVentaCuenta;
use App\Models\Customer;
use App\Models\Turno;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Turno 10: ventas por 1.658.000 recibidas con todas las formas de pago y un faltante
 * trasladado de 65.000. Turno 11: un sobrante trasladado de 10.000. Turno 12 fuera del periodo.
 */
function turnosControlCaja(): void
{
    $consorcio = Customer::create(['name' => 'CONSORCIO LIDER', 'document' => '900111222']);
    $arkatec = Customer::create(['name' => 'ARKATEC', 'document' => '900333444']);

    // Compras para valorar el costo de ventas: gasolina a 10.000, ACPM a 8.000 y aditivo a 10.000.
    Compra::create([
        'fecha' => '2026-09-20', 'factura' => '1006', 'vr_total_fra' => 18000000, 'gasolina' => 1000, 'acpm' => 1000,
        'total' => 2000, 'distribucion_gasolina' => 10000000, 'distribucion_acpm' => 8000000,
    ]);
    CompraLubricante::create([
        'fecha' => '2026-09-20', 'nombre' => 'ADITIVO', 'no_fc' => 'L-1', 'unidades' => 10,
        'valor_unitario' => 10000, 'vr_sin_iva' => 100000, 'iva' => 19000, 'total' => 119000,
    ]);

    $turno = Turno::create([
        'fecha' => '2026-09-25', 'numero_turno' => 10,
        'tirillas_galones_corriente' => 60, 'tirillas_valor_corriente' => 984000,
        'tirillas_galones_acpm' => 60, 'tirillas_valor_acpm' => 642000,
        'total_venta_lecturas' => 1600000, 'traslado_faltante' => 65000, 'traslado_sobrante' => 0,
    ]);
    $turno->lubricantes()->create(['cantidad' => 2, 'producto' => 'ADITIVO', 'valor_sin_iva' => 26890, 'iva' => 5110, 'total' => 32000]);
    $turno->consignaciones()->create(['consignacion_no' => 'C-1', 'valor' => 1000000, 'total' => 1000000]);
    $turno->qrPagos()->create(['concepto' => 'QR', 'valor' => 300000, 'total' => 300000]);
    $turno->transferencias()->create(['puntos_redimidos' => 20000, 'total_puntos' => 20000]);
    $turno->gasolinaEds()->create(['valor' => 15000, 'total' => 15000]);
    $turno->descuentos()->create(['descuento_no' => 'D-1', 'valor' => 10000, 'total' => 10000]);
    $turno->cartera()->create(['factura_no' => 'F-1', 'cliente_id' => $consorcio->id, 'valor' => 250000, 'total' => 250000]);
    foreach ([['PEAJE', 10000], ['ASEO', 20000], ['CAFETERIA', 30000], ['PAPELERIA', 5000], ['TAXI', 3000]] as [$concepto, $valor]) {
        $turno->varios()->create(['concepto' => $concepto, 'valor' => $valor, 'total' => 68000]);
    }
    $turno->recaudos()->create(['cliente_id' => $consorcio->id, 'valor' => 50000, 'total' => 70000]);
    $turno->recaudos()->create(['cliente_id' => $arkatec->id, 'valor' => 20000, 'total' => 70000]);

    $conSobrante = Turno::create([
        'fecha' => '2026-09-26', 'numero_turno' => 11,
        'tirillas_galones_corriente' => 10, 'tirillas_valor_corriente' => 100000,
        'total_venta_lecturas' => 100000, 'traslado_faltante' => 0, 'traslado_sobrante' => -10000,
    ]);
    $conSobrante->consignaciones()->create(['consignacion_no' => 'C-2', 'valor' => 110000, 'total' => 110000]);

    Turno::create(['fecha' => '2026-10-01', 'numero_turno' => 12, 'tirillas_valor_corriente' => 999999]);
}

test('el control caja arma una fila por planilla con ventas, formas de pago y total recibido', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    turnosControlCaja();

    $response = $this->actingAs($admin)
        ->get(route('informes.control-caja.index', ['desde' => '2026-09-25', 'hasta' => '2026-09-30']))
        ->assertOk()
        ->assertSee('Informe control caja')
        ->assertSee('COMPROBANTE CONTABLE DE VENTAS');

    [$turno10, $turno11] = $response->viewData('filas');

    expect($response->viewData('filas'))->toHaveCount(2)
        ->and($turno10)->toMatchArray([
            'numero_turno' => 10,
            'gasolina' => 984000.0, 'acpm' => 642000.0, 'canastilla' => 26890.0, 'iva' => 5110.0, 'total_ventas' => 1658000.0,
            'consignaciones' => 1000000.0, 'pagos_electronicos' => 300000.0, 'puntos' => 20000.0, 'gasolina_eds' => 15000.0,
            'descuentos' => 10000.0, 'cartera' => 250000.0, 'faltantes' => 65000.0, 'sobrantes' => 0.0, 'recaudos' => -70000.0,
            // Mismo total recibido que el resumen de la planilla: cuadra con la venta después del traslado.
            'total' => 1658000.0, 'recibido_planilla' => 1658000.0, 'recibido_lectura' => 1632000.0,
        ])
        // Más de cuatro conceptos de varios: la cuarta casilla acumula el resto.
        ->and($turno10['varios'])->toBe([
            ['concepto' => 'PEAJE', 'valor' => 10000.0],
            ['concepto' => 'ASEO', 'valor' => 20000.0],
            ['concepto' => 'CAFETERIA', 'valor' => 30000.0],
            ['concepto' => 'PAPELERIA / TAXI', 'valor' => 8000.0],
        ])
        ->and($turno11)->toMatchArray(['sobrantes' => -10000.0, 'total' => 100000.0])
        ->and($response->viewData('totales'))->toMatchArray(['total_ventas' => 1758000.0, 'total' => 1758000.0, 'consignaciones' => 1110000.0]);
});

test('el comprobante contable de ventas del periodo cuadra débitos y créditos', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    turnosControlCaja();

    $response = $this->actingAs($admin)
        ->get(route('informes.control-caja.index', ['desde' => '2026-09-25', 'hasta' => '2026-09-30']))
        ->assertSeeInOrder(['<th>CONCEPTO</th>', '<th>CLIENTE/TERCERO</th>', '<th>NIT</th>', '<th>DEBITO</th>'], false);
    $comprobante = $response->viewData('comprobante');

    $lineas = array_map(fn (array $linea): array => array_values($linea), $comprobante['lineas']);

    expect($lineas)->toBe([
        // Consignaciones y pagos electrónicos (1.410.000) menos los recaudos y anticipos (70.000), que van aparte.
        ['11050501', 'CONSIGNACIONES, TC, QR Y TRANSF.', '', '', 1340000.0, 0.0],
        ['11050501', 'RECAUDOS Y ANTICIPOS', '', '', 70000.0, 0.0],
        ['413601', 'DESCUENTOS EN VENTAS', '', '', 30000.0, 0.0],
        ['519535', 'GASTOS DE GASOLINA EDS', '', '', 15000.0, 0.0],
        ['11050501', 'VARIOS', '', '', 68000.0, 0.0],
        ['11050501', 'FALT/SOBRANT/DIF. MENORES', '', '', 55000.0, 0.0],
        ['13050501', 'CARTERA CLIENTES', 'ARKATEC', '900333444', 0.0, 20000.0],
        ['13050501', 'CARTERA CLIENTES', 'CONSORCIO LIDER', '900111222', 250000.0, 50000.0],
        ['41350701', 'VENTAS COMBUSTIBLE', '', '', 0.0, 1726000.0],
        // Costo al promedio de las salidas según cortes: 70 gal gasolina x 10.000 + 60 gal ACPM x 8.000.
        ['41350801', 'COSTO DE VENTAS DE COMBUSTIBLE', '', '', 1180000.0, 0.0],
        ['143508', 'INVENTARIOS DE COMBUSTIBLES', '', '', 0.0, 1180000.0],
        ['41351001', 'VENTAS DE CANASTILLA', '', '', 0.0, 26890.0],
        ['41351002', 'COSTO DE VENTAS CANASTILLA', '', '', 20000.0, 0.0],
        ['143509', 'INVENTARIOS DE CANASTILLA', '', '', 0.0, 20000.0],
        ['23670501', 'IVA POR PAGAR', '', '', 0.0, 5110.0],
    ])
        ->and($comprobante['debito'])->toBe(3028000.0)
        ->and($comprobante['credito'])->toBe(3028000.0)
        ->and($comprobante['diferencia'])->toBe(0.0);
});

test('el administrador cambia las cuentas del comprobante y el jefe de patios no accede', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);

    $this->actingAs($jefe)->get(route('informes.control-caja.index'))->assertForbidden();
    $this->actingAs($jefe)->post(route('informes.control-caja.cuentas'), ['cuentas' => []])->assertForbidden();

    $this->actingAs($admin)
        ->post(route('informes.control-caja.cuentas'), ['cuentas' => ['iva_por_pagar' => ['cuenta' => '24080101']]])
        ->assertRedirect();

    expect(ComprobanteVentaCuenta::query()->where('clave', 'iva_por_pagar')->value('cuenta'))->toBe('24080101');
});

test('el informe control caja se exporta a Excel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    turnosControlCaja();

    $response = $this->actingAs($admin)
        ->get(route('informes.control-caja.exportar', ['desde' => '2026-09-25', 'hasta' => '2026-09-30']))
        ->assertOk()
        ->assertDownload('control-caja-2026-09-25-a-2026-09-30.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'caja').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('A1')->getValue())->toBe('INFORME CONTROL CAJA')
        ->and($hoja->getCell('A5')->getValue())->toBe('25/09/2026')
        ->and((float) $hoja->getCell('G5')->getValue())->toBe(1658000.0)
        ->and($hoja->getCell('T5')->getValue())->toBe('PAPELERIA / TAXI')
        ->and($hoja->getCell('B7')->getValue())->toBe('TOTALES')
        ->and((float) $hoja->getCell('Y7')->getValue())->toBe(1758000.0);

    // Comprobante: CLIENTE/TERCERO y NIT después de CONCEPTO.
    $comprobante = collect($hoja->toArray(null, false, false, false))
        ->skipUntil(fn (array $fila): bool => $fila[0] === 'COMPROBANTE CONTABLE DE VENTAS')
        ->values();

    expect(array_slice($comprobante[1], 0, 6))->toBe(['CUENTA', 'CONCEPTO', 'CLIENTE/TERCERO', 'NIT', 'DEBITO', 'CREDITO'])
        ->and($comprobante->first(fn (array $fila): bool => $fila[2] === 'CONSORCIO LIDER'))
        ->toMatchArray([1 => 'CARTERA CLIENTES', 3 => '900111222', 4 => 250000, 5 => 50000]);
});
