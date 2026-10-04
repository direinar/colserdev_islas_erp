<?php

use App\Models\Customer;
use App\Models\Turno;
use App\Models\User;

function guardarTurno(User $user, int $numero, array $extra = [])
{
    return test()->actingAs($user)->post(route('turnos.store'), array_merge([
        'fecha' => '2026-09-25',
        'numero_turno' => $numero,
        'sobrante_pendiente' => '0',
    ], $extra));
}

test('no se marca como revisada una planilla con sobrante o faltante sin trasladar', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    guardarTurno($admin, 3, ['sobrante_pendiente' => '-20600']);
    $turno = Turno::firstOrFail();

    $this->actingAs($admin)->from(route('turnos.pendientes'))->post(route('turnos.revisar', $turno))
        ->assertRedirect(route('turnos.pendientes'))
        ->assertSessionHasErrors(['revisar' => 'Turno #3: tiene un FALTANTE de $20.600 sin trasladar. Use TRASLADAR y guarde la planilla antes de marcarla como revisada.']);

    expect($turno->fresh()->revisado)->toBeFalse();

    // Después de trasladar y guardar, el pendiente queda en cero y ya se puede revisar.
    guardarTurno($admin, 3, ['traslado_faltante' => '20600', 'sobrante_pendiente' => '0']);
    $this->actingAs($admin)->post(route('turnos.revisar', $turno))->assertSessionHasNoErrors();

    expect($turno->fresh()->revisado)->toBeTrue();
});

test('una planilla que no se ha vuelto a guardar no se puede revisar', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    guardarTurno($admin, 3, ['sobrante_pendiente' => null]);
    $turno = Turno::firstOrFail();

    $this->actingAs($admin)->post(route('turnos.revisar', $turno))->assertSessionHasErrors('revisar');

    expect($turno->fresh()->revisado)->toBeFalse();
});

test('no se guardan valores de cartera o recaudos sin cliente', function (string $seccion, array $fila) {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    guardarTurno($admin, 3, [$seccion => [$fila]])->assertSessionHasErrors($seccion);

    expect(Turno::count())->toBe(0);
})->with([
    'cartera' => ['cartera', ['cartera_factura_no' => 'F-1', 'cliente_id' => '', 'cartera_valor' => '50.000']],
    'recaudos por islas' => ['recaudos', ['cliente_id' => '', 'valor' => '30.000']],
    'recaudos por administracion' => ['recaudos_admin', ['banco' => 'Caja general', 'responsable_id' => '', 'valor' => '20.000']],
]);

test('el credito directo no admite cliente ni valor sin numero de factura', function (array $fila) {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurno($admin, 3, ['cartera' => [array_merge(['cliente_id' => $cliente->id], $fila)]])
        ->assertSessionHasErrors(['cartera' => 'En CARTERA - CRÉDITO DIRECTO hay filas sin No. de FACTURA. Digite la factura antes del cliente y el valor.']);

    expect(Turno::count())->toBe(0);
})->with([
    'cliente y valor' => [['cartera_factura_no' => '', 'cartera_valor' => '50.000']],
    'solo cliente' => [['cartera_factura_no' => '  ', 'cartera_valor' => '']],
]);

test('la planilla tiene el boton para copiar las lecturas iniciales a finales', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->get(route('turnos.create'))
        ->assertOk()
        ->assertSee('COPIAR SALDOS INICIALES A FINALES')
        ->assertSee('id="sobrante-pendiente-input"', false);
});

test('una planilla revisada no se puede modificar ni por el administrador', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    guardarTurno($admin, 3);
    $turno = Turno::firstOrFail();
    $this->actingAs($admin)->post(route('turnos.revisar', $turno));

    guardarTurno($admin, 3)->assertForbidden();

    $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 3]))
        ->assertOk()
        ->assertSee('PLANILLA REVISADA.')
        ->assertSee('Planilla REVISADA: el registro está bloqueado')
        ->assertSee('Volver a pendiente de revisión')
        ->assertDontSee('onclick="limpiarPlanilla()"', false);
});

test('el administrador vuelve una planilla revisada a pendiente de revision', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    guardarTurno($admin, 3);
    $turno = Turno::firstOrFail();
    $this->actingAs($admin)->post(route('turnos.revisar', $turno));

    $this->actingAs($islero)->post(route('turnos.reabrir', $turno))->assertForbidden();
    $this->actingAs($admin)->post(route('turnos.reabrir', $turno))->assertRedirect();

    expect($turno->fresh()->revisado)->toBeFalse()
        ->and($turno->fresh()->revisado_por)->toBeNull();

    guardarTurno($islero, 3)->assertRedirect();
});

test('el traslado no se multiplica al volver a guardar la planilla', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    guardarTurno($admin, 3, ['traslado_sobrante' => '-98000', 'traslado_faltante' => '0']);
    // Al recargar, el formulario reenvía el valor guardado con decimales.
    guardarTurno($admin, 3, ['traslado_sobrante' => '-98000.00', 'traslado_faltante' => '0.00']);
    guardarTurno($admin, 3, ['traslado_sobrante' => (string) Turno::firstOrFail()->traslado_sobrante]);

    expect((float) Turno::firstOrFail()->traslado_sobrante)->toBe(-98000.0);

    $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 3]))
        ->assertSee('id="traslado-sobrante-input"'."\n".'            value="-98000"', false);
});

test('solo el administrador puede reversar un traslado guardado', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);

    guardarTurno($islero, 5, ['traslado_faltante' => '20600']);
    guardarTurno($islero, 5, ['traslado_faltante' => '0']);

    expect((float) Turno::firstOrFail()->traslado_faltante)->toBe(20600.0);

    guardarTurno($admin, 5, ['traslado_faltante' => '0']);

    expect((float) Turno::firstOrFail()->traslado_faltante)->toBe(0.0);
});

test('buscar por fecha lista las planillas de ese dia', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    guardarTurno($admin, 3);
    guardarTurno($admin, 4);
    test()->actingAs($admin)->post(route('turnos.store'), ['fecha' => '2026-09-26', 'numero_turno' => 5]);

    $this->actingAs($admin)->get(route('turnos.create', ['buscar_fecha' => '2026-09-25']))
        ->assertOk()
        ->assertSee('Turno 003')
        ->assertSee('Turno 004')
        ->assertDontSee('Turno 005')
        ->assertDontSee('>Buscar</button>', false);
});
