<?php

use App\Models\AforoTanque;
use App\Models\Compra;
use App\Models\TanqueSaldoInicial;
use App\Models\Turno;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

function compraTanques(string $fecha, float $gasolina, float $acpm): Compra
{
    return Compra::create([
        'fecha' => $fecha,
        'factura' => 'F-'.$fecha,
        'vr_total_fra' => 1000000,
        'gasolina' => $gasolina,
        'acpm' => $acpm,
        'total' => $gasolina + $acpm,
        'distribucion_gasolina' => 500000,
        'distribucion_acpm' => 500000,
    ]);
}

/**
 * Turno con galones vendidos según cortes de IAPROPIADA. Las lecturas de los surtidores
 * llevan otro valor a propósito: el control de tanques no debe usarlas.
 */
function turnoTanques(string $fecha, int $numero, float $corriente, float $acpm): Turno
{
    return Turno::create([
        'fecha' => $fecha,
        'numero_turno' => $numero,
        'tirillas_galones_corriente' => $corriente,
        'tirillas_galones_acpm' => $acpm,
        'lecturas_galones_corriente' => $corriente + 1000,
        'lecturas_galones_acpm' => $acpm + 1000,
    ]);
}

test('el control de tanques arma el saldo diario con compras, los cortes de IAPROPIADA de los turnos de cada fecha y el aforo', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    TanqueSaldoInicial::create(['combustible' => 'corriente', 'fecha_corte' => '2026-09-01', 'galones' => 3350]);
    TanqueSaldoInicial::create(['combustible' => 'acpm', 'fecha_corte' => '2026-09-01', 'galones' => 4265]);

    // Movimientos anteriores al informe: forman el VIENEN.
    compraTanques('2026-09-10', 1000, 500);
    turnoTanques('2026-09-10', 1, 200, 100);

    turnoTanques('2026-09-25', 2, 60, 40);
    turnoTanques('2026-09-25', 3, 44, 3);
    compraTanques('2026-09-26', 3800, 4000);
    turnoTanques('2026-09-26', 4, 100, 50);
    AforoTanque::create(['fecha' => '2026-09-26', 'vara_corriente' => 321, 'tabla_corriente' => 7800, 'vara_acpm' => 92.26, 'tabla_acpm' => 9700]);

    $response = $this->actingAs($jefe)
        ->get(route('control-tanques.index', ['desde' => '2026-09-25', 'hasta' => '2026-09-27']))
        ->assertOk()
        ->assertSee('INFORME DE CONTROL DE TANQUES')
        ->assertSee('VIENEN');

    expect($response->viewData('vienen'))->toBe(['corriente' => 4150.0, 'acpm' => 4665.0]);

    [$dia25, $dia26, $dia27] = $response->viewData('filas');

    expect($dia25['corriente'])->toMatchArray(['ingreso' => 0.0, 'venta' => 104.0, 'saldo' => 4046.0, 'faltante' => null])
        ->and($dia25['acpm'])->toMatchArray(['venta' => 43.0, 'saldo' => 4622.0])
        ->and($dia25)->toMatchArray(['venta_diaria' => 147.0, 'venta_acumulada' => 147.0])
        ->and($dia26['corriente'])->toMatchArray(['ingreso' => 3800.0, 'venta' => 100.0, 'saldo' => 7746.0, 'vara' => 321.0, 'tabla' => 7800.0, 'faltante' => -54.0])
        ->and($dia26['acpm'])->toMatchArray(['saldo' => 8572.0, 'tabla' => 9700.0, 'faltante' => -1128.0])
        ->and($dia26)->toMatchArray(['venta_diaria' => 150.0, 'venta_acumulada' => 297.0])
        ->and($dia27['corriente'])->toMatchArray(['venta' => 0.0, 'saldo' => 7746.0])
        ->and($response->viewData('totales'))->toMatchArray([
            'ingreso_corriente' => 3800.0,
            'venta_corriente' => 204.0,
            'ingreso_acpm' => 4000.0,
            'venta_acpm' => 93.0,
            'venta_diaria' => 297.0,
        ]);

    // Los aforos se muestran sin separador de miles para que al volver a guardar no cambien.
    $response->assertSee('(54,00)')
        ->assertSee('name="aforos[2026-09-26][tabla_corriente]"', false)
        ->assertSee('value="7800"', false)
        ->assertSee('value="92,26"', false);
});

test('el INGRESO se arrastra automáticamente del módulo de compras según su fecha', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $ingresos = fn (): array => collect(
        $this->actingAs($jefe)
            ->get(route('control-tanques.index', ['desde' => '2026-09-25', 'hasta' => '2026-09-26']))
            ->viewData('filas')
    )->mapWithKeys(fn (array $fila): array => [$fila['fecha'] => [$fila['corriente']['ingreso'], $fila['acpm']['ingreso']]])->all();

    // Dos facturas del mismo día se suman en la fila de esa fecha.
    $this->actingAs($jefe)->post(route('compras.store'), ['compras' => [
        ['fecha' => '2026-09-25', 'factura' => '1006', 'vr_total_fra' => '40.000.000', 'gasolina' => '3.000', 'acpm' => '1.000'],
        ['fecha' => '2026-09-25', 'factura' => '1007', 'vr_total_fra' => '10.000.000', 'gasolina' => '800', 'acpm' => '200'],
    ]])->assertRedirect();

    expect($ingresos())->toBe(['2026-09-25' => [3800.0, 1200.0], '2026-09-26' => [0.0, 0.0]]);

    // Al corregir la fecha o los galones de la compra, el informe se actualiza solo.
    $compra = Compra::query()->where('factura', '1007')->sole();
    $this->actingAs($jefe)->put(route('compras.update', $compra), [
        'fecha' => '2026-09-26', 'factura' => '1007', 'vr_total_fra' => '10.000.000', 'gasolina' => '900', 'acpm' => '100',
    ])->assertRedirect();

    expect($ingresos())->toBe(['2026-09-25' => [3000.0, 1000.0], '2026-09-26' => [900.0, 100.0]]);

    $this->actingAs($jefe)->delete(route('compras.destroy', $compra))->assertRedirect();

    expect($ingresos())->toBe(['2026-09-25' => [3000.0, 1000.0], '2026-09-26' => [0.0, 0.0]]);
});

test('si el informe empieza antes del corte, el saldo arranca el día del saldo inicial', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    TanqueSaldoInicial::create(['combustible' => 'corriente', 'fecha_corte' => '2026-09-02', 'galones' => 1000]);
    turnoTanques('2026-09-01', 1, 30, 0);
    turnoTanques('2026-09-02', 2, 50, 0);

    $response = $this->actingAs($admin)
        ->get(route('control-tanques.index', ['desde' => '2026-09-01', 'hasta' => '2026-09-02']))
        ->assertOk()
        ->assertSee('Falta el saldo inicial de A C P M');

    [$dia1, $dia2] = $response->viewData('filas');

    expect($response->viewData('vienen')['corriente'])->toBeNull()
        ->and($dia1['corriente']['saldo'])->toBeNull()
        ->and($dia2['corriente']['saldo'])->toBe(950.0);
});

test('el jefe de patios registra el aforo nocturno y una fecha sin datos borra su aforo', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    AforoTanque::create(['fecha' => '2026-09-26', 'tabla_corriente' => 100]);

    $this->actingAs($jefe)
        ->post(route('control-tanques.aforos'), [
            'desde' => '2026-09-25',
            'hasta' => '2026-09-26',
            'aforos' => [
                '2026-09-25' => ['vara_corriente' => '63', 'tabla_corriente' => '3.246,5', 'vara_acpm' => '52,55', 'tabla_acpm' => '4262'],
                '2026-09-26' => ['vara_corriente' => '', 'tabla_corriente' => '', 'vara_acpm' => '', 'tabla_acpm' => ''],
            ],
        ])
        ->assertRedirect(route('control-tanques.index', ['desde' => '2026-09-25', 'hasta' => '2026-09-26']));

    $aforo = AforoTanque::query()->sole();

    expect($aforo->fecha->format('Y-m-d'))->toBe('2026-09-25')
        ->and((float) $aforo->vara_corriente)->toBe(63.0)
        ->and((float) $aforo->tabla_corriente)->toBe(3246.5)
        ->and((float) $aforo->vara_acpm)->toBe(52.55)
        ->and((float) $aforo->tabla_acpm)->toBe(4262.0)
        ->and($aforo->user_id)->toBe($jefe->id);
});

test('solo el administrador registra el saldo inicial de los tanques', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $datos = ['combustible' => 'acpm', 'fecha_corte' => '2026-09-01', 'galones' => '4265'];

    $this->actingAs($jefe)->post(route('control-tanques.saldo-inicial'), $datos)->assertForbidden();

    $this->actingAs($admin)->post(route('control-tanques.saldo-inicial'), $datos)->assertRedirect();
    $this->actingAs($admin)->post(route('control-tanques.saldo-inicial'), ['galones' => '4300,5'] + $datos)->assertRedirect();

    $saldo = TanqueSaldoInicial::query()->sole();

    expect($saldo->combustible)->toBe('acpm')
        ->and((float) $saldo->galones)->toBe(4300.5);

    $this->actingAs($admin)->get(route('control-tanques.index'))->assertSee('value="4300,5"', false);
});

test('el islero no tiene acceso al control de tanques', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $this->actingAs($islero)->get(route('control-tanques.index'))->assertForbidden();
});

test('el control de tanques se exporta a Excel', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    turnoTanques('2026-09-25', 1, 60, 40);

    $response = $this->actingAs($jefe)
        ->get(route('control-tanques.exportar', ['desde' => '2026-09-25', 'hasta' => '2026-09-25']))
        ->assertOk()
        ->assertDownload('control-de-tanques-2026-09-25-a-2026-09-25.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'tanques').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('A1')->getValue())->toBe('INFORME DE CONTROL DE TANQUES')
        ->and($hoja->getCell('A7')->getValue())->toBe('25/09/2026')
        ->and((float) $hoja->getCell('C7')->getValue())->toBe(60.0)
        ->and((float) $hoja->getCell('I7')->getValue())->toBe(40.0)
        ->and((float) $hoja->getCell('N7')->getValue())->toBe(100.0);
});
