<?php

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\InventarioSaldoInicial;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Models\User;
use App\Services\KardexInventarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

test('el inventario de gasolina valora al costo promedio las salidas según cortes de IAPROPIADA y trae la venta y el precio de la planilla', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $compra = compraCombustible();

    Turno::create([
        'fecha' => '2026-09-25',
        'numero_turno' => 4,
        'precio_corriente' => 16400,
        'tirillas_galones_corriente' => 60,
        'lecturas_galones_corriente' => 999,
        'tirillas_valor_corriente' => 984000,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('inventarios-gasolina.create', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
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
        // Solo el filtro de fechas: el kardex no tiene campos para digitar movimientos.
        ->assertDontSee('name="fecha', false)
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
        'tirillas_galones_corriente' => 60,
        'tirillas_galones_acpm' => 60,
        // Las salidas salen de los cortes de IAPROPIADA, no de las lecturas de surtidores.
        'lecturas_galones_acpm' => 999,
        'tirillas_valor_acpm' => 642000,
    ]);

    $rows = $this->actingAs($admin)
        ->get(route('inventarios-acpm.create', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
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
        ->get(route('inventarios-lubricantes.create', ['producto' => '17-MOBIL SUPER 20W50', 'desde' => '2026-09-01', 'hasta' => '2026-09-30']))
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
        ->get(route('inventarios-lubricantes.create', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
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
        ->get(route('inventarios-lubricantes.control', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
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

test('el kardex de canastilla consulta lo mismo sin importar cuántas referencias haya y se reutiliza en la petición', function () {
    foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $i => $referencia) {
        CompraLubricante::create(['fecha' => '2026-09-20', 'nombre' => $referencia, 'no_fc' => 'L-'.$i, 'unidades' => 10, 'valor_unitario' => 1000, 'vr_sin_iva' => 10000, 'iva' => 0, 'total' => 10000]);
        $turno = Turno::create(['fecha' => '2026-09-25', 'numero_turno' => $i + 1]);
        $turno->lubricantes()->create(['cantidad' => 1, 'producto' => $referencia, 'valor_sin_iva' => 1500, 'iva' => 0, 'total' => 1500]);
    }

    $kardex = app(KardexInventarioService::class);
    DB::enableQueryLog();

    $control = $kardex->canastillaControl();
    foreach ($kardex->referenciasCanastilla() as $referencia) {
        $kardex->canastilla($referencia);
    }

    // Saldos iniciales + compras + líneas de canastilla + sus turnos, sin importar el número de referencias.
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(4)
        ->and($control['totales']['salidas'])->toBe(6.0)
        ->and($kardex->referenciasCanastilla())->toBe(['A', 'B', 'C', 'D', 'E', 'F'])
        // Los informes de la misma petición comparten la instancia (y lo ya calculado).
        ->and(app(KardexInventarioService::class))->toBe($kardex);
});

test('el kardex de gasolina arranca con el saldo inicial y no cuenta los movimientos anteriores al corte', function () {
    InventarioSaldoInicial::create(['inventario' => InventarioSaldoInicial::GASOLINA, 'fecha_corte' => '2026-09-20', 'unidades' => 1000, 'costo_unitario' => 9000]);
    // Anterior al corte: no cuenta.
    compraCombustible(['fecha' => '2026-09-10', 'factura' => '0999']);
    compraCombustible(['fecha' => '2026-09-20', 'factura' => '1006']);
    Turno::create(['fecha' => '2026-09-21', 'numero_turno' => 1, 'precio_corriente' => 16400, 'tirillas_galones_corriente' => 60, 'tirillas_valor_corriente' => 984000]);

    $kardex = app(KardexInventarioService::class)->gasolina();
    $filas = array_map(fn (array $fila): array => [$fila['tipo'], $fila['factura'], $fila['entradas'], $fila['salidas'], round($fila['valor_entradas']), round($fila['valor_salidas']), round($fila['promedio'])], $kardex['rows']);

    // Promedio tras la compra: (9.000.000 + 30.000.000) / 4.000 galones = 9.750.
    expect($filas)->toBe([
        ['saldo_inicial', 'SALDO INICIAL', 1000.0, 0.0, 9000000.0, 0.0, 9000.0],
        ['compra', '1006', 3000.0, 0.0, 30000000.0, 0.0, 9750.0],
        ['venta', '', 0.0, 60.0, 0.0, 585000.0, 9750.0],
    ])
        ->and($kardex['totales']['saldo_unidades'])->toBe(3940.0)
        ->and(round($kardex['totales']['valor_saldo']))->toBe(38415000.0);
});

test('el saldo inicial de canastilla abre el kardex de la referencia y suma en el control', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Lubricant::create(['reference' => 'MOBIL 20W50', 'sale_price' => 50000, 'iva' => 0, 'total' => 50000, 'cost_price' => 30000, 'active' => true]);
    InventarioSaldoInicial::create(['inventario' => InventarioSaldoInicial::CANASTILLA, 'referencia' => 'MOBIL 20W50', 'fecha_corte' => '2026-09-20', 'unidades' => 12, 'costo_unitario' => 30000]);

    $kardex = app(KardexInventarioService::class)->canastilla('MOBIL 20W50');

    expect($kardex['rows'][0])->toMatchArray(['tipo' => 'saldo_inicial', 'entradas' => 12.0, 'valor_entradas' => 360000.0])
        ->and(app(KardexInventarioService::class)->canastillaControl()['totales'])->toMatchArray(['saldo_unidades' => 12.0, 'valor_saldo' => 360000.0]);

    $this->actingAs($admin)->get(route('inventarios-lubricantes.create', ['producto' => 'MOBIL 20W50', 'desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertOk()
        ->assertSeeInOrder(['20/09/2026', 'SALDO INICIAL', '360.000']);
});

test('el administrador carga los saldos iniciales de inventario y el jefe de patios no accede', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    Lubricant::create(['reference' => 'MOBIL 20W50', 'sale_price' => 50000, 'iva' => 0, 'total' => 50000, 'cost_price' => 30000, 'active' => true]);
    InventarioSaldoInicial::create(['inventario' => InventarioSaldoInicial::ACPM, 'fecha_corte' => '2026-09-01', 'unidades' => 50, 'costo_unitario' => 8000]);

    $this->actingAs($jefe)->get(route('inventarios-saldos-iniciales.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('inventarios-saldos-iniciales.index'))
        ->assertOk()
        ->assertSeeInOrder(['GASOLINA (CORRIENTE)', 'ACPM', 'MOBIL 20W50']);

    $saldos = [
        ['inventario' => 'gasolina', 'referencia' => '', 'unidades' => '1.500,5', 'costo_unitario' => '9.800'],
        // En blanco: se borra el saldo que tenía.
        ['inventario' => 'acpm', 'referencia' => '', 'unidades' => '', 'costo_unitario' => ''],
        ['inventario' => 'canastilla', 'referencia' => 'MOBIL 20W50', 'unidades' => '12', 'costo_unitario' => '30.000'],
    ];

    $this->actingAs($admin)->post(route('inventarios-saldos-iniciales.store'), ['fecha_corte' => '2026-10-13', 'saldos' => $saldos])
        ->assertRedirect(route('inventarios-saldos-iniciales.index'))
        ->assertSessionHasNoErrors();

    expect(InventarioSaldoInicial::query()->orderBy('inventario')->get()->map(fn ($s) => [$s->inventario, $s->referencia, $s->fecha_corte->format('Y-m-d'), (float) $s->unidades, (float) $s->costo_unitario])->all())
        ->toBe([
            ['canastilla', 'MOBIL 20W50', '2026-10-13', 12.0, 30000.0],
            ['gasolina', '', '2026-10-13', 1500.5, 9800.0],
        ]);

    // Con cantidad pero sin costo no se guarda.
    $saldos[0]['costo_unitario'] = '';
    $this->actingAs($admin)->post(route('inventarios-saldos-iniciales.store'), ['fecha_corte' => '2026-10-13', 'saldos' => $saldos])
        ->assertSessionHasErrors('saldos');

    expect((float) InventarioSaldoInicial::query()->where('inventario', 'gasolina')->value('costo_unitario'))->toBe(9800.0);
});

test('el kardex se consulta por periodo: lo anterior queda en SALDO ANTERIOR y los totales son del periodo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    compraCombustible(['fecha' => '2026-09-01', 'factura' => 'A-1']);
    compraCombustible(['fecha' => '2026-10-05', 'factura' => 'A-2']);
    Turno::create(['fecha' => '2026-10-06', 'numero_turno' => 1, 'precio_corriente' => 16400, 'tirillas_galones_corriente' => 100, 'tirillas_valor_corriente' => 1640000]);

    $response = $this->actingAs($admin)
        ->get(route('inventarios-gasolina.create', ['desde' => '2026-10-01', 'hasta' => '2026-10-31']))
        ->assertOk()
        // Filtro de fechas, imprimir y Excel en el encabezado.
        ->assertSee('IMPRIMIR')
        ->assertSee(route('inventarios-gasolina.exportar', ['desde' => '2026-10-01', 'hasta' => '2026-10-31']))
        ->assertSeeInOrder(['SALDO ANTERIOR', 'A-2'], false)
        ->assertDontSee('>A-1<', false);

    $kardex = $response->viewData('kardex');

    expect(array_column($kardex['rows'], 'tipo'))->toBe(['saldo_anterior', 'compra', 'venta'])
        ->and($kardex['rows'][0])->toMatchArray(['saldo_unidades' => 3000.0, 'valor_saldo' => 30000000.0, 'promedio' => 10000.0])
        ->and($kardex['totales'])->toMatchArray(['entradas' => 3000.0, 'salidas' => 100.0, 'saldo_unidades' => 5900.0, 'valor_salidas' => 1000000.0]);
});

test('los kardex de inventario se exportan a Excel con el periodo', function (string $ruta, array $parametros, string $titulo) {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Lubricant::create(['reference' => 'MOBIL 20W50', 'sale_price' => 50000, 'iva' => 0, 'total' => 50000, 'cost_price' => 30000, 'active' => true]);
    compraCombustible(['fecha' => '2026-10-05']);
    CompraLubricante::create(['fecha' => '2026-10-05', 'nombre' => 'MOBIL 20W50', 'no_fc' => 'L-1', 'unidades' => 10, 'valor_unitario' => 30000, 'vr_sin_iva' => 300000, 'iva' => 0, 'total' => 300000]);

    $response = $this->actingAs($admin)
        ->get(route($ruta, $parametros + ['desde' => '2026-10-01', 'hasta' => '2026-10-31']))
        ->assertOk();

    $archivo = tempnam(sys_get_temp_dir(), 'kardex').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('A1')->getValue())->toBe($titulo)
        ->and($hoja->getCell('A2')->getValue())->toBe('DESDE 01/10/2026  HASTA 31/10/2026')
        ->and($hoja->getCell('B5')->getValue())->toBe('05/10/2026')
        ->and($hoja->getCell('A6')->getValue())->toBe('TOTALES');
})->with([
    ['inventarios-gasolina.exportar', [], 'INVENTARIOS GASOLINA'],
    ['inventarios-acpm.exportar', [], 'INVENTARIOS ACPM'],
    ['inventarios-lubricantes.exportar', ['producto' => 'MOBIL 20W50'], 'INVENTARIOS MOBIL 20W50'],
    ['inventarios-lubricantes.control.exportar', [], 'INVENTARIO CANASTILLA – CONTROL'],
]);
