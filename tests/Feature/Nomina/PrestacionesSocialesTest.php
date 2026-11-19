<?php

use App\Models\Nomina\Dotacion;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\LiquidacionContrato;
use App\Models\Nomina\LiquidacionNomina;
use App\Models\Nomina\ParametroLiquidacion;
use App\Models\User;
use App\Services\Nomina\NominaLiquidacionService;
use App\Services\Nomina\PrestacionesSocialesService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    ParametroLiquidacion::factory()->create(['vigente_desde' => '2025-01-01']);
});

/**
 * Nómina aprobada del mes con los empleados vinculados (30 días, sin novedades).
 */
function nominaAprobada(int $anio, int $mes): LiquidacionNomina
{
    $liquidacion = app(NominaLiquidacionService::class)->crear($anio, $mes);
    $liquidacion->update(['estado' => LiquidacionNomina::ESTADO_APROBADA]);

    return $liquidacion;
}

test('cuenta los dias como DAYS360 de la plantilla y liquida el contrato al retiro', function () {
    $servicio = app(PrestacionesSocialesService::class);
    // Ejemplo de la hoja LIQUIDADOR PREST. SOC.: ingreso 01/12/2025, retiro 22/08/2026.
    expect($servicio->dias360(CarbonImmutable::parse('2025-12-01'), CarbonImmutable::parse('2026-08-22')))->toBe(262)
        ->and($servicio->dias360(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-08-22')))->toBe(232)
        ->and($servicio->dias360(CarbonImmutable::parse('2026-07-01'), CarbonImmutable::parse('2026-08-22')))->toBe(52)
        ->and($servicio->dias360(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-12-31')))->toBe(360);

    // Sin nóminas aprobadas la base es sueldo + auxilio: 1.750.905 + 249.095 = 2.000.000.
    $empleado = Empleado::factory()->create(['nombre_corto' => 'MICHAEL', 'fecha_ingreso' => '2025-12-01']);

    $this->actingAs($this->admin)->post(route('nomina.liquidaciones-contrato.store'), [
        'empleado_id' => $empleado->id,
        'fecha_retiro' => '2026-08-22',
        'motivo_retiro' => 'Finalización de contrato a término fijo',
        'sueldo_pendiente' => 1228400,
        'auxilio_transporte' => 146600,
        'otras_deducciones' => 29550,
        'registrar_retiro' => 1,
    ])->assertSessionHasNoErrors();

    $liquidacion = LiquidacionContrato::sole();
    expect($liquidacion->only(['salario_base', 'dias_anio', 'dias_semestre', 'dias_contrato', 'cesantias', 'intereses_cesantias', 'prima', 'vacaciones', 'salud', 'pension']))->toBe([
        'salario_base' => 2000000.0, 'dias_anio' => 232, 'dias_semestre' => 52, 'dias_contrato' => 262,
        'cesantias' => 1288889.0, 'intereses_cesantias' => 99674.0, 'prima' => 288889.0, 'vacaciones' => 644444.0,
        'salud' => 49136.0, 'pension' => 49136.0,
    ])
        ->and($liquidacion->neto_pagar)->toBe(1228400 + 146600 + 1288889 + 99674 + 288889 + 644444 - 49136 - 49136 - 29550.0)
        ->and($empleado->fresh())->activo->toBeFalse()->fecha_retiro->toDateString()->toBe('2026-08-22');

    $this->actingAs($this->admin)->get(route('nomina.liquidaciones-contrato.show', $liquidacion))
        ->assertOk()->assertSee('LIQUIDACIÓN CONTRATO DE TRABAJO')->assertSee('$ 1.288.889');
    $this->actingAs($this->admin)->get(route('nomina.liquidaciones-contrato.show', [$liquidacion, 'pdf' => 1]))->assertOk()->assertDownload();

    // La fecha de retiro no puede ser anterior al ingreso.
    $this->actingAs($this->admin)->post(route('nomina.liquidaciones-contrato.store'), [
        'empleado_id' => $empleado->id, 'fecha_retiro' => '2025-01-01', 'motivo_retiro' => 'Renuncia voluntaria',
    ])->assertSessionHasErrors('fecha_retiro');
});

test('acumula las nominas aprobadas, calcula la prima del semestre y la lleva a la nomina de diciembre', function () {
    $rosa = Empleado::factory()->create(['nombre_corto' => 'ROSA', 'fecha_ingreso' => '2024-01-01']);
    // Ingresó el 16 de octubre: 75 días del segundo semestre.
    $nuevo = Empleado::factory()->create(['nombre_corto' => 'NUEVO', 'fecha_ingreso' => '2026-10-16', 'recibe_auxilio_transporte' => false]);

    nominaAprobada(2026, 7);
    $agosto = nominaAprobada(2026, 8);
    // Una nómina en borrador no cuenta.
    app(NominaLiquidacionService::class)->crear(2026, 9);
    $agosto->empleados()->where('empleado_id', $rosa->id)->update(['prima' => 100000]);

    $acumulados = collect(app(PrestacionesSocialesService::class)->acumulados(2026))->keyBy(fn ($fila) => $fila['empleado']->nombre_corto);
    expect($acumulados['ROSA'])->toMatchArray(['meses' => 2, 'sueldo' => 3501810.0, 'auxilio' => 498190.0, 'total_salarios' => 4000000.0, 'prima' => 100000.0])
        ->and($acumulados->has('NUEVO'))->toBeFalse();

    $prima = collect(app(PrestacionesSocialesService::class)->primaSemestral(2026, 2))->keyBy(fn ($fila) => $fila['empleado']->nombre_corto);
    // ROSA: promedio 2.000.000 × 180 días / 360. NUEVO: sin historial, sueldo sin auxilio × 75 / 360.
    expect($prima['ROSA'])->toMatchArray(['dias' => 180, 'promedio' => 2000000.0, 'prima' => 1000000.0, 'sin_historial' => false, 'ya_pagada' => 100000.0])
        ->and($prima['NUEVO'])->toMatchArray(['dias' => 75, 'prima' => round(1750905 * 75 / 360), 'sin_historial' => true]);

    $this->actingAs($this->admin)->get(route('nomina.prestaciones.prima', ['anio' => 2026, 'semestre' => 2]))
        ->assertOk()->assertSee('Prima semestral · Segundo semestre 2026')->assertSee('1.000.000');

    $diciembre = app(NominaLiquidacionService::class)->crear(2026, 12);
    $this->actingAs($this->admin)->post(route('nomina.prestaciones.aplicar'), [
        'concepto' => 'prima', 'liquidacion_id' => $diciembre->id, 'valores' => [$rosa->id => 1000000, $nuevo->id => 364772],
    ])->assertRedirect(route('nomina.nominas.show', $diciembre));

    $filaRosa = $diciembre->empleados()->where('empleado_id', $rosa->id)->sole();
    expect($filaRosa->prima)->toBe(1000000.0)
        ->and($filaRosa->total_devengado)->toBe(2000000.0 + 1000000.0);

    // A una nómina aprobada no se lleva.
    $this->actingAs($this->admin)->post(route('nomina.prestaciones.aplicar'), [
        'concepto' => 'prima', 'liquidacion_id' => $agosto->id, 'valores' => [$rosa->id => 1],
    ])->assertSessionHasErrors('liquidacion_id');

    // Certificado de ingresos del año.
    $this->actingAs($this->admin)->get(route('nomina.acumulados.certificado', [$rosa, 'anio' => 2026]))
        ->assertOk()->assertSee('CERTIFICADO DE INGRESOS LABORALES AÑO GRAVABLE 2026')->assertSee('$ 4.100.000');
    $this->actingAs($this->admin)->get(route('nomina.acumulados.certificado', [$nuevo, 'anio' => 2026]))->assertNotFound();
    $this->actingAs($this->admin)->get(route('nomina.acumulados.index', ['anio' => 2026]))->assertOk()->assertSee('TOTAL INGRESOS LABORALES');
});

test('consolida cesantias, intereses y vacaciones del año', function () {
    Empleado::factory()->create(['nombre_corto' => 'ROSA', 'fecha_ingreso' => '2024-01-01']);
    nominaAprobada(2026, 1);

    $fila = app(PrestacionesSocialesService::class)->consolidacion(2026)[0];
    // Hoja CONSOLIDACION PRESTAC: cesantías = promedio × días / 360; intereses 12 %; vacaciones = promedio × días / 720.
    expect($fila)->toMatchArray(['dias' => 360, 'promedio' => 2000000.0, 'cesantias' => 2000000.0, 'intereses' => 240000.0, 'vacaciones' => 1000000.0]);

    $this->actingAs($this->admin)->get(route('nomina.prestaciones.consolidacion', ['anio' => 2026]))
        ->assertOk()->assertSee('Cesantías a consignar al fondo')->assertSee('240.000');
});

test('controla vacaciones, dotacion y emite la certificacion laboral', function () {
    $rosa = Empleado::factory()->create(['nombre_corto' => 'ROSA', 'nombres' => 'Rosa', 'apellidos' => 'Rivera', 'fecha_ingreso' => '2024-01-01', 'cargo' => 'OPERARIO DE ISLAS']);

    $control = app(PrestacionesSocialesService::class)->controlVacaciones($rosa, CarbonImmutable::parse('2025-12-30'));
    expect($control)->toMatchArray(['dias_laborados' => 720, 'periodos' => 2, 'causados' => 30.0, 'tomados' => 0, 'semaforo' => 'rojo']);

    $this->actingAs($this->admin)->post(route('nomina.vacaciones.store', $rosa), ['fecha_inicio' => '2025-06-01', 'dias_disfrutados' => 15, 'periodo' => '2024-2025'])
        ->assertRedirect(route('nomina.vacaciones.show', $rosa));
    $this->actingAs($this->admin)->post(route('nomina.vacaciones.store', $rosa), ['fecha_inicio' => '2025-07-01'])->assertSessionHasErrors('dias_disfrutados');
    expect(app(PrestacionesSocialesService::class)->controlVacaciones($rosa->fresh(), CarbonImmutable::parse('2025-12-30')))
        ->toMatchArray(['tomados' => 15, 'saldo' => 15.0, 'semaforo' => 'amarillo']);
    $this->actingAs($this->admin)->get(route('nomina.vacaciones.index'))->assertOk()->assertSee('Rosa Rivera');

    $this->actingAs($this->admin)->post(route('nomina.dotaciones.store'), [
        'empleado_id' => $rosa->id, 'anio' => 2026, 'entrega' => 1, 'fecha_entrega' => '2026-04-20', 'elementos' => '2 camisas y 1 pantalón',
    ])->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->post(route('nomina.dotaciones.store'), [
        'empleado_id' => $rosa->id, 'anio' => 2026, 'entrega' => 1, 'fecha_entrega' => '2026-04-21', 'elementos' => 'Botas',
    ])->assertSessionHasErrors('entrega');
    expect(Dotacion::count())->toBe(1);
    $this->actingAs($this->admin)->get(route('nomina.dotaciones.index', ['anio' => 2026]))->assertOk()->assertSee('Entregada 20/04/2026');

    $this->actingAs($this->admin)->get(route('nomina.empleados.certificacion', [$rosa, 'dirigido_a' => 'Banco Agrario']))
        ->assertOk()->assertSee('BANCO AGRARIO')->assertSee('OPERARIO DE ISLAS')->assertSee('$1.750.905');
    $this->actingAs($this->admin)->get(route('nomina.empleados.certificacion', [$rosa, 'incluir_salario' => 0]))
        ->assertOk()->assertDontSee('$1.750.905');
    $this->actingAs($this->admin)->get(route('nomina.empleados.certificacion', [$rosa, 'pdf' => 1]))->assertOk()->assertDownload();
});
