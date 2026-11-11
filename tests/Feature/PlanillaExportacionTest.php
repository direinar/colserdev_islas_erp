<?php

use App\Models\FuelPrice;
use App\Models\Turno;
use App\Models\User;
use App\Services\PlanillaTurnoExportService;

beforeEach(function () {
    FuelPrice::create(['name' => 'Gasolina', 'price' => 16395, 'turno_desde' => 1, 'active' => true]);
    FuelPrice::create(['name' => 'ACPM', 'price' => 11725, 'turno_desde' => 1, 'active' => true]);
});

test('la planilla guardada se exporta a PDF y a Excel con sus secciones y el cuadre', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => '2026-11-08',
        'numero_turno' => 21,
        'ventas' => [
            ['surtidor' => 'SURTIDOR 1 CTE', 'combustible' => 'CTE', 'no_cierre' => '5', 'galones' => '10'],
            ['surtidor' => 'SURTIDOR 3 ACPM', 'combustible' => 'ACPM', 'no_cierre' => '6', 'galones' => '2'],
        ],
        'consignaciones' => [['consignacion_no' => '4001', 'consignacion_valor' => '150.000']],
        'varios' => [['concepto' => 'PEAJE', 'valor' => '20.000']],
    ])->assertSessionHasNoErrors();
    $turno = Turno::query()->sole();

    // Botones en la planilla guardada; una planilla nueva no los tiene.
    $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 21]))
        ->assertSee(route('turnos.pdf', $turno))
        ->assertSee(route('turnos.excel', $turno));
    $this->actingAs($admin)->get(route('turnos.create'))->assertDontSee('id="planilla-pdf"', false);

    $secciones = collect(app(PlanillaTurnoExportService::class)->secciones($turno))->keyBy('titulo');

    expect($secciones->keys()->all())->toBe([
        'VENTAS SEGÚN CIERRES DE IAPPROPIADA', 'TOTAL VENTAS SEGÚN CORTE', 'CONSIGNACIONES', 'VARIOS', 'RESUMEN DE LO RECIBIDO EN ESTE TURNO',
    ])
        ->and($secciones['VENTAS SEGÚN CIERRES DE IAPPROPIADA']['total'])->toBe(['TOTAL', '', 12.0, 187400.0])
        ->and($secciones['VARIOS']['filas'])->toBe([['PEAJE', 20000.0]])
        // 150.000 + 20.000 recibidos − 187.400 vendidos.
        ->and(collect($secciones['RESUMEN DE LO RECIBIDO EN ESTE TURNO']['filas'])->last())->toBe(['SOBRANTE O FALTANTE SEGÚN CIERRES DE IAPPROPIADA', -17400.0]);

    $pdf = $this->actingAs($admin)->get(route('turnos.pdf', $turno))
        ->assertOk()
        ->assertDownload('planilla-turno-21.pdf');
    expect(substr((string) $pdf->getContent(), 0, 5))->toBe('%PDF-');

    $this->actingAs($admin)->get(route('turnos.excel', $turno))->assertDownload('planilla-turno-21.xlsx');

    $hoja = app(PlanillaTurnoExportService::class)->excel($turno)->getActiveSheet();
    $celdas = collect($hoja->toArray(null, false, false))->flatten()->filter(fn ($valor) => $valor !== null && $valor !== '')->values();
    expect($celdas)->toContain('PLANILLA DE TURNO 021', 'CONSIGNACIONES', '4001', 150000.0, 'PEAJE', -17400.0);
});

test('quien no inicia sesion no descarga la planilla', function () {
    $turno = Turno::create(['fecha' => '2026-11-08', 'numero_turno' => 22]);

    $this->get(route('turnos.pdf', $turno))->assertRedirect(route('login'));
    $this->get(route('turnos.excel', $turno))->assertRedirect(route('login'));
});
