<?php

use App\Models\Nomina\Empleado;
use App\Models\User;

function datosEmpleado(array $cambios = []): array
{
    return array_merge([
        'tipo_documento' => 'CC',
        'numero_documento' => '1002365104',
        'nombres' => 'Rosa Teresa',
        'apellidos' => 'Rivera Barajas',
        'nombre_corto' => ' rosa ',
        'cargo' => 'OPERARIO DE ISLAS',
        'fecha_ingreso' => '2017-08-01',
        'tipo_contrato' => 'indefinido',
        'salario_basico' => '1.750.905',
        'recibe_auxilio_transporte' => '1',
        'aplica_recargos' => '1',
        'forma_pago' => 'transferencia',
        'banco' => 'Davivienda',
        'tipo_cuenta' => 'ahorros',
        'numero_cuenta' => '0123456789',
        'activo' => '1',
    ], $cambios);
}

test('los invitados no entran al modulo de empleados', function () {
    $this->get(route('nomina.empleados.index'))->assertRedirect(route('login'));
});

test('crea un empleado con el sueldo digitado con separadores de miles', function () {
    $gerente = User::factory()->create(['role' => User::ROLE_GERENTE]);

    $this->actingAs($gerente)->post(route('nomina.empleados.store'), datosEmpleado())
        ->assertRedirect()
        ->assertSessionHas('success');

    $empleado = Empleado::sole();

    expect((float) $empleado->salario_basico)->toBe(1750905.0)
        ->and($empleado->nombre_corto)->toBe('ROSA')
        ->and($empleado->recibe_auxilio_transporte)->toBeTrue()
        ->and($empleado->aplica_recargos)->toBeTrue();
});

test('la seguridad social del empleado incluye la ARL', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('nomina.empleados.store'), datosEmpleado(['eps' => 'Nueva EPS', 'arl' => 'Positiva']))
        ->assertSessionHasNoErrors();
    $empleado = Empleado::sole();

    expect($empleado->arl)->toBe('Positiva');
    $this->actingAs($admin)->get(route('nomina.empleados.show', $empleado))->assertSeeInOrder(['Seguridad social', 'ARL', 'Positiva']);
    $this->actingAs($admin)->get(route('nomina.empleados.edit', $empleado))->assertSee('name="arl"', false)->assertSee('value="Positiva"', false);
});

test('exige vencimiento al contrato fijo y cuenta bancaria al pago por transferencia', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('nomina.empleados.store'), datosEmpleado([
        'tipo_contrato' => 'fijo',
        'banco' => '',
        'numero_cuenta' => '',
    ]))->assertSessionHasErrors(['fecha_vencimiento_contrato', 'banco', 'numero_cuenta']);

    expect(Empleado::count())->toBe(0);
});

test('el pago en efectivo no guarda datos bancarios', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('nomina.empleados.store'), datosEmpleado(['forma_pago' => 'efectivo']))
        ->assertSessionHasNoErrors();

    expect(Empleado::sole()->banco)->toBeNull();
});

test('retirar un empleado exige la fecha de retiro y lo saca del listado de activos', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $empleado = Empleado::factory()->create(['nombres' => 'Dagoberto']);

    $this->actingAs($admin)->put(route('nomina.empleados.update', $empleado), datosEmpleado([
        'numero_documento' => $empleado->numero_documento,
        'nombres' => 'Dagoberto',
        'activo' => null,
    ]))->assertSessionHasErrors('fecha_retiro');

    $this->actingAs($admin)->put(route('nomina.empleados.update', $empleado), datosEmpleado([
        'numero_documento' => $empleado->numero_documento,
        'nombres' => 'Dagoberto',
        'activo' => null,
        'fecha_retiro' => '2026-10-31',
        'motivo_retiro' => 'Renuncia',
    ]))->assertSessionHasNoErrors();

    expect($empleado->fresh()->activo)->toBeFalse();

    $this->actingAs($admin)->get(route('nomina.empleados.index'))->assertOk()->assertDontSee('Dagoberto');
    $this->actingAs($admin)->get(route('nomina.empleados.index', ['estado' => 'retirados']))->assertOk()->assertSee('Dagoberto');
});

test('muestra el listado, la ficha y el formulario de edicion del empleado', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $empleado = Empleado::factory()->create(['tipo_contrato' => 'fijo', 'fecha_vencimiento_contrato' => now()->addDays(10)->toDateString()]);

    $this->actingAs($admin)->get(route('nomina.empleados.index'))->assertOk()->assertSee($empleado->nombre_completo);
    $this->actingAs($admin)->get(route('nomina.empleados.show', $empleado))->assertOk()->assertSee('El contrato vence el');
    $this->actingAs($admin)->get(route('nomina.empleados.edit', $empleado))->assertOk()->assertSee($empleado->numero_documento);
    $this->actingAs($admin)->get(route('nomina.empleados.create'))->assertOk();
});
