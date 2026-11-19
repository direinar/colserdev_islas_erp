<?php

use App\Models\Nomina\ParametroLiquidacion;
use App\Models\Nomina\Turno;
use App\Models\User;
use App\Services\Nomina\CalculoHorasTurnoService;
use Carbon\CarbonImmutable;

test('crea un turno y el catalogo muestra sus horas nocturnas', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    ParametroLiquidacion::factory()->create();

    $this->actingAs($admin)->post(route('nomina.turnos.store'), [
        'nombre' => '2pm-10pm',
        'hora_inicio' => '14:00',
        'hora_fin' => '22:00',
        'color' => '#d1e7dd',
        'activo' => '1',
    ])->assertSessionHasNoErrors()->assertRedirect(route('nomina.turnos.index'));

    expect(Turno::sole()->nombre)->toBe('2PM-10PM');

    $this->actingAs($admin)->get(route('nomina.turnos.index'))
        ->assertOk()
        ->assertSeeInOrder(['2PM-10PM', '14:00 - 22:00', '8', '3']);
});

test('calcula horas trabajadas, nocturnas y festivas segun el dia del turno', function (string $inicio, string $fin, string $fecha, array $festivos, array $esperado) {
    $parametros = ParametroLiquidacion::factory()->make();
    $turno = Turno::factory()->make(['hora_inicio' => $inicio, 'hora_fin' => $fin]);

    $horas = app(CalculoHorasTurnoService::class)->calcular($turno, CarbonImmutable::parse($fecha), $parametros, $festivos);

    expect($horas)->toBe($esperado);
})->with([
    'tarde entre semana' => ['14:00', '22:00', '2026-11-11', [], ['horas_trabajadas' => 8.0, 'horas_nocturnas' => 3.0, 'horas_festivas' => 0.0]],
    'mediodia entre semana' => ['12:00', '20:00', '2026-11-11', [], ['horas_trabajadas' => 8.0, 'horas_nocturnas' => 1.0, 'horas_festivas' => 0.0]],
    'noche que inicia el domingo' => ['22:00', '06:00', '2026-11-08', [], ['horas_trabajadas' => 8.0, 'horas_nocturnas' => 8.0, 'horas_festivas' => 2.0]],
    'noche del sabado que termina el domingo antes de las 6' => ['22:00', '05:00', '2026-11-07', [], ['horas_trabajadas' => 7.0, 'horas_nocturnas' => 7.0, 'horas_festivas' => 0.0]],
    'madrugada del domingo' => ['05:00', '13:00', '2026-11-08', [], ['horas_trabajadas' => 8.0, 'horas_nocturnas' => 1.0, 'horas_festivas' => 7.0]],
    'lunes festivo' => ['06:00', '14:00', '2026-11-16', ['2026-11-16'], ['horas_trabajadas' => 8.0, 'horas_nocturnas' => 0.0, 'horas_festivas' => 8.0]],
]);

test('muestra el formulario de edicion del turno', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $turno = Turno::factory()->create(['nombre' => '10PM-6AM', 'hora_inicio' => '22:00', 'hora_fin' => '06:00']);

    $this->actingAs($admin)->get(route('nomina.turnos.edit', $turno))->assertOk()->assertSee('value="22:00"', false);
    $this->actingAs($admin)->get(route('nomina.turnos.index'))->assertOk()->assertSee('termina al día siguiente');
});
