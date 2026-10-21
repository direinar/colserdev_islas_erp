<?php

use App\Models\Turno;
use App\Models\User;
use App\Services\ManguerasTurnoService;

function abrir(User $user, array $mangueras, array $datos = [])
{
    return test()->actingAs($user)->post(route('turnos.abrir'), $datos + [
        'fecha' => now()->toDateString(),
        'turno_dia' => (int) Turno::query()->max('turno_dia') + 1,
        'hora_inicio' => '06:00',
        'mangueras' => $mangueras,
    ]);
}

function lecturas(Turno $turno, array $finales): array
{
    return $turno->surtidores()->get()->map(fn ($s) => [
        'manguera' => $s->manguera,
        'combustible' => $s->combustible,
        'lectura_inicial' => (string) $s->lectura_inicial,
        'lectura_final' => $finales[$s->manguera] ?? '',
    ])->all();
}

function guardarLecturas(User $user, Turno $turno, array $finales, bool $cerrar = false)
{
    return test()->actingAs($user)->post(route('turnos.store'), [
        'fecha' => $turno->fecha->toDateString(),
        'numero_turno' => $turno->numero_turno,
        'lecturas' => lecturas($turno, $finales),
    ] + ($cerrar ? ['cerrar' => '1'] : []));
}

test('una manguera solo puede estar en una planilla abierta; los otros isleros toman otras', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO, 'name' => 'Ana']);
    $luis = User::factory()->create(['role' => User::ROLE_ISLERO, 'name' => 'Luis']);

    abrir($ana, ['PLUS 09', 'PLUS 10'])->assertSessionHasNoErrors();

    abrir($luis, ['PLUS 10', 'PLUS 01'])
        ->assertSessionHasErrors(['mangueras' => 'PLUS 10 está en el turno #1 de Ana, que no se ha cerrado.']);

    abrir($luis, ['PLUS 01', 'PLUS 02'])->assertSessionHasNoErrors();

    expect(Turno::query()->orderBy('numero_turno')->get()->map(fn (Turno $t) => [$t->user_id, $t->surtidores->pluck('manguera')->all()])->all())
        ->toBe([[$ana->id, ['PLUS 09', 'PLUS 10']], [$luis->id, ['PLUS 01', 'PLUS 02']]]);

    // En ABRIR TURNO la manguera ocupada aparece bloqueada con quién la tiene.
    $pantalla = $this->actingAs($luis)->get(route('turnos.create'))->assertSee('Ocupada: turno #1 de');

    expect($pantalla->getContent())->toMatch('/value="PLUS 09"\s+disabled/')
        ->not->toMatch('/value="PLUS 03"\s+disabled/');
});

test('la lectura inicial es la última lectura cerrada, nunca la de un turno sin cerrar', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $luis = User::factory()->create(['role' => User::ROLE_ISLERO]);

    abrir($ana, ['PLUS 09']);
    $turnoAna = Turno::query()->sole();
    guardarLecturas($ana, $turnoAna, ['PLUS 09' => '1500'], cerrar: true)->assertSessionHasNoErrors();

    abrir($luis, ['PLUS 09']);
    $turnoLuis = Turno::query()->where('numero_turno', 2)->sole();
    // Luis digita lecturas finales pero no cierra: el siguiente turno no las toma.
    guardarLecturas($luis, $turnoLuis, ['PLUS 09' => '1800'])->assertSessionHasNoErrors();

    expect((float) $turnoLuis->surtidores()->sole()->lectura_inicial)->toBe(1500.0)
        ->and(app(ManguerasTurnoService::class)->ultimasLecturas()['PLUS 09'])->toBe(1500.0);

    // Al cerrar Luis, el turno siguiente arranca en 1.800.
    guardarLecturas($luis, $turnoLuis, ['PLUS 09' => '1800'], cerrar: true)->assertSessionHasNoErrors();
    abrir($ana, ['PLUS 09']);

    expect((float) Turno::query()->where('numero_turno', 3)->sole()->surtidores()->sole()->lectura_inicial)->toBe(1800.0);
});

test('guardar no acepta la lectura inicial del navegador ni mangueras que no son del turno', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO]);
    abrir($ana, ['PLUS 09']);
    guardarLecturas($ana, Turno::query()->sole(), ['PLUS 09' => '1500'], cerrar: true);

    abrir($ana, ['PLUS 09']);
    $turno = Turno::query()->where('numero_turno', 2)->sole();

    $this->actingAs($ana)->post(route('turnos.store'), [
        'fecha' => $turno->fecha->toDateString(),
        'numero_turno' => 2,
        'lecturas' => [
            ['manguera' => 'PLUS 09', 'combustible' => 'corriente', 'lectura_inicial' => '0', 'lectura_final' => '1600'],
            ['manguera' => 'PLUS 01', 'combustible' => 'corriente', 'lectura_inicial' => '0', 'lectura_final' => '700'],
        ],
    ])->assertSessionHasNoErrors();

    $surtidor = $turno->surtidores()->sole();

    expect($surtidor->manguera)->toBe('PLUS 09')
        ->and((float) $surtidor->lectura_inicial)->toBe(1500.0)
        ->and((float) $surtidor->galones)->toBe(100.0);
});

test('CERRAR TURNO exige la lectura final de cada manguera, no menor que la inicial', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO, 'name' => 'Ana']);
    abrir($ana, ['PLUS 09', 'PLUS 10']);
    $turno = Turno::query()->sole();

    guardarLecturas($ana, $turno, ['PLUS 09' => '120'], cerrar: true)
        ->assertSessionHas('success', 'Turno guardado, pero NO se cerró.')
        ->assertSessionHasErrors(['cerrar' => 'Falta la lectura final de PLUS 10.']);
    // Lo digitado se guarda aunque no se cierre.
    expect($turno->fresh()->estaAbierta())->toBeTrue()
        ->and((float) $turno->surtidores()->where('manguera', 'PLUS 09')->value('lectura_final'))->toBe(120.0);

    // Una lectura final en cero queda por debajo de una inicial mayor (la de un turno anterior).
    $turno->surtidores()->where('manguera', 'PLUS 10')->update(['lectura_inicial' => 50]);
    guardarLecturas($ana, $turno, ['PLUS 09' => '120', 'PLUS 10' => '0'], cerrar: true)
        ->assertSessionHasErrors(['cerrar' => 'La lectura final de PLUS 10 es menor que la inicial.']);

    guardarLecturas($ana, $turno, ['PLUS 09' => '120', 'PLUS 10' => '80'], cerrar: true)->assertSessionHasNoErrors();

    $turno->refresh();
    expect($turno->estaAbierta())->toBeFalse()
        ->and($turno->cerrado_por)->toBe('Ana')
        ->and($turno->cerrado_at)->not->toBeNull()
        ->and((float) $turno->lecturas_galones_corriente)->toBe(150.0);

    // Las mangueras quedaron libres.
    abrir($ana, ['PLUS 09', 'PLUS 10'])->assertSessionHasNoErrors();
});

test('el jefe de patios cierra la planilla de un islero que no la cerró; otro islero no puede', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $luis = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS, 'name' => 'Jefe']);
    abrir($ana, ['PLUS 09']);
    $turno = Turno::query()->sole();

    $this->actingAs($jefe)->get(route('turnos.create', ['turno_busqueda' => 1]))
        ->assertSee('CERRAR TURNO DE')
        ->assertSee('name="finales[PLUS 09]"', false);

    $this->actingAs($luis)->post(route('turnos.cerrar', $turno), ['finales' => ['PLUS 09' => '10']])->assertForbidden();

    $this->actingAs($jefe)->post(route('turnos.cerrar', $turno), ['finales' => ['PLUS 09' => '1.250,5']])
        ->assertSessionHasNoErrors();

    $turno->refresh();
    expect($turno->estaAbierta())->toBeFalse()
        ->and($turno->cerrado_por)->toBe('Jefe')
        ->and((float) $turno->surtidores()->sole()->galones)->toBe(1250.5)
        ->and($turno->sobrante_pendiente)->not->toBeNull();
});

test('una lectura final que ya tomó el turno siguiente no se cambia desde la planilla cerrada', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO]);
    abrir($ana, ['PLUS 09']);
    $primero = Turno::query()->sole();
    guardarLecturas($ana, $primero, ['PLUS 09' => '1500'], cerrar: true);

    // Antes de que otro turno la tome, la planilla cerrada aún se corrige.
    guardarLecturas($ana, $primero, ['PLUS 09' => '1550'])->assertSessionHasNoErrors();
    abrir($ana, ['PLUS 09']);

    guardarLecturas($ana, $primero, ['PLUS 09' => '1600'])
        ->assertSessionHasErrors(['lecturas' => 'La lectura final de PLUS 09 ya es la inicial del turno #2: no se puede cambiar desde esta planilla.']);

    expect((float) $primero->surtidores()->sole()->lectura_final)->toBe(1550.0);
});

test('el islero abre para hoy o para mañana (turno de la noche) y el turno del día no se repite', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO]);

    abrir($ana, ['PLUS 01'], ['fecha' => now()->subDay()->toDateString()])->assertSessionHasErrors('fecha');
    abrir($ana, ['PLUS 01'], ['fecha' => now()->addDay()->toDateString(), 'turno_dia' => 1])->assertSessionHasNoErrors();
    abrir($ana, ['PLUS 02'], ['fecha' => now()->addDay()->toDateString(), 'turno_dia' => 1])->assertSessionHasErrors('turno_dia');

    expect(Turno::query()->sole()->fecha->toDateString())->toBe(now()->addDay()->toDateString());
});

test('el administrador abre turnos de cualquier fecha y, sin turno del día, se toma el siguiente de esa fecha', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    abrir($admin, ['PLUS 01'], ['fecha' => '2026-10-06', 'turno_dia' => ''])->assertSessionHasNoErrors();
    abrir($admin, ['PLUS 02'], ['fecha' => '2026-10-06', 'turno_dia' => ''])->assertSessionHasNoErrors();

    expect(Turno::query()->orderBy('numero_turno')->pluck('turno_dia')->all())->toBe([1, 2]);
});

test('guardar no crea planillas: primero se abre el turno, y revisar exige que esté cerrada', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('turnos.store'), ['fecha' => '2026-09-25', 'numero_turno' => 1])
        ->assertSessionHasErrors(['numero_turno' => 'El turno #1 no existe. Primero use ABRIR TURNO con sus mangueras.']);
    expect(Turno::count())->toBe(0);

    abrir($admin, ['PLUS 01']);
    $this->actingAs($admin)->post(route('turnos.revisar', Turno::query()->sole()))
        ->assertSessionHasErrors(['revisar' => 'Turno #1: la planilla sigue ABIERTA. Debe cerrarse con las lecturas finales antes de revisarla.']);
});
