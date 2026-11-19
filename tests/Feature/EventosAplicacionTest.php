<?php

use App\Models\EventoAplicacion;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Support\Facades\Route;

function fallaDePrueba(): RuntimeException
{
    return new RuntimeException('Sin conexión con el banco');
}

test('una falla del sistema queda en rojo con su origen y la misma falla suma ocurrencias', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR, 'name' => 'GERMAN ADMIN']);
    Route::middleware('web')->get('/prueba-falla', fn () => throw fallaDePrueba());

    $this->actingAs($admin)->get('/prueba-falla')->assertServerError();
    report(fallaDePrueba());

    $falla = EventoAplicacion::query()->sole();
    expect($falla)->toMatchArray([
        'tipo' => EventoAplicacion::TIPO_ERROR,
        'nivel' => EventoAplicacion::NIVEL_CRITICO,
        'estado' => EventoAplicacion::ESTADO_PENDIENTE,
        'modulo' => 'Operaciones',
        'titulo' => 'RuntimeException: Sin conexión con el banco',
        'ocurrencias' => 2,
        'user_id' => $admin->id,
        'metodo' => 'GET',
    ])
        ->and($falla->semaforo())->toBe('rojo')
        ->and($falla->detalle['origen'])->toStartWith('tests/Feature/EventosAplicacionTest.php:')
        ->and($falla->detalle['reincidente'])->toBeFalse();

    // Resuelta y vuelve a ocurrir: se registra de nuevo como reincidente.
    $this->actingAs($admin)->patch(route('control-planillas.eventos.gestionar', $falla), ['estado' => 'resuelto', 'observacion' => 'Se corrigió la conexión'])
        ->assertSessionHasNoErrors();
    report(fallaDePrueba());

    expect(EventoAplicacion::query()->count())->toBe(2)
        ->and(EventoAplicacion::query()->latest('id')->first()->detalle['reincidente'])->toBeTrue()
        ->and($falla->fresh()->semaforo())->toBe('verde');
});

test('las acciones de los usuarios quedan en el historial y las sensibles o sin permiso quedan para revisar', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $cuadrada = Turno::create(['fecha' => '2026-11-10', 'numero_turno' => 40, 'sobrante_pendiente' => 0]);
    $sinGuardar = Turno::create(['fecha' => '2026-11-10', 'numero_turno' => 41]);

    $this->actingAs($admin)->post(route('turnos.revisar', $cuadrada))->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('turnos.revisar', $sinGuardar))->assertSessionHasErrors('revisar');
    $this->actingAs($admin)->post(route('turnos.anular', $sinGuardar), ['motivo_anulacion' => 'Repetida']);
    $this->actingAs($islero)->post(route('turnos.revisar', $cuadrada))->assertForbidden();
    // Las consultas no se registran.
    $this->actingAs($admin)->get(route('control-planillas.index'))->assertOk();

    expect(EventoAplicacion::query()->orderBy('id')->get()->map(fn (EventoAplicacion $evento): array => [$evento->titulo, $evento->nivel, $evento->estado])->all())
        ->toBe([
            ['Marcó como revisada planilla de turno #40', 'informativo', 'registrado'],
            ['Marcó como revisada planilla de turno #41 (no se guardó: datos con errores)', 'informativo', 'registrado'],
            ['Anuló planilla de turno #41', 'advertencia', 'pendiente'],
            ['Intento sin permiso: marcó como revisada planilla de turno #40', 'advertencia', 'pendiente'],
        ])
        ->and(EventoAplicacion::query()->where('titulo', 'like', 'Intento sin permiso%')->value('user_id'))->toBe($islero->id);
});

test('los intentos fallidos de ingreso del mismo usuario en el dia se agrupan', function () {
    $usuario = User::factory()->create(['email' => 'rosa@eds.test']);

    $this->post('/login', ['email' => 'rosa@eds.test', 'password' => 'equivocada']);
    $this->post('/login', ['email' => 'ROSA@eds.test', 'password' => 'otra']);

    $intento = EventoAplicacion::query()->where('tipo', EventoAplicacion::TIPO_ACCESO)->sole();
    expect($intento)->toMatchArray([
        'titulo' => 'Intento fallido de ingreso con rosa@eds.test',
        'nivel' => EventoAplicacion::NIVEL_ADVERTENCIA,
        'estado' => EventoAplicacion::ESTADO_PENDIENTE,
        'ocurrencias' => 2,
        'user_id' => $usuario->id,
    ])
        ->and(json_encode($intento->getAttributes()))->not->toContain('equivocada');
});

test('administracion gestiona las fallas con semaforo, observacion obligatoria al cerrar y en lote', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR, 'name' => 'GERMAN ADMIN']);
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $critica = EventoAplicacion::factory()->create(['titulo' => 'QueryException: tabla bloqueada']);
    $advertencias = EventoAplicacion::factory()->advertencia()->count(2)->sequence(['titulo' => 'Eliminó cliente #5'], ['titulo' => 'Anuló planilla de turno #9'])->create();
    EventoAplicacion::factory()->informativo()->create(['titulo' => 'Guardó compra de combustible']);

    $pantalla = $this->actingAs($admin)->get(route('control-planillas.eventos.index'))
        ->assertOk()
        ->assertSee('Hay 1 falla(s) crítica(s) sin atender.')
        ->assertSee('FALLAS Y ADVERTENCIAS POR GESTIONAR (3)')
        ->assertSee('QueryException: tabla bloqueada')
        ->assertDontSee('Guardó compra de combustible');
    expect($pantalla->viewData('semaforo'))->toBe(['rojo' => 1, 'amarillo' => 2, 'verde' => 1, 'gris' => 0]);

    // El historial completo del periodo incluye los eventos informativos.
    $this->actingAs($admin)->get(route('control-planillas.eventos.index', ['vista' => 'todos']))
        ->assertSee('Guardó compra de combustible');
    $this->actingAs($admin)->get(route('control-planillas.eventos.index', ['vista' => 'rojo']))
        ->assertSee('QueryException: tabla bloqueada')
        ->assertDontSee('Eliminó cliente #5');

    // Tomarla en revisión la pasa a amarillo; para cerrarla se exige la observación.
    $this->actingAs($admin)->patch(route('control-planillas.eventos.gestionar', $critica), ['estado' => 'en_revision'])->assertSessionHasNoErrors();
    expect($critica->fresh()->semaforo())->toBe('amarillo');
    $this->actingAs($admin)->patch(route('control-planillas.eventos.gestionar', $critica), ['estado' => 'resuelto'])
        ->assertSessionHasErrors(['observacion' => 'Escriba qué se hizo (o por qué se descarta) para cerrar el evento.']);
    $this->actingAs($admin)->patch(route('control-planillas.eventos.gestionar', $critica), ['estado' => 'resuelto', 'observacion' => 'Se reinició la base de datos'])
        ->assertSessionHas('success', 'Evento #'.$critica->id.' quedó RESUELTO.');

    $critica->refresh();
    expect($critica->gestionado_por)->toBe($admin->id)
        ->and(array_column($critica->detalle['gestiones'], 'estado'))->toBe(['en_revision', 'resuelto'])
        ->and($critica->detalle['gestiones'][1])->toMatchArray(['usuario' => 'GERMAN ADMIN', 'observacion' => 'Se reinició la base de datos']);

    // En lote.
    $this->actingAs($admin)->patch(route('control-planillas.eventos.gestionar-varios'), [
        'eventos' => $advertencias->pluck('id')->all(), 'estado' => 'descartado', 'observacion' => 'Cambios autorizados',
    ])->assertSessionHas('success', '2 evento(s) quedaron DESCARTADO.');
    expect(EventoAplicacion::query()->porGestionar()->count())->toBe(0);

    $this->actingAs($admin)->get(route('control-planillas.eventos.index'))
        ->assertSee('Todo en orden.')
        ->assertSee('No hay fallas ni advertencias por gestionar.');

    $this->actingAs($islero)->get(route('control-planillas.eventos.index'))->assertForbidden();
    $this->actingAs($islero)->patch(route('control-planillas.eventos.gestionar', $critica), ['estado' => 'pendiente'])->assertForbidden();
});

test('el informe de auditoria muestra el semaforo general con las fallas de la aplicacion y la pestana de eventos', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    EventoAplicacion::factory()->create();
    Turno::create(['fecha' => now()->toDateString(), 'numero_turno' => 50, 'sobrante_pendiente' => 0]);

    $this->actingAs($admin)->get(route('control-planillas.index'))
        ->assertOk()
        ->assertSee('Atención: hay novedades críticas.')
        ->assertSee('1 fallas críticas de la aplicación')
        ->assertSee('Fallas y eventos de la aplicación')
        ->assertSee(route('turnos.revisar', Turno::query()->where('numero_turno', 50)->sole()));
});
