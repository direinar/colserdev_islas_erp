<?php

use App\Models\Customer;
use App\Models\FuelPrice;
use App\Models\Turno;
use App\Models\User;

function guardarPlanilla(User $user, int $numero, array $datos = [], string $fecha = '2026-09-25')
{
    turnoAbierto($user, $numero, $fecha);

    return test()->actingAs($user)->post(route('turnos.store'), array_merge([
        'fecha' => $fecha,
        'numero_turno' => $numero,
    ], $datos));
}

test('las filas con valor cero se guardan y las filas en blanco no', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarPlanilla($admin, 3, [
        'varios' => [['concepto' => 'PEAJE', 'valor' => '0'], ['concepto' => '', 'valor' => '']],
        'qr_pagos' => [['concepto' => 'NEQUI', 'valor' => '0'], ['concepto' => 'TC', 'valor' => '']],
        'recaudos' => [['cliente_id' => $cliente->id, 'valor' => '0'], ['cliente_id' => '', 'valor' => '']],
        'gasolina_eds' => [['puntos' => '0'], ['puntos' => '']],
        'transferencias' => [['puntos' => '0'], ['puntos' => '']],
        'consignaciones' => [['consignacion_no' => '', 'consignacion_valor' => '0'], ['consignacion_no' => '', 'consignacion_valor' => '']],
        'urea_lubricantes' => [['producto' => 'UREA', 'cantidad' => '0', 'valor_sin_iva' => '0', 'iva' => '0', 'total' => '0']],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $turno = Turno::query()->with(['varios', 'qrPagos', 'recaudos', 'gasolinaEds', 'transferencias', 'consignaciones', 'lubricantes'])->sole();

    expect($turno->varios->pluck('concepto')->all())->toBe(['PEAJE'])
        ->and($turno->qrPagos->pluck('concepto')->all())->toBe(['NEQUI'])
        ->and($turno->recaudos)->toHaveCount(1)
        ->and($turno->gasolinaEds)->toHaveCount(1)
        ->and($turno->transferencias)->toHaveCount(1)
        ->and($turno->consignaciones)->toHaveCount(1)
        ->and($turno->lubricantes->pluck('producto')->all())->toBe(['UREA']);

    // Al volver a abrir la planilla, el cero sigue ahí.
    $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 3]))
        ->assertSee('value="PEAJE"', false);
});

test('las observaciones de la planilla se guardan', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    guardarPlanilla($admin, 3, ['observaciones' => 'Surtidor 2 con fuga, se reportó a mantenimiento.']);

    expect(Turno::query()->sole()->observaciones)->toBe('Surtidor 2 con fuga, se reportó a mantenimiento.');

    $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 3]))
        ->assertSee('Surtidor 2 con fuga, se reportó a mantenimiento.');
});

test('un pago electrónico con valor y sin concepto no se guarda', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    guardarPlanilla($admin, 3, ['qr_pagos' => [['concepto' => '', 'valor' => '50.000']]])
        ->assertSessionHasErrors('qr_pagos');
    guardarPlanilla($admin, 3, ['qr_pagos' => [['concepto' => 'Concepto', 'valor' => '50.000']]])
        ->assertSessionHasErrors('qr_pagos.0.concepto');

    // La planilla abierta quedó sin guardar.
    expect(Turno::query()->whereNotNull('sobrante_pendiente')->count())->toBe(0);
});

test('un recaudo por administración con cliente o valor y sin banco no se guarda', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarPlanilla($admin, 3, ['recaudos_admin' => [['banco' => '', 'responsable_id' => $cliente->id, 'valor' => '']]])
        ->assertSessionHasErrors('recaudos_admin');
    guardarPlanilla($admin, 3, ['recaudos_admin' => [['banco' => '', 'responsable_id' => '', 'valor' => '50.000']]])
        ->assertSessionHasErrors('recaudos_admin');

    // La planilla abierta quedó sin guardar.
    expect(Turno::query()->whereNotNull('sobrante_pendiente')->count())->toBe(0);

    guardarPlanilla($admin, 3, ['recaudos_admin' => [['banco' => 'CAJA', 'responsable_id' => $cliente->id, 'valor' => '50.000']]])
        ->assertSessionHasNoErrors();

    // En pantalla: el cliente y el valor exigen primero el banco.
    $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 3]))
        ->assertSeeInOrder(["campos: '.recaudos-admin-responsable, .recaudo-admin-valor'", "requisito: '.recaudos-admin-banco'"], false)
        ->assertSee('recaudos-admin-banco"', false);
});

test('si el guardado falla, la planilla vuelve con lo que se había digitado', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    turnoAbierto($admin, 3, '2026-09-25');
    $this->actingAs($admin)->from(route('turnos.create'))->post(route('turnos.store'), [
        'fecha' => '2026-09-25',
        'numero_turno' => 3,
        'observaciones' => 'Turno de la tarde',
        'varios' => [['concepto' => 'CAFETERIA', 'valor' => '12.500']],
        'consignaciones' => [['consignacion_no' => 'C-77', 'consignacion_valor' => '300.000']],
        // Recaudo sin cliente: el servidor rechaza el guardado.
        'recaudos' => [['cliente_id' => '', 'valor' => '30.000']],
    ])->assertRedirect(route('turnos.create'))->assertSessionHasErrors('recaudos');

    // La planilla abierta quedó sin guardar.
    expect(Turno::query()->whereNotNull('sobrante_pendiente')->count())->toBe(0);

    $this->actingAs($admin)->get(route('turnos.create'))
        ->assertOk()
        ->assertSee('value="CAFETERIA"', false)
        ->assertSee('value="12.500"', false)
        ->assertSee('value="C-77"', false)
        ->assertSee('value="300.000"', false)
        ->assertSee('Turno de la tarde');
});

test('el islero y el jefe de patios solo modifican sus planillas; el administrador cualquiera y el vendedor se conserva', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO, 'name' => 'Ana']);
    $beto = User::factory()->create(['role' => User::ROLE_ISLERO, 'name' => 'Beto']);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $gerente = User::factory()->create(['role' => User::ROLE_GERENTE]);

    guardarPlanilla($ana, 3, ['observaciones' => 'original']);

    guardarPlanilla($beto, 3, ['observaciones' => 'cambiado por Beto'])->assertForbidden();
    guardarPlanilla($jefe, 3, ['observaciones' => 'cambiado por el jefe'])->assertForbidden();

    // El jefe de patios ve la planilla del islero, pero sin poder guardarla.
    $this->actingAs($jefe)->get(route('turnos.create', ['turno_busqueda' => 3]))
        ->assertOk()
        ->assertDontSee('<button type="submit" form="turno-form"', false);

    guardarPlanilla($ana, 3, ['observaciones' => 'corregido por Ana'])->assertRedirect();
    guardarPlanilla($gerente, 3, ['observaciones' => 'corregido por el gerente'])->assertRedirect();

    $turno = Turno::query()->sole();

    expect($turno->observaciones)->toBe('corregido por el gerente')
        ->and($turno->nombre_vendedor)->toBe('Ana')
        ->and($turno->user_id)->toBe($ana->id);
});

test('el islero solo ve sus planillas y el jefe de patios las suyas y las de los isleros', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $beto = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $otroJefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    guardarPlanilla($ana, 1);
    guardarPlanilla($jefe, 2);
    guardarPlanilla($admin, 3);

    $verPlanilla = fn (User $usuario, int $numero) => $this->actingAs($usuario)->get(route('turnos.create', ['turno_busqueda' => $numero]));
    $planillasDelDia = fn (User $usuario): array => $this->actingAs($usuario)
        ->get(route('turnos.create', ['buscar_fecha' => '2026-09-25']))
        ->viewData('turnosDeFecha')->pluck('numero_turno')->all();

    $verPlanilla($ana, 1)->assertOk();
    $verPlanilla($beto, 1)->assertForbidden();
    $verPlanilla($ana, 2)->assertForbidden();
    $verPlanilla($jefe, 1)->assertOk();
    $verPlanilla($otroJefe, 2)->assertForbidden();
    $verPlanilla($jefe, 3)->assertForbidden();
    $verPlanilla($admin, 2)->assertOk();

    expect($planillasDelDia($ana))->toBe([1])
        ->and($planillasDelDia($beto))->toBe([])
        ->and($planillasDelDia($jefe))->toBe([1, 2])
        ->and($planillasDelDia($admin))->toBe([1, 2, 3]);
});

test('después de revisada, el islero sigue viendo su planilla pero ya no la modifica', function () {
    $ana = User::factory()->create(['role' => User::ROLE_ISLERO]);

    guardarPlanilla($ana, 1);
    Turno::query()->sole()->update(['revisado' => true, 'revisado_por' => 'Admin']);

    $this->actingAs($ana)->get(route('turnos.create', ['turno_busqueda' => 1]))
        ->assertOk()
        ->assertSee('PLANILLA REVISADA.')
        ->assertDontSee('<button type="submit" form="turno-form"', false);

    guardarPlanilla($ana, 1, ['observaciones' => 'cambio'])->assertForbidden();
});

test('solo el administrador cambia la fecha de una planilla ya guardada', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    guardarPlanilla($jefe, 3);

    guardarPlanilla($jefe, 3, [], '2026-09-30')->assertSessionHasErrors('fecha');
    expect(Turno::query()->sole()->fecha->format('Y-m-d'))->toBe('2026-09-25');

    guardarPlanilla($admin, 3, [], '2026-09-30')->assertSessionHasNoErrors();
    expect(Turno::query()->sole()->fecha->format('Y-m-d'))->toBe('2026-09-30');
});

test('el sobrante o faltante pendiente se recalcula al guardar, también después de un traslado', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $venta = ['urea_lubricantes' => [['producto' => 'UREA', 'cantidad' => '1', 'valor_sin_iva' => '100.000', 'iva' => '0', 'total' => '100.000']]];

    // Se vendió 100.000 y se consignó 98.000: faltan 2.000, diga lo que diga el navegador.
    guardarPlanilla($admin, 3, $venta + [
        'consignaciones' => [['consignacion_no' => 'C-1', 'consignacion_valor' => '98.000']],
        'sobrante_pendiente' => '0',
    ]);
    expect((float) Turno::query()->sole()->sobrante_pendiente)->toBe(-2000.0);

    // Se traslada el faltante: queda en cero.
    guardarPlanilla($admin, 3, $venta + [
        'consignaciones' => [['consignacion_no' => 'C-1', 'consignacion_valor' => '98.000']],
        'traslado_faltante' => '2000',
    ]);
    expect((float) Turno::query()->sole()->sobrante_pendiente)->toBe(0.0);

    // Luego se agrega otra consignación de 50.000: aparece un sobrante nuevo en lugar de quedar en 0.
    guardarPlanilla($admin, 3, $venta + [
        'consignaciones' => [
            ['consignacion_no' => 'C-1', 'consignacion_valor' => '98.000'],
            ['consignacion_no' => 'C-2', 'consignacion_valor' => '50.000'],
        ],
        'traslado_faltante' => '2000',
    ]);
    expect((float) Turno::query()->sole()->sobrante_pendiente)->toBe(50000.0);
});

test('el precio del combustible rige desde un número de turno, aunque cambie entre turnos del mismo día', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    FuelPrice::create(['name' => 'Gasolina', 'price' => 16500, 'turno_desde' => 1, 'active' => true]);
    FuelPrice::create(['name' => 'ACPM', 'price' => 9850, 'turno_desde' => 1, 'active' => true]);

    guardarPlanilla($admin, 1);

    // El precio de la gasolina cambia a partir del turno 2, el mismo día.
    FuelPrice::create(['name' => 'Gasolina', 'price' => 17000, 'turno_desde' => 2, 'active' => true]);

    // La planilla que se abre después (turno 2) ya muestra el precio nuevo.
    cerrarTurnos();
    $this->actingAs($admin)->post(route('turnos.abrir'), [
        'fecha' => '2026-09-25', 'turno_dia' => 2, 'hora_inicio' => '14:00', 'mangueras' => ['PLUS 01'],
    ])->assertSessionHasNoErrors();
    $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 2]))
        ->assertOk()
        ->assertSee('data-precio="17000.00"', false);

    guardarPlanilla($admin, 2);
    // Volver a guardar el turno 1 no le cambia el precio.
    guardarPlanilla($admin, 1);

    $turnos = Turno::query()->orderBy('numero_turno')->get();

    expect($turnos->pluck('fecha')->map->toDateString()->unique()->all())->toBe(['2026-09-25'])
        ->and($turnos->pluck('precio_corriente')->all())->toBe(['16500.00', '17000.00'])
        ->and($turnos->pluck('precio_acpm')->all())->toBe(['9850.00', '9850.00']);
});

test('la cantidad de canastilla admite decimales porque la urea se vende por litros', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    guardarPlanilla($admin, 4, [
        'urea_lubricantes' => [['producto' => '22-UREA AUTOMOTRIZ', 'cantidad' => '2.5', 'valor_sin_iva' => '25.000', 'iva' => '0', 'total' => '25.000']],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $turno = Turno::query()->with('lubricantes')->sole();

    expect((float) $turno->lubricantes->sole()->cantidad)->toBe(2.5);

    // Al volver a abrir la planilla se ven los 2,5 litros y el campo acepta decimales.
    $this->actingAs($admin)->get(route('turnos.create', ['fecha' => '2026-09-25', 'numero_turno' => 4]))
        ->assertSee('value="2.5"', false)
        ->assertSee('step="0.001" class="form-control form-control-sm cantidad-input"', false);
});
