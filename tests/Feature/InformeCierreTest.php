<?php

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\GalonajeContrato;
use App\Models\Turno;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Septiembre: compra de 1.000 galones de gasolina a 10.000 y 1.000 de ACPM a 8.000, aditivo
 * a 10.000. Turno del 25 (60 gal gasolina, 50 gal ACPM, descuentos y canastilla) y del 26
 * (40 gal gasolina). Un turno de octubre queda fuera.
 */
function periodoCierre(): void
{
    Compra::create([
        'fecha' => '2026-09-21', 'factura' => '1006', 'vr_total_fra' => 18000000, 'gasolina' => 1000, 'acpm' => 1000,
        'total' => 2000, 'distribucion_gasolina' => 10000000, 'distribucion_acpm' => 8000000,
    ]);
    CompraLubricante::create([
        'fecha' => '2026-09-21', 'nombre' => 'ADITIVO', 'no_fc' => 'L-1', 'unidades' => 10,
        'valor_unitario' => 10000, 'vr_sin_iva' => 100000, 'iva' => 19000, 'total' => 119000,
    ]);

    $turno = Turno::create([
        'fecha' => '2026-09-25', 'numero_turno' => 30,
        'tirillas_galones_corriente' => 60, 'tirillas_valor_corriente' => 984000,
        'tirillas_galones_acpm' => 50, 'tirillas_valor_acpm' => 535000,
    ]);
    $turno->transferencias()->create(['puntos_redimidos' => 20000, 'total_puntos' => 20000]);
    $turno->descuentos()->create(['descuento_no' => 'D-1', 'valor' => 10000, 'total' => 10000]);
    $turno->lubricantes()->create(['cantidad' => 2, 'producto' => 'ADITIVO', 'valor_sin_iva' => 30000, 'iva' => 5700, 'total' => 35700]);

    Turno::create([
        'fecha' => '2026-09-26', 'numero_turno' => 31,
        'tirillas_galones_corriente' => 40, 'tirillas_valor_corriente' => 656000,
    ]);
    Turno::create([
        'fecha' => '2026-10-01', 'numero_turno' => 32,
        'tirillas_galones_corriente' => 500, 'tirillas_valor_corriente' => 8200000,
    ]);
}

test('el informe de cierre arma el estado de resultados del periodo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    periodoCierre();

    $response = $this->actingAs($admin)
        ->get(route('informes.cierre.index', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertOk()
        ->assertSee('INFORMES DE GESTIÓN')
        ->assertSee('UTILIDAD BRUTA TOTAL EDS')
        ->assertSee('$ 1.106.426')
        ->assertSee('Falta registrar los saldos iniciales del contrato de galonaje');

    expect($response->viewData('valores'))->toBe([
        'ventas_acpm' => 535000.0,
        'ventas_gasolina' => 1640000.0,
        'total_ventas' => 2175000.0,
        // 100 gal gasolina x 10.000 + 50 gal ACPM x 8.000.
        'costo_combustible' => 1400000.0,
        'subtotal' => 775000.0,
        'descuentos' => 30000.0,
        'utilidad_combustibles' => 745000.0,
        // 2.000 galones comprados x 0,3 x 585,71.
        'margen_mayorista' => 351426.0,
        'ingresos_combustibles' => 1096426.0,
        'venta_canastilla' => 30000.0,
        'costo_canastilla' => 20000.0,
        'utilidad_canastilla' => 10000.0,
        'utilidad_total' => 1106426.0,
    ])
        ->and($response->viewData('estadisticas'))->toMatchArray([
            'galonaje_periodo' => 150.0,
            'galonaje_acumulado' => null,
            'saldo_contrato' => null,
        ])
        ->and(round($response->viewData('estadisticas')['margen_intermediacion'], 6))->toBe(round(745000 / 2175000, 6));
});

test('desde el corte, los galones vendidos se suman al acumulado y se restan del saldo del contrato', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    periodoCierre();

    $this->actingAs($admin)
        ->post(route('informes.cierre.contrato'), [
            'fecha_corte' => '2026-09-26', 'galonaje_acumulado_inicial' => '5000000', 'saldo_contrato_inicial' => '4800000',
        ])
        ->assertRedirect();

    $estadisticas = $this->actingAs($admin)
        ->get(route('informes.cierre.index', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertSee('Contrato ejecutado')
        ->viewData('estadisticas');

    // Desde el 26/09 solo se vendieron los 40 galones del turno 31.
    expect($estadisticas['galonaje_acumulado'])->toBe(5000040.0)
        ->and($estadisticas['saldo_contrato'])->toBe(4799960.0);

    // Repetir la fecha de corte corrige los saldos en vez de crear otro registro.
    $this->actingAs($admin)->post(route('informes.cierre.contrato'), [
        'fecha_corte' => '2026-09-26', 'galonaje_acumulado_inicial' => '5056279', 'saldo_contrato_inicial' => '4743721',
    ]);

    expect(GalonajeContrato::query()->count())->toBe(1);
});

test('solo el administrador accede al informe de cierre', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);

    $this->actingAs($jefe)->get(route('informes.cierre.index'))->assertForbidden();
    $this->actingAs($jefe)->post(route('informes.cierre.contrato'), [])->assertForbidden();
});

test('el informe de cierre se exporta a Excel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    periodoCierre();

    $response = $this->actingAs($admin)
        ->get(route('informes.cierre.exportar', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertOk()
        ->assertDownload('informes-de-gestion-2026-09-01-a-2026-09-30.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'cierre').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('A5')->getValue())->toBe('VENTAS DE ACPM')
        ->and((float) $hoja->getCell('B5')->getValue())->toBe(535000.0)
        ->and($hoja->getCell('A17')->getValue())->toBe('UTILIDAD BRUTA TOTAL EDS')
        ->and((float) $hoja->getCell('B17')->getValue())->toBe(1106426.0)
        ->and((float) $hoja->getCell('B21')->getValue())->toBe(150.0);
});
