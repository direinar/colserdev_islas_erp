<?php

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\ComprobanteCompraCuenta;
use App\Models\Proveedor;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

function compraLubricanteComprobante(Proveedor $proveedor, string $fecha, string $factura, string $producto, float $sinIva, float $iva): CompraLubricante
{
    return CompraLubricante::create([
        'fecha' => $fecha,
        'proveedor_id' => $proveedor->id,
        'nombre' => $producto,
        'no_fc' => $factura,
        'unidades' => 10,
        'valor_unitario' => $sinIva / 10,
        'vr_sin_iva' => $sinIva,
        'iva' => $iva,
        'total' => $sinIva + $iva,
    ]);
}

/**
 * Compras del ejemplo de la plantilla: una factura de Chevron y tres de canastilla,
 * una de ellas con dos productos y con IVA.
 *
 * @return array{chevron: Proveedor, solub: Proveedor}
 */
function comprasDelPeriodo(): array
{
    $chevron = Proveedor::create(['name' => 'CHEVRON PETROLEUM', 'document' => '860023560']);
    $soluciones = Proveedor::create(['name' => 'SOLUCIONES EN LUBRICANTES', 'document' => '901235560']);
    $solub = Proveedor::create(['name' => 'SOLUB', 'document' => '830104556']);

    ComprobanteCompraCuenta::query()
        ->where('clave', ComprobanteCompraCuenta::PROVEEDOR_COMBUSTIBLES)
        ->update(['proveedor_id' => $chevron->id]);

    Compra::create([
        'fecha' => '2026-03-05', 'factura' => '46-1238070', 'vr_total_fra' => 83974669,
        'gasolina' => 4100, 'acpm' => 2130, 'total' => 6230,
        'distribucion_gasolina' => 55264228, 'distribucion_acpm' => 28710441,
    ]);
    // Fuera del periodo: no debe salir.
    Compra::create([
        'fecha' => '2026-04-01', 'factura' => '46-9999999', 'vr_total_fra' => 1000,
        'gasolina' => 1, 'acpm' => 0, 'total' => 1, 'distribucion_gasolina' => 1000, 'distribucion_acpm' => 0,
    ]);

    compraLubricanteComprobante($soluciones, '2026-03-10', '1248', '1-MOBIL 20W50', 228000, 0);
    compraLubricanteComprobante($solub, '2026-03-12', '2150', '2-ADITIVO MOTOS', 554000, 105260);
    compraLubricanteComprobante($solub, '2026-03-12', '2150', '1 - ADITIVO GASOLINA AUTOS', 100000, 19000);

    return ['chevron' => $chevron, 'solub' => $solub];
}

test('el comprobante arma débito y crédito por cada factura de compras con su tercero y NIT', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    comprasDelPeriodo();

    $response = $this->actingAs($admin)
        ->get(route('informes.comprobante-contable-compras.index', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
        ->assertOk()
        ->assertSee('Comprobante contable de compras')
        ->assertDontSee('Los débitos y los créditos no cuadran');

    $lineas = collect($response->viewData('lineas'))
        ->map(fn (array $linea): array => [$linea['cuenta'], $linea['concepto'], $linea['tercero'], $linea['nit'], $linea['debito'], $linea['credito']])
        ->all();

    expect($lineas)->toBe([
        ['143508', 'FC 46-1238070', 'CHEVRON PETROLEUM', '860023560', 83974669.0, 0.0],
        ['230505', 'FC 46-1238070', 'CHEVRON PETROLEUM', '860023560', 0.0, 83974669.0],
        ['143509', 'FC 1248', 'SOLUCIONES EN LUBRICANTES', '901235560', 228000.0, 0.0],
        ['233501', 'FC 1248', 'SOLUCIONES EN LUBRICANTES', '901235560', 0.0, 228000.0],
        // Los dos productos de la factura 2150 van en una sola factura; el IVA en su propia cuenta.
        ['143509', 'FC 2150', 'SOLUB', '830104556', 654000.0, 0.0],
        ['23670506', 'FC 2150', 'SOLUB', '830104556', 124260.0, 0.0],
        ['233501', 'FC 2150', 'SOLUB', '830104556', 0.0, 778260.0],
        // Margen mayorista del periodo: 6.230 galones x 0,3 x 585,71.
        ['230505', 'DESCUENTOS COMPRAS', 'CHEVRON PETROLEUM', '860023560', 1094692.0, 0.0],
        ['421040', 'DESCUENTOS COMPRAS', 'CHEVRON PETROLEUM', '860023560', 0.0, 1094692.0],
    ])
        ->and($response->viewData('totales'))->toBe(['debito' => 86075621.0, 'credito' => 86075621.0]);
});

test('el administrador cambia las cuentas contables y el proveedor de combustibles', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    ['solub' => $solub] = comprasDelPeriodo();

    $this->actingAs($admin)
        ->post(route('informes.comprobante-contable-compras.cuentas'), [
            'cuentas' => [
                ComprobanteCompraCuenta::INVENTARIO_COMBUSTIBLES => ['cuenta' => '14350801'],
                ComprobanteCompraCuenta::IVA_CANASTILLA => ['cuenta' => '24080506'],
            ],
            'proveedor_combustibles_id' => $solub->id,
        ])
        ->assertRedirect();

    $lineas = $this->actingAs($admin)
        ->get(route('informes.comprobante-contable-compras.index', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
        ->viewData('lineas');

    expect($lineas[0])->toMatchArray(['cuenta' => '14350801', 'tercero' => 'SOLUB', 'nit' => '830104556'])
        ->and($lineas[5]['cuenta'])->toBe('24080506');
});

test('solo el administrador accede al comprobante contable de compras', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);

    $this->actingAs($jefe)->get(route('informes.comprobante-contable-compras.index'))->assertForbidden();
    $this->actingAs($jefe)->post(route('informes.comprobante-contable-compras.cuentas'), ['cuentas' => []])->assertForbidden();
});

test('el comprobante contable de compras se exporta a Excel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    comprasDelPeriodo();

    $response = $this->actingAs($admin)
        ->get(route('informes.comprobante-contable-compras.exportar', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
        ->assertOk()
        ->assertDownload('comprobante-contable-compras-2026-03-01-a-2026-03-31.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'comprobante').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('A5')->getValue())->toBe('143508')
        ->and($hoja->getCell('B5')->getValue())->toBe('FC 46-1238070')
        ->and($hoja->getCell('D5')->getValue())->toBe('860023560')
        ->and((float) $hoja->getCell('E5')->getValue())->toBe(83974669.0)
        ->and($hoja->getCell('B12')->getValue())->toBe('DESCUENTOS COMPRAS')
        ->and($hoja->getCell('B14')->getValue())->toBe('TOTALES')
        ->and((float) $hoja->getCell('E14')->getValue())->toBe(86075621.0)
        ->and((float) $hoja->getCell('F14')->getValue())->toBe(86075621.0);
});
