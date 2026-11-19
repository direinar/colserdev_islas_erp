<?php

use App\Models\Nomina\Empleado;
use App\Models\Nomina\Festivo;
use App\Models\Nomina\ParametroLiquidacion;
use App\Models\Nomina\PlanillaTurno;
use App\Models\Nomina\Turno;
use App\Models\User;
use App\Services\Nomina\PlanillaTurnosService;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    ParametroLiquidacion::factory()->create();
    $this->noche = Turno::factory()->create(['nombre' => '10PM-6AM', 'hora_inicio' => '22:00', 'hora_fin' => '06:00']);
    $this->manana = Turno::factory()->create(['nombre' => '6AM-2PM', 'hora_inicio' => '06:00', 'hora_fin' => '14:00']);
});

test('crea la planilla del mes una sola vez', function () {
    $this->actingAs($this->admin)->post(route('nomina.planillas-turnos.store'), ['anio' => 2026, 'mes' => 11])
        ->assertRedirect(route('nomina.planillas-turnos.edit', PlanillaTurno::sole()));

    $this->actingAs($this->admin)->post(route('nomina.planillas-turnos.store'), ['anio' => 2026, 'mes' => 11])
        ->assertSessionHasErrors('mes');
});

test('las semanas van de domingo a sabado y cubren todo el mes', function () {
    $semanas = app(PlanillaTurnosService::class)->semanas(PlanillaTurno::factory()->make(['anio' => 2026, 'mes' => 12]));

    expect($semanas)->toHaveCount(5)
        ->and($semanas[0][0]->toDateString())->toBe('2026-11-29')
        ->and($semanas[0][0]->isSunday())->toBeTrue()
        ->and($semanas[4][6]->toDateString())->toBe('2027-01-02');
});

test('incluye solo a los empleados con recargos vinculados en el mes', function () {
    $planilla = PlanillaTurno::factory()->create(['anio' => 2026, 'mes' => 11]);
    $islero = Empleado::factory()->create(['nombre_corto' => 'ROSA']);
    $retiradoEnElMes = Empleado::factory()->create(['nombre_corto' => 'MARIA', 'activo' => false, 'fecha_retiro' => '2026-11-10']);
    Empleado::factory()->create(['nombre_corto' => 'ELKIN', 'aplica_recargos' => false]);
    Empleado::factory()->create(['nombre_corto' => 'NUEVO', 'fecha_ingreso' => '2026-12-01']);
    Empleado::factory()->create(['nombre_corto' => 'ANTIGUO', 'activo' => false, 'fecha_retiro' => '2026-10-31']);

    expect(app(PlanillaTurnosService::class)->empleados($planilla)->pluck('id')->all())
        ->toEqualCanonicalizing([$islero->id, $retiradoEnElMes->id]);
});

test('guarda los turnos y liquida recargos nocturnos, festivos y horas por semana', function () {
    Festivo::create(['fecha' => '2026-11-16', 'nombre' => 'Independencia de Cartagena']);
    $planilla = PlanillaTurno::factory()->create(['anio' => 2026, 'mes' => 11]);
    $rosa = Empleado::factory()->create(['nombre_corto' => 'ROSA']);

    $this->actingAs($this->admin)->put(route('nomina.planillas-turnos.update', $planilla), [
        'turnos' => [$rosa->id => [
            '2026-11-08' => $this->noche->id,
            '2026-11-09' => $this->manana->id,
            '2026-11-10' => '',
            '2026-11-16' => $this->manana->id,
        ]],
    ])->assertSessionHasNoErrors()->assertRedirect(route('nomina.planillas-turnos.edit', $planilla));

    $fila = app(PlanillaTurnosService::class)->resumen($planilla)[$rosa->id];

    expect($planilla->asignaciones()->count())->toBe(3)
        ->and($fila['horas_trabajadas'])->toBe(24.0)
        ->and($fila['horas_nocturnas'])->toBe(8.0)
        ->and($fila['horas_festivas'])->toBe(10.0)
        ->and($fila['semanas'])->toBe([0.0, 16.0, 8.0, 0.0, 0.0])
        ->and(round($fila['valor_nocturnas']))->toBe(22284.0)
        ->and(round($fila['valor_festivas']))->toBe(63669.0);

    $this->actingAs($this->admin)->get(route('nomina.planillas-turnos.liquidacion', $planilla))
        ->assertOk()
        ->assertSee('$ 22.284')
        ->assertSee('$ 63.669');
});

test('rechaza dias fuera del mes y empleados que no estan en la planilla', function () {
    $planilla = PlanillaTurno::factory()->create(['anio' => 2026, 'mes' => 11]);
    $rosa = Empleado::factory()->create();
    $administrativo = Empleado::factory()->create(['aplica_recargos' => false]);

    $this->actingAs($this->admin)->put(route('nomina.planillas-turnos.update', $planilla), [
        'turnos' => [$rosa->id => ['2026-12-01' => $this->manana->id]],
    ])->assertSessionHasErrors('turnos');

    $this->actingAs($this->admin)->put(route('nomina.planillas-turnos.update', $planilla), [
        'turnos' => [$administrativo->id => ['2026-11-02' => $this->manana->id]],
    ])->assertSessionHasErrors('turnos');

    expect($planilla->asignaciones()->count())->toBe(0);
});

test('cerrar recalcula las horas y bloquea la edicion hasta reabrir', function () {
    $planilla = PlanillaTurno::factory()->create(['anio' => 2026, 'mes' => 11]);
    $rosa = Empleado::factory()->create();

    $this->actingAs($this->admin)->put(route('nomina.planillas-turnos.update', $planilla), [
        'turnos' => [$rosa->id => ['2026-11-16' => $this->manana->id]],
    ]);
    expect($planilla->asignaciones()->sole()->horas_festivas)->toBe(0.0);

    // El festivo se registró después de programar: al cerrar se tiene en cuenta.
    Festivo::create(['fecha' => '2026-11-16', 'nombre' => 'Independencia de Cartagena']);

    $this->actingAs($this->admin)->patch(route('nomina.planillas-turnos.cerrar', $planilla))
        ->assertRedirect(route('nomina.planillas-turnos.liquidacion', $planilla));

    expect($planilla->fresh()->estaCerrada())->toBeTrue()
        ->and($planilla->asignaciones()->sole()->horas_festivas)->toBe(8.0);

    $this->actingAs($this->admin)->get(route('nomina.planillas-turnos.edit', $planilla))->assertRedirect(route('nomina.planillas-turnos.show', $planilla));
    $this->actingAs($this->admin)->put(route('nomina.planillas-turnos.update', $planilla), ['turnos' => []])->assertSessionHas('error');
    expect($planilla->asignaciones()->count())->toBe(1);

    $this->actingAs($this->admin)->patch(route('nomina.planillas-turnos.reabrir', $planilla));
    expect($planilla->fresh()->estaCerrada())->toBeFalse();
});

test('muestra la programacion y la planilla de turnos para imprimir', function () {
    $planilla = PlanillaTurno::factory()->create(['anio' => 2026, 'mes' => 11]);
    $rosa = Empleado::factory()->create(['nombre_corto' => 'ROSA']);
    $planilla->asignaciones()->create(['empleado_id' => $rosa->id, 'turno_id' => $this->noche->id, 'fecha' => '2026-11-03']);

    $this->actingAs($this->admin)->get(route('nomina.planillas-turnos.index'))->assertOk()->assertSee('NOVIEMBRE 2026');
    $this->actingAs($this->admin)->get(route('nomina.planillas-turnos.edit', $planilla))
        ->assertOk()
        ->assertSee('name="turnos['.$rosa->id.'][2026-11-03]"', false)
        ->assertSee('Copiar semana anterior');
    $this->actingAs($this->admin)->get(route('nomina.planillas-turnos.show', $planilla))
        ->assertOk()
        ->assertSeeInOrder(['ROSA', 'Descanso', '10PM-6AM']);
});

test('no se elimina un empleado ni un turno que ya tienen turnos en planillas', function () {
    $planilla = PlanillaTurno::factory()->create();
    $rosa = Empleado::factory()->create();
    $planilla->asignaciones()->create(['empleado_id' => $rosa->id, 'turno_id' => $this->noche->id, 'fecha' => '2026-11-03']);

    $this->actingAs($this->admin)->delete(route('nomina.empleados.destroy', $rosa))->assertSessionHas('error');
    $this->actingAs($this->admin)->delete(route('nomina.turnos.destroy', $this->noche))->assertSessionHas('error');

    expect($rosa->fresh())->not->toBeNull()
        ->and($this->noche->fresh())->not->toBeNull();
});
