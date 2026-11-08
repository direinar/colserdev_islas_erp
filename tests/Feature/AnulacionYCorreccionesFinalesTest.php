<?php

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Models\Turno;
use App\Models\User;
use App\Services\CarteraTurnoSyncService;

function guardarPlanillaFinal(User $usuario, int $numero): Turno
{
    test()->actingAs($usuario)->post(route('turnos.store'), [
        'fecha' => '2026-11-01',
        'numero_turno' => $numero,
        'sobrante_pendiente' => '0',
    ])->assertRedirect();

    return Turno::query()->where('numero_turno', $numero)->sole();
}

test('los isleros ven planillas ajenas pero no pueden modificarlas', function () {
    $isleroA = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $isleroB = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $turno = guardarPlanillaFinal($isleroA, 701);

    $this->actingAs($isleroB)
        ->get(route('turnos.create', ['turno_busqueda' => $turno->numero_turno]))
        ->assertOk()
        ->assertSee('value="701"', false);

    $this->actingAs($isleroB)
        ->post(route('turnos.store'), [
            'fecha' => '2026-11-01',
            'numero_turno' => 701,
            'observaciones' => 'cambio no autorizado',
            'sobrante_pendiente' => '0',
        ])
        ->assertSessionHasErrors('planilla');

    expect($turno->fresh()->observaciones)->toBeNull();
});

test('solo administración puede anular y una planilla anulada queda fuera de operación', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR, 'name' => 'Admin']);
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $turno = guardarPlanillaFinal($islero, 702);
    $turno->cartera()->create(['factura_no' => 'V-702', 'cliente_id' => Customer::create(['name' => 'Cliente 702'])->id, 'valor' => 100]);
    app(CarteraTurnoSyncService::class)->sincronizar($turno->fresh());

    $this->actingAs($islero)
        ->post(route('turnos.anular', $turno), ['motivo_anulacion' => 'duplicada'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('turnos.anular', $turno), ['motivo_anulacion' => 'duplicada'])
        ->assertRedirect(route('turnos.create', ['turno_busqueda' => 702]));

    expect($turno->fresh()->anulada)->toBeTrue()
        ->and(Turno::query()->noAnulados()->whereKey($turno->id)->exists())->toBeFalse()
        ->and(CarteraMovimiento::query()->where('turno_id', $turno->id)->exists())->toBeFalse();

    $this->actingAs($admin)
        ->post(route('turnos.store'), [
            'fecha' => '2026-11-01',
            'numero_turno' => 702,
            'sobrante_pendiente' => '0',
        ])
        ->assertSessionHasErrors('planilla');
});

test('se puede crear un cliente sin NIT', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)
        ->post(route('customers.store'), ['name' => 'Vampi', 'document' => '', 'dv' => ''])
        ->assertSessionHas('success');

    $cliente = Customer::query()->where('name', 'Vampi')->sole();

    expect($cliente->document)->toBeNull()
        ->and($cliente->dv)->toBeNull();
});
