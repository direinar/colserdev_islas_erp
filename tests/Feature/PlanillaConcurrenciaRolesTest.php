<?php

use App\Models\Customer;
use App\Models\FuelPrice;
use App\Models\Turno;
use App\Models\User;

beforeEach(function () {
    FuelPrice::create(['name' => 'Gasolina', 'price' => 16395, 'turno_desde' => 1, 'active' => true]);
    FuelPrice::create(['name' => 'ACPM', 'price' => 11725, 'turno_desde' => 1, 'active' => true]);
});

/**
 * Planilla digitada en un formulario "Nuevo" abierto con el número $numero.
 */
function planillaNueva(User $user, int $numero, string $surtidor, string $galones, string $consignacionNo, array $extra = [])
{
    return test()->actingAs($user)->post(route('turnos.store'), array_merge([
        'fecha' => '2026-10-28',
        'numero_turno' => $numero,
        'planilla_nueva' => '1',
        'ventas' => [['surtidor' => $surtidor, 'combustible' => str_ends_with($surtidor, 'ACPM') ? 'ACPM' : 'CTE', 'no_cierre' => '10', 'galones' => $galones]],
        'consignaciones' => [['consignacion_no' => $consignacionNo, 'consignacion_valor' => '100.000']],
    ], $extra));
}

test('si una islera y el gerente abren Nuevo a la vez, la segunda planilla se guarda con el siguiente numero y no se pierde', function () {
    $carlos = User::factory()->create(['role' => User::ROLE_GERENTE, 'name' => 'Carlos Beltrán']);
    $rosa = User::factory()->create(['role' => User::ROLE_ISLERO, 'name' => 'ROSA RIVERA']);

    // Los dos abrieron "Nuevo" con el #143; Carlos guarda primero.
    planillaNueva($carlos, 143, 'SURTIDOR 1 CTE', '11', '5001')->assertSessionHasNoErrors();

    // Antes a Rosa se le rechazaba ("la registró otro usuario") y perdía lo digitado.
    planillaNueva($rosa, 143, 'SURTIDOR 3 CTE', '46.890', '5002')
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('turnos.create', ['fecha' => '2026-10-28', 'numero_turno' => 144]))
        ->assertSessionHas('success', 'Turno guardado correctamente como turno #144: el #143 lo registró Carlos Beltrán mientras usted digitaba esta planilla.')
        ->assertSessionHas('borrador_descartar', '2026-10-28:143');

    $planillas = Turno::query()->with('ventas')->orderBy('numero_turno')->get();

    expect($planillas->map(fn (Turno $t) => [$t->numero_turno, $t->user_id, $t->ventas->sole()->surtidor, (float) $t->ventas->sole()->galones])->all())
        ->toBe([[143, $carlos->id, 'SURTIDOR 1 CTE', 11.0], [144, $rosa->id, 'SURTIDOR 3 CTE', 46.89]]);
});

test('si el gerente guarda despues con el mismo numero, ya no sobrescribe la planilla de la islera', function () {
    $carlos = User::factory()->create(['role' => User::ROLE_GERENTE, 'name' => 'Carlos Beltrán']);
    $maria = User::factory()->create(['role' => User::ROLE_ISLERO, 'name' => 'MARIA GROSSO']);

    planillaNueva($maria, 143, 'SURTIDOR 2 ACPM', '93.12', '5001')->assertSessionHasNoErrors();
    // Antes el gerente reemplazaba el contenido de la #143 de María con su planilla.
    planillaNueva($carlos, 143, 'SURTIDOR 1 CTE', '11', '5002')->assertSessionHasNoErrors();

    $deMaria = Turno::query()->where('numero_turno', 143)->with('ventas')->sole();
    $deCarlos = Turno::query()->where('numero_turno', 144)->with('ventas')->sole();

    expect($deMaria->user_id)->toBe($maria->id)
        ->and($deMaria->ventas->sole()->surtidor)->toBe('SURTIDOR 2 ACPM')
        ->and((float) $deMaria->ventas->sole()->galones)->toBe(93.12)
        ->and($deCarlos->user_id)->toBe($carlos->id)
        ->and($deCarlos->ventas->sole()->surtidor)->toBe('SURTIDOR 1 CTE');
});

test('editar una planilla ya guardada sigue actualizando la misma, sin crear otra', function () {
    $carlos = User::factory()->create(['role' => User::ROLE_GERENTE]);
    planillaNueva($carlos, 143, 'SURTIDOR 1 CTE', '11', '5001');

    // La planilla abierta desde la búsqueda no lleva planilla_nueva: se corrige la misma.
    $this->actingAs($carlos)->post(route('turnos.store'), [
        'fecha' => '2026-10-28',
        'numero_turno' => 143,
        'ventas' => [['surtidor' => 'SURTIDOR 1 CTE', 'combustible' => 'CTE', 'no_cierre' => '10', 'galones' => '12']],
        'consignaciones' => [['consignacion_no' => '5001', 'consignacion_valor' => '100.000']],
    ])->assertSessionHasNoErrors();

    expect(Turno::count())->toBe(1)
        ->and((float) Turno::query()->sole()->ventas()->sole()->galones)->toBe(12.0);
});

test('en todos los roles lo digitado se guarda, se ve al abrir y no se pierde al volver a guardar', function (string $rol) {
    $usuario = User::factory()->create(['role' => $rol]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '900306424']);

    $datos = [
        'ventas' => [
            ['surtidor' => 'SURTIDOR 3 CTE', 'combustible' => 'CTE', 'no_cierre' => '2227', 'galones' => '46.890'],
            ['surtidor' => 'SURTIDOR 3 ACPM', 'combustible' => 'ACPM', 'no_cierre' => '2228', 'galones' => '17.930'],
        ],
        'consignaciones' => [['consignacion_no' => '2227', 'consignacion_valor' => '674.700']],
        'qr_pagos' => [['concepto' => 'NEQUI', 'valor' => '510.000']],
        'varios' => [['concepto' => 'PEAJE', 'valor' => '12.000']],
        'recaudos_admin' => [['banco' => 'CAJA', 'responsable_id' => $cliente->id, 'valor' => '50.000']],
    ];

    planillaNueva($usuario, 149, 'SURTIDOR 3 CTE', '46.890', '2227', $datos)->assertSessionHasNoErrors();

    $turno = Turno::query()->sole();
    $verificar = function () use ($turno, $rol) {
        $turno->refresh()->load(['ventas', 'consignaciones', 'qrPagos', 'varios', 'recaudosAdmin']);

        expect($turno->ventas->map(fn ($v) => [$v->surtidor, $v->no_cierre, (float) $v->galones, (float) $v->valor])->all())->toBe([
            ['SURTIDOR 3 CTE', '2227', 46.89, 768761.55],
            ['SURTIDOR 3 ACPM', '2228', 17.93, 210229.25],
        ])
            ->and((float) $turno->total_ventas)->toBe(978990.8)
            ->and((float) $turno->consignaciones->sole()->valor)->toBe(674700.0)
            ->and((float) $turno->qrPagos->sole()->valor)->toBe(510000.0)
            ->and($turno->varios->sole()->concepto)->toBe('PEAJE')
            // Lógica de cada rol: el islero no alimenta RECAUDOS POR ADMINISTRACIÓN.
            ->and($turno->recaudosAdmin)->toHaveCount($rol === User::ROLE_ISLERO ? 0 : 1);
    };

    $verificar();

    // Al abrir la planilla se ven los valores calculados, no en cero.
    $this->actingAs($usuario)->get(route('turnos.create', ['turno_busqueda' => 149]))
        ->assertOk()
        ->assertSee('value="768.762"', false)
        ->assertSee('value="674.700"', false);

    // Volver a guardarla (sin planilla_nueva) conserva todo.
    $this->actingAs($usuario)->post(route('turnos.store'), ['fecha' => '2026-10-28', 'numero_turno' => 149] + $datos)
        ->assertSessionHasNoErrors();

    $verificar();
    expect(Turno::count())->toBe(1);
})->with([
    'islero' => User::ROLE_ISLERO,
    'jefe de patios' => User::ROLE_JEFE_PATIOS,
    'administrador' => User::ROLE_ADMINISTRADOR,
    'gerente' => User::ROLE_GERENTE,
]);
