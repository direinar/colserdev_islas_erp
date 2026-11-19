<?php

use App\Models\Compra;
use App\Models\InventarioAjuste;
use App\Models\InventarioSaldoInicial;
use App\Models\LecturaElectronica;
use App\Models\Turno;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

function compraSicom(string $fecha, string $factura, float $gasolina, float $acpm): Compra
{
    return Compra::create([
        'fecha' => $fecha,
        'factura' => $factura,
        'vr_total_fra' => 1000000,
        'gasolina' => $gasolina,
        'acpm' => $acpm,
        'total' => $gasolina + $acpm,
        'distribucion_gasolina' => 500000,
        'distribucion_acpm' => 500000,
    ]);
}

function lecturaSicom(string $fecha, string $manguera, float $inicial, float $final): LecturaElectronica
{
    return LecturaElectronica::create([
        'fecha' => $fecha,
        'manguera' => $manguera,
        'combustible' => LecturaElectronica::MANGUERAS[$manguera],
        'lectura_inicial' => $inicial,
        'lectura_final' => $final,
        'galones' => $final - $inicial,
    ]);
}

/**
 * Octubre: saldo al inicio 1.400 / 2.000, compra 3.000 / 2.500, venta 2.800 / 1.200 y una
 * pérdida de 5 galones de corriente.
 */
function movimientosSicom(): void
{
    InventarioSaldoInicial::create(['inventario' => 'gasolina', 'referencia' => '', 'fecha_corte' => '2026-09-01', 'unidades' => 1000, 'costo_unitario' => 10000]);
    InventarioSaldoInicial::create(['inventario' => 'acpm', 'referencia' => '', 'fecha_corte' => '2026-09-01', 'unidades' => 2000, 'costo_unitario' => 9000]);
    compraSicom('2026-09-15', 'FC-0900', 500, 0);
    Turno::create(['fecha' => '2026-09-20', 'numero_turno' => 1, 'tirillas_galones_corriente' => 100, 'tirillas_valor_corriente' => 1600000]);

    compraSicom('2026-10-05', 'FC-1001', 3000, 2500);
    Turno::create(['fecha' => '2026-10-10', 'numero_turno' => 2, 'tirillas_galones_corriente' => 2800, 'tirillas_valor_corriente' => 44800000,
        'tirillas_galones_acpm' => 1200, 'tirillas_valor_acpm' => 12000000]);
    InventarioAjuste::create(['fecha' => '2026-10-31', 'inventario' => 'gasolina', 'referencia' => '', 'motivo' => 'perdida', 'unidades' => 5, 'observacion' => 'Derrame']);

    // Noviembre no entra al informe de octubre.
    compraSicom('2026-11-02', 'FC-1101', 1000, 1000);
}

test('el informe de Gobernacion y SICOM toma lecturas, compras y el resumen del inventario del mes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    movimientosSicom();

    // PLUS 01: la inicial es la final del 30 de septiembre; ACPM 03 arranca en octubre.
    lecturaSicom('2026-09-30', 'PLUS 01', 900, 1000);
    lecturaSicom('2026-10-15', 'PLUS 01', 1000, 1500);
    lecturaSicom('2026-10-31', 'PLUS 01', 1500, 2100);
    lecturaSicom('2026-10-01', 'ACPM 03', 500, 600);
    lecturaSicom('2026-10-30', 'ACPM 03', 600, 900);

    $pantalla = $this->actingAs($admin)->get(route('informes.gobernacion-sicom.index', ['mes' => '2026-10']))
        ->assertOk()
        ->assertSee('MES: OCTUBRE 2026')
        ->assertSee('3 - RESUMEN DEL MES PARA DECLARACIÓN DE SICOM')
        ->assertSee('FC-1001')
        ->assertDontSee('FC-1101')
        ->assertSee('id="lecturas-incompletas"', false);

    $lecturas = collect($pantalla->viewData('lecturas')['filas'])->keyBy('manguera');
    expect($lecturas['PLUS 01'])->toMatchArray(['inicial' => 1000.0, 'final' => 2100.0, 'plus' => 1100.0, 'acpm' => 0.0, 'total' => 1100.0])
        ->and($lecturas['ACPM 03'])->toMatchArray(['inicial' => 500.0, 'final' => 900.0, 'fecha_final' => '2026-10-30', 'acpm' => 400.0])
        ->and($pantalla->viewData('lecturas')['totales'])->toBe(['plus' => 1100.0, 'acpm' => 400.0, 'total' => 1500.0])
        // ACPM 03 no tiene lectura del 31: se avisa.
        ->and($pantalla->viewData('lecturas')['incompletas'])->toContain('ACPM 03')->not->toContain('PLUS 01')
        ->and($pantalla->viewData('compras')['filas'])->toBe([
            ['fecha' => '2026-10-05', 'factura' => 'FC-1001', 'plus' => 3000.0, 'acpm' => 2500.0, 'total' => 5500.0],
        ]);

    $sicom = $pantalla->viewData('sicom');
    expect($sicom['corriente'])->toBe([
        'saldo_inicio' => 1400.0, 'compras' => 3000.0, 'ventas' => 2800.0, 'ajustes' => -5.0,
        'saldo_cierre' => 1595.0, 'saldo_inventario' => 1595.0, 'diferencia' => 0.0,
    ])
        ->and($sicom['acpm'])->toMatchArray(['saldo_inicio' => 2000.0, 'compras' => 2500.0, 'ventas' => 1200.0, 'saldo_cierre' => 3300.0, 'diferencia' => 0.0])
        ->and($sicom['total']['saldo_cierre'])->toBe(4895.0);
});

test('el informe de Gobernacion y SICOM se descarga en Excel y solo lo ve administracion', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    movimientosSicom();

    $respuesta = $this->actingAs($admin)->get(route('informes.gobernacion-sicom.exportar', ['mes' => '2026-10']))
        ->assertOk()
        ->assertDownload('informe-gobernacion-sicom-2026-10.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'sicom');
    file_put_contents($archivo, $respuesta->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    $valores = collect($hoja->toArray())->flatten()->filter()->values();
    unlink($archivo);

    expect($valores)->toContain('INFORME PARA GOBERNACIÓN Y SICOM', '2 - COMPRAS DEL MES', 'FC-1001', 'SALDO AL CIERRE DEL MES');

    // Sin mes, el informe es el del mes anterior; el menú lo muestra a administración.
    $this->actingAs($admin)->get(route('informes.gobernacion-sicom.index'))
        ->assertOk()
        ->assertSee('value="'.now()->subMonthNoOverflow()->format('Y-m').'"', false)
        ->assertSee('Informe Gobernación y SICOM');

    $this->actingAs($islero)->get(route('informes.gobernacion-sicom.index'))->assertForbidden();
});
