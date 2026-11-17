<?php

use App\Models\Turno;
use App\Models\User;

function planillaComision(string $vendedor, int $numero, float $corriente, float $acpm, float $canastilla, bool $anulada = false): Turno
{
    $turno = Turno::create([
        'fecha' => '2026-11-10',
        'numero_turno' => $numero,
        'nombre_vendedor' => $vendedor,
        'anulada' => $anulada,
        'tirillas_galones_corriente' => $corriente / 10000,
        'tirillas_galones_acpm' => $acpm / 10000,
        'tirillas_valor_corriente' => $corriente,
        'tirillas_valor_acpm' => $acpm,
    ]);
    if ($canastilla > 0) {
        $turno->lubricantes()->create(['cantidad' => 1, 'producto' => 'HAVOLINE', 'valor_sin_iva' => $canastilla, 'iva' => 0, 'total' => $canastilla]);
    }

    return $turno;
}

test('el informe suma las ventas de cada islero y calcula su comision con los porcentajes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    planillaComision('Rosa Rivera', 1, 1000000, 500000, 100000);
    planillaComision('ROSA RIVERA', 2, 2000000, 0, 0);
    planillaComision('Armando Villaraga', 3, 400000, 600000, 50000);
    // Anulada: no cuenta.
    planillaComision('Armando Villaraga', 4, 9999999, 0, 0, anulada: true);

    $this->actingAs($admin)->post(route('informes.comisiones.porcentajes'), ['combustible' => '0,5', 'canastilla' => '10'])
        ->assertRedirect(route('informes.comisiones.index'));

    $informe = $this->actingAs($admin)->get(route('informes.comisiones.index', ['desde' => '2026-11-01', 'hasta' => '2026-11-30']))
        ->assertOk()
        ->assertSee('COMBUSTIBLE (0,5%)');

    expect($informe->viewData('filas'))->toBe([
        ['islero' => 'ARMANDO VILLARAGA', 'planillas' => 1, 'galones' => 100.0, 'combustible' => 1000000.0, 'comision_combustible' => 5000.0, 'canastilla' => 50000.0, 'comision_canastilla' => 5000.0, 'comision_total' => 10000.0],
        ['islero' => 'ROSA RIVERA', 'planillas' => 2, 'galones' => 350.0, 'combustible' => 3500000.0, 'comision_combustible' => 17500.0, 'canastilla' => 100000.0, 'comision_canastilla' => 10000.0, 'comision_total' => 27500.0],
    ])
        ->and($informe->viewData('totales')['comision_total'])->toBe(37500.0);

    $this->actingAs($admin)->get(route('informes.comisiones.exportar', ['desde' => '2026-11-01', 'hasta' => '2026-11-30']))
        ->assertDownload('comisiones-isleros-2026-11-01-a-2026-11-30.xlsx');
});

test('los porcentajes van de 0 a 100 y solo administracion entra al informe', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);

    $this->actingAs($admin)->post(route('informes.comisiones.porcentajes'), ['combustible' => '150', 'canastilla' => '5'])
        ->assertSessionHasErrors('porcentajes');
    $this->actingAs($jefe)->get(route('informes.comisiones.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('turnos.pendientes'))->assertSee(route('informes.comisiones.index'));
});
