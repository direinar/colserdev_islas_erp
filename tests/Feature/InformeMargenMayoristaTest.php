<?php

use App\Models\Compra;
use App\Models\MargenMayorista;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Las siete facturas de la plantilla: 562.527.126 en facturas y 44.018 galones.
 */
function facturasMayorista(): void
{
    $facturas = [
        ['2026-03-03', '46-1238070', 4100, 2130, 83974669],
        ['2026-03-08', '46-1238505', 3800, 2488, 83011638],
        ['2026-03-11', '46-1238829', 2500, 3800, 76425179],
        ['2026-03-15', '46-1239101', 3800, 2500, 83132641],
        ['2026-03-19', '46-1239454', 2500, 3800, 76425179],
        ['2026-03-22', '43-1239807', 2500, 3800, 76425179],
        ['2026-03-26', '46-1240115', 3800, 2500, 83132641],
    ];

    foreach ($facturas as [$fecha, $factura, $gasolina, $acpm, $valor]) {
        Compra::create([
            'fecha' => $fecha, 'factura' => $factura, 'vr_total_fra' => $valor, 'gasolina' => $gasolina, 'acpm' => $acpm,
            'total' => $gasolina + $acpm, 'distribucion_gasolina' => $valor / 2, 'distribucion_acpm' => $valor / 2,
        ]);
    }

    // Fuera del periodo.
    Compra::create([
        'fecha' => '2026-04-02', 'factura' => '46-9999999', 'vr_total_fra' => 1000, 'gasolina' => 1, 'acpm' => 0,
        'total' => 1, 'distribucion_gasolina' => 1000, 'distribucion_acpm' => 0,
    ]);
}

test('el margen mayorista del periodo es total de galones por porcentaje por margen', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    facturasMayorista();

    $response = $this->actingAs($admin)
        ->get(route('informes.margen-mayorista.index', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
        ->assertOk()
        ->assertSee('Informe margen mayorista')
        ->assertSee('7.734.535');

    expect($response->viewData('facturas'))->toHaveCount(7)
        ->and($response->viewData('totales'))->toBe(['valor' => 562527126.0, 'gasolina' => 23000.0, 'acpm' => 21018.0, 'galones' => 44018.0])
        ->and($response->viewData('liquidacion'))->toBe([
            ['vigente_desde' => '2026-01-01', 'galones' => 44018.0, 'porcentaje' => 0.3, 'margen' => 585.71, 'valor' => 7734535.0],
        ])
        ->and($response->viewData('margen_mayorista'))->toBe(7734535.0);
});

test('si el convenio cambia dentro del periodo, cada factura usa los parámetros de su fecha', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    facturasMayorista();

    $this->actingAs($admin)
        ->post(route('informes.margen-mayorista.parametros'), ['vigente_desde' => '2026-03-15', 'porcentaje' => '0,5', 'margen' => '600'])
        ->assertRedirect();

    $liquidacion = $this->actingAs($admin)
        ->get(route('informes.margen-mayorista.index', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
        ->viewData('liquidacion');

    // Hasta el 11/03: 18.818 galones al convenio inicial; desde el 15/03: 25.200 al nuevo.
    expect($liquidacion)->toBe([
        ['vigente_desde' => '2026-01-01', 'galones' => 18818.0, 'porcentaje' => 0.3, 'margen' => 585.71, 'valor' => 3306567.0],
        ['vigente_desde' => '2026-03-15', 'galones' => 25200.0, 'porcentaje' => 0.5, 'margen' => 600.0, 'valor' => 7560000.0],
    ]);
});

test('solo el administrador ve el informe y registra los parámetros; repetir la fecha los corrige', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $parametros = ['vigente_desde' => '2027-01-01', 'porcentaje' => '0,3', 'margen' => '600,25'];

    $this->actingAs($jefe)->get(route('informes.margen-mayorista.index'))->assertForbidden();
    $this->actingAs($jefe)->post(route('informes.margen-mayorista.parametros'), $parametros)->assertForbidden();

    $this->actingAs($admin)->post(route('informes.margen-mayorista.parametros'), $parametros)->assertRedirect();
    $this->actingAs($admin)->post(route('informes.margen-mayorista.parametros'), ['margen' => '610'] + $parametros)->assertRedirect();
    // Un porcentaje escrito como 30 (en vez de 0,3) se rechaza.
    $this->actingAs($admin)->post(route('informes.margen-mayorista.parametros'), ['porcentaje' => '30'] + $parametros)
        ->assertSessionHasErrors('porcentaje');

    $parametro = MargenMayorista::query()->whereDate('vigente_desde', '2027-01-01')->sole();

    expect($parametro->margen)->toBe('610.00')
        ->and($parametro->porcentaje)->toBe('0.3000');
});

test('el informe de margen mayorista se exporta a Excel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    facturasMayorista();

    $response = $this->actingAs($admin)
        ->get(route('informes.margen-mayorista.exportar', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
        ->assertOk()
        ->assertDownload('margen-mayorista-2026-03-01-a-2026-03-31.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'mayorista').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('B12')->getValue())->toBe('TOTALES')
        ->and((float) $hoja->getCell('F12')->getValue())->toBe(44018.0)
        ->and($hoja->getCell('C16')->getValue())->toBe('MARGEN MAYORISTA')
        ->and((float) $hoja->getCell('F16')->getValue())->toBe(7734535.0);
});
