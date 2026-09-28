<?php

use App\Models\Turno;
use App\Models\User;

function guardarTurno(User $user, int $numero, array $extra = [])
{
    return test()->actingAs($user)->post(route('turnos.store'), array_merge([
        'fecha' => '2026-09-25',
        'numero_turno' => $numero,
    ], $extra));
}

test('una planilla revisada no se puede modificar ni por el administrador', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    guardarTurno($admin, 3);
    $turno = Turno::firstOrFail();
    $this->actingAs($admin)->post(route('turnos.revisar', $turno));

    guardarTurno($admin, 3)->assertForbidden();

    $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 3]))
        ->assertOk()
        ->assertSee('PLANILLA REVISADA.')
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
