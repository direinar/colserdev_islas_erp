<?php

use App\Models\Banco;
use App\Models\ComprobanteRecaudoCuenta;
use App\Models\Customer;
use App\Models\Turno;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Planilla 20: recaudo por islas de Juan Carlos y dos por administración (Bancolombia y
 * Davivienda). Planilla 21: islas y administración en caja de Arkatec. Planilla 22 fuera del periodo.
 */
function recaudosDelPeriodo(): array
{
    $bancos = [
        'CAJA' => Banco::create(['name' => 'CAJA', 'cuenta_contable' => '11050501']),
        'BANCOLOMBIA' => Banco::create(['name' => 'BANCOLOMBIA', 'cuenta_contable' => '11100510']),
        'DAVIVIENDA' => Banco::create(['name' => 'DAVIVIENDA', 'cuenta_contable' => '11100502']),
    ];
    $juan = Customer::create(['name' => 'JUAN CARLOS GIL', 'document' => '1001']);
    $consorcio = Customer::create(['name' => 'CONSORCIO LIDER', 'document' => '1002']);
    $arkatec = Customer::create(['name' => 'ARKATEC', 'document' => '1003']);

    $turno20 = Turno::create(['fecha' => '2026-09-25', 'numero_turno' => 20]);
    $turno20->recaudos()->create(['cliente_id' => $juan->id, 'valor' => 500000, 'total' => 500000]);
    $turno20->recaudosAdmin()->create(['banco' => 'BANCOLOMBIA', 'responsable_id' => $consorcio->id, 'valor' => 300000, 'total' => 500000]);
    $turno20->recaudosAdmin()->create(['banco' => 'DAVIVIENDA', 'responsable_id' => $juan->id, 'valor' => 200000, 'total' => 500000]);

    $turno21 = Turno::create(['fecha' => '2026-09-26', 'numero_turno' => 21]);
    $turno21->recaudos()->create(['cliente_id' => $arkatec->id, 'valor' => 100000, 'total' => 100000]);
    $turno21->recaudosAdmin()->create(['banco' => 'CAJA', 'responsable_id' => $arkatec->id, 'valor' => 50000, 'total' => 50000]);

    $turno22 = Turno::create(['fecha' => '2026-10-02', 'numero_turno' => 22]);
    $turno22->recaudos()->create(['cliente_id' => $juan->id, 'valor' => 999999, 'total' => 999999]);

    return $bancos;
}

test('el informe lista cada recaudo del periodo con su banco y concepto', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    recaudosDelPeriodo();

    $response = $this->actingAs($jefe)
        ->get(route('informes.recaudos-cartera.index', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertOk()
        ->assertSee('Informe de recaudos de cartera')
        ->assertSee('COMPROBANTE CONTABLE - RECAUDOS DE CARTERA')
        // El jefe de patios consulta, pero no cambia las cuentas.
        ->assertDontSee('Guardar cuentas');

    $recaudos = array_map(fn (array $r): array => array_values($r), $response->viewData('recaudos'));

    expect($recaudos)->toBe([
        [20, '2026-09-25', 'JUAN CARLOS GIL', 'CAJA', 'Recaudo por Islas', 500000.0],
        [20, '2026-09-25', 'CONSORCIO LIDER', 'BANCOLOMBIA', 'Recaudo por Admón', 300000.0],
        [20, '2026-09-25', 'JUAN CARLOS GIL', 'DAVIVIENDA', 'Recaudo por Admón', 200000.0],
        [21, '2026-09-26', 'ARKATEC', 'CAJA', 'Recaudo por Islas', 100000.0],
        [21, '2026-09-26', 'ARKATEC', 'CAJA', 'Recaudo por Admón', 50000.0],
    ])
        ->and($response->viewData('total'))->toBe(1150000.0);
});

test('el comprobante debita caja y cada banco y acredita la cartera de cada cliente partida por partida', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    recaudosDelPeriodo();

    $comprobante = $this->actingAs($admin)
        ->get(route('informes.recaudos-cartera.index', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->viewData('comprobante');

    expect(array_map(fn (array $linea): array => array_values($linea), $comprobante['lineas']))->toBe([
        ['11050501', 'RECAUDO POR ISLAS', 600000.0, 0.0],
        ['11100510', 'BANCOLOMBIA', 300000.0, 0.0],
        ['11050501', 'CAJA', 50000.0, 0.0],
        ['11100502', 'DAVIVIENDA', 200000.0, 0.0],
        ['13050501', 'ARKATEC', 0.0, 100000.0],
        ['13050501', 'ARKATEC', 0.0, 50000.0],
        ['13050501', 'CONSORCIO LIDER', 0.0, 300000.0],
        ['13050501', 'JUAN CARLOS GIL', 0.0, 500000.0],
        ['13050501', 'JUAN CARLOS GIL', 0.0, 200000.0],
    ])
        ->and($comprobante['debito'])->toBe(1150000.0)
        ->and($comprobante['credito'])->toBe(1150000.0)
        ->and($comprobante['diferencia'])->toBe(0.0);
});

test('solo el administrador cambia las cuentas de caja, cartera y bancos', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $bancos = recaudosDelPeriodo();
    $datos = [
        'cuentas' => [ComprobanteRecaudoCuenta::CARTERA_CLIENTES => ['cuenta' => '13050599']],
        'bancos' => [$bancos['DAVIVIENDA']->id => '11100599'],
    ];

    $this->actingAs($jefe)->post(route('informes.recaudos-cartera.cuentas'), $datos)->assertForbidden();
    $this->actingAs($admin)->post(route('informes.recaudos-cartera.cuentas'), $datos)->assertRedirect();

    expect(ComprobanteRecaudoCuenta::query()->where('clave', ComprobanteRecaudoCuenta::CARTERA_CLIENTES)->value('cuenta'))->toBe('13050599')
        ->and($bancos['DAVIVIENDA']->fresh()->cuenta_contable)->toBe('11100599');
});

test('el islero no tiene acceso al informe de recaudos de cartera', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $this->actingAs($islero)->get(route('informes.recaudos-cartera.index'))->assertForbidden();
});

test('el informe de recaudos de cartera se exporta a Excel', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    recaudosDelPeriodo();

    $response = $this->actingAs($admin)
        ->get(route('informes.recaudos-cartera.exportar', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertOk()
        ->assertDownload('recaudos-cartera-2026-09-01-a-2026-09-30.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'recaudos').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('C5')->getValue())->toBe('JUAN CARLOS GIL')
        ->and($hoja->getCell('E10')->getValue())->toBe('TOTAL DEL PERIODO')
        ->and((float) $hoja->getCell('F10')->getValue())->toBe(1150000.0)
        ->and($hoja->getCell('H5')->getValue())->toBe('11050501')
        ->and($hoja->getCell('I14')->getValue())->toBe('TOTALES')
        ->and((float) $hoja->getCell('J14')->getValue())->toBe(1150000.0);
});
