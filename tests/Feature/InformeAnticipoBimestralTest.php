<?php

use App\Models\Compra;
use App\Models\LiquidacionAnticipoBimestral;
use App\Models\MargenIntermediacion;
use App\Models\Turno;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

function compraAnticipo(string $fecha, string $factura, float $gasolina, float $acpm): Compra
{
    return Compra::create([
        'fecha' => $fecha, 'factura' => $factura, 'vr_total_fra' => ($gasolina + $acpm) * 13000,
        'gasolina' => $gasolina, 'acpm' => $acpm, 'total' => $gasolina + $acpm,
        'distribucion_gasolina' => $gasolina * 13000, 'distribucion_acpm' => $acpm * 13000,
    ]);
}

/**
 * Galones del ejemplo de la plantilla: enero 44.018 y febrero 30.350; más marzo y abril
 * para el segundo bimestre. Ventas de canastilla en febrero (dentro) y marzo (fuera del 1er bimestre).
 */
function comprasAnticipo2026(): void
{
    compraAnticipo('2026-01-05', '46-1238070', 4100, 2130);
    compraAnticipo('2026-01-20', '46-1238505', 18900, 18888);
    compraAnticipo('2026-02-10', '46-1238829', 14250, 16100);
    compraAnticipo('2026-03-05', '46-1239101', 3800, 2500);
    compraAnticipo('2026-04-15', '46-1239454', 2500, 3800);

    $febrero = Turno::create(['fecha' => '2026-02-12', 'numero_turno' => 40]);
    $febrero->lubricantes()->create(['cantidad' => 100, 'producto' => '2-ADITIVO MOTOS', 'valor_sin_iva' => 554000, 'iva' => 105260, 'total' => 659260]);
    $marzo = Turno::create(['fecha' => '2026-03-12', 'numero_turno' => 41]);
    $marzo->lubricantes()->create(['cantidad' => 10, 'producto' => 'UREA', 'valor_sin_iva' => 22000, 'iva' => 0, 'total' => 22000]);
}

test('el primer bimestre liquida enero con el margen anterior y febrero con el del año', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    comprasAnticipo2026();
    LiquidacionAnticipoBimestral::create(['anio' => 2026, 'bimestre' => 1, 'otros_ingresos' => 1000000, 'descuentos_ventas' => 500000]);

    $response = $this->actingAs($admin)
        ->get(route('informes.anticipo-bimestral.index', ['anio' => 2026, 'bimestre' => 1]))
        ->assertOk()
        ->assertSee('Informe anticipo bimestral')
        ->assertSee('LIQUIDACIÓN PRIMER BIMESTRE')
        ->assertSee('48.377.983')
        ->assertSee('35.057.285');

    [$enero, $febrero] = $response->viewData('meses');

    expect($enero)->toMatchArray(['nombre' => 'ENERO', 'galones' => 44018.0, 'margen' => 1099.05, 'pesos' => 48377983.0])
        ->and($febrero)->toMatchArray(['nombre' => 'FEBRERO', 'galones' => 30350.0, 'margen' => 1155.1, 'pesos' => 35057285.0])
        ->and($response->viewData('totalBimestre'))->toBe(['galones' => 74368.0, 'pesos' => 83435268.0])
        ->and($response->viewData('compras'))->toHaveCount(2)
        ->and($response->viewData('liquidacion'))->toBe([
            'ventas_combustible' => 83435268.0,
            'ventas_lubricantes' => 554000.0,
            'subtotal' => 83989268.0,
            'otros_ingresos' => 1000000.0,
            'base_simple' => 84989268.0,
            'descuentos_ventas' => 500000.0,
            'base_ica' => 84489268.0,
        ]);
});

test('el acumulado del segundo bimestre suma enero a su margen y febrero a abril al margen del año', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    comprasAnticipo2026();

    $response = $this->actingAs($admin)
        ->get(route('informes.anticipo-bimestral.index', ['anio' => 2026, 'bimestre' => 2]))
        ->assertOk()
        ->assertSee('FEBRERO A ABRIL');

    $acumulado = $response->viewData('acumulado');

    expect($acumulado['filas'][0])->toMatchArray(['nombre' => 'ENERO', 'galones' => 44018.0, 'pesos' => 48377983.0])
        ->and($acumulado['filas'][1])->toMatchArray(['nombre' => 'FEBRERO A ABRIL', 'galones' => 42950.0, 'margen' => 1155.1, 'pesos' => 49611545.0])
        ->and($acumulado['total'])->toBe(['galones' => 86968.0, 'pesos' => 97989528.0])
        ->and($response->viewData('liquidacion')['ventas_lubricantes'])->toBe(22000.0);
});

test('el administrador registra otros ingresos, descuentos y márgenes; el jefe de patios no', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $ajustes = ['anio' => 2026, 'bimestre' => 3, 'otros_ingresos' => '2.500.000', 'descuentos_ventas' => '300.000'];
    $margen = ['vigente_desde' => '2027-02-01', 'valor' => '1200,50'];

    $this->actingAs($jefe)->post(route('informes.anticipo-bimestral.ajustes'), $ajustes)->assertForbidden();
    $this->actingAs($jefe)->post(route('informes.anticipo-bimestral.margenes'), $margen)->assertForbidden();

    $this->actingAs($admin)->post(route('informes.anticipo-bimestral.ajustes'), $ajustes)
        ->assertRedirect(route('informes.anticipo-bimestral.index', ['anio' => 2026, 'bimestre' => 3]));
    $this->actingAs($admin)->post(route('informes.anticipo-bimestral.margenes'), $margen)->assertRedirect();
    // Repetir la fecha corrige el margen en vez de duplicarlo.
    $this->actingAs($admin)->post(route('informes.anticipo-bimestral.margenes'), ['valor' => '1210'] + $margen)->assertRedirect();

    $liquidacion = LiquidacionAnticipoBimestral::query()->sole();

    expect((float) $liquidacion->otros_ingresos)->toBe(2500000.0)
        ->and((float) $liquidacion->descuentos_ventas)->toBe(300000.0)
        ->and(MargenIntermediacion::query()->whereDate('vigente_desde', '2027-02-01')->sole()->valor)->toBe('1210.00');
});

test('el informe de anticipo bimestral se exporta a Excel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    comprasAnticipo2026();

    $response = $this->actingAs($admin)
        ->get(route('informes.anticipo-bimestral.exportar', ['anio' => 2026, 'bimestre' => 1]))
        ->assertOk()
        ->assertDownload('anticipo-bimestral-2026-bimestre-1.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'anticipo').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('A1')->getValue())->toBe('INFORME ANTICIPO BIMESTRAL')
        ->and($hoja->getCell('J4')->getValue())->toBe('LIQUIDACIÓN PRIMER BIMESTRE')
        ->and($hoja->getCell('J6')->getValue())->toBe('ENERO')
        ->and((float) $hoja->getCell('M6')->getValue())->toBe(48377983.0)
        ->and((float) $hoja->getCell('M8')->getValue())->toBe(83435268.0);
});
