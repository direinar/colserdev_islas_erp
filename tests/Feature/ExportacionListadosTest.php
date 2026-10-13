<?php

use App\Models\Compra;
use App\Models\Lubricant;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

function hojaDescargada(TestResponse $response): Worksheet
{
    $archivo = tempnam(sys_get_temp_dir(), 'listado').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    return $hoja;
}

test('precios de canastilla, proveedores y compras registradas tienen imprimir y Excel', function (string $pantalla, string $exportar) {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->get(route($pantalla))
        ->assertOk()
        ->assertSee('IMPRIMIR')
        ->assertSee(route($exportar), false);
})->with([
    ['lubricants.index', 'lubricants.exportar'],
    ['proveedores.index', 'proveedores.exportar'],
]);

test('los precios de canastilla se exportan a Excel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Lubricant::create(['reference' => 'MOBIL 20W50', 'sale_price' => 42000, 'iva' => 0, 'total' => 42000, 'cost_price' => 30000, 'active' => true]);

    $this->actingAs($admin)->get(route('lubricants.index'))->assertSee('Precios de canastilla');

    $hoja = hojaDescargada($this->actingAs($admin)->get(route('lubricants.exportar'))->assertDownload('precios-de-canastilla.xlsx'));

    expect($hoja->getCell('A1')->getValue())->toBe('PRECIOS DE CANASTILLA')
        ->and($hoja->getCell('A5')->getValue())->toBe('MOBIL 20W50')
        ->and((float) $hoja->getCell('B5')->getValue())->toBe(42000.0)
        ->and($hoja->getCell('G5')->getValue())->toBe('ACTIVO');
});

test('los proveedores se exportan a Excel con el NIT como texto', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Proveedor::create(['name' => 'CHEVRON PETROLEUM', 'document' => '0860005223']);

    $hoja = hojaDescargada($this->actingAs($admin)->get(route('proveedores.exportar'))->assertDownload('proveedores.xlsx'));

    expect($hoja->getCell('A5')->getValue())->toBe('CHEVRON PETROLEUM')
        ->and($hoja->getCell('B5')->getValue())->toBe('0860005223');
});

test('las compras registradas del rango se exportan a Excel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $proveedor = Proveedor::create(['name' => 'TERPEL', 'document' => '830095213']);
    $compra = ['vr_total_fra' => 40000000, 'gasolina' => 3000, 'acpm' => 1000, 'total' => 4000, 'distribucion_gasolina' => 30000000, 'distribucion_acpm' => 10000000];
    Compra::create(['fecha' => '2026-09-25', 'factura' => '1006', 'proveedor_id' => $proveedor->id] + $compra);
    Compra::create(['fecha' => '2026-08-01', 'factura' => 'FUERA'] + $compra);

    $this->actingAs($admin)->get(route('compras.create', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertSee('IMPRIMIR')
        ->assertSee(route('compras.exportar', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']));

    $hoja = hojaDescargada($this->actingAs($admin)
        ->get(route('compras.exportar', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertDownload('compras-combustible-2026-09-01-a-2026-09-30.xlsx'));

    expect($hoja->getCell('A2')->getValue())->toBe('DESDE 01/09/2026  HASTA 30/09/2026')
        ->and($hoja->getCell('A5')->getValue())->toBe('25/09/2026')
        ->and($hoja->getCell('B5')->getValue())->toBe('1006')
        ->and($hoja->getCell('C5')->getValue())->toBe('TERPEL')
        ->and($hoja->getCell('A6')->getValue())->toBe('TOTALES')
        ->and((float) $hoja->getCell('H6')->getValue())->toBe(40000000.0);
});
