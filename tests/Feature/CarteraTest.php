<?php

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Models\Turno;
use App\Models\User;

function guardarTurnoConCartera(User $user, int $numero, string $fecha, array $cartera)
{
    return test()->actingAs($user)->post(route('turnos.store'), [
        'fecha' => $fecha,
        'numero_turno' => $numero,
        'cartera' => $cartera,
    ]);
}

test('credito directo de la planilla de turno se registra en la cartera del cliente', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $cliente = Customer::create(['name' => 'Transportes Boyacá', 'document' => '900123456']);

    guardarTurnoConCartera($islero, 15, '2026-09-10', [
        ['cartera_factura_no' => 'FE-101', 'cliente_id' => $cliente->id, 'cartera_valor' => '350.000'],
        ['cartera_factura_no' => 'FE-102', 'cliente_id' => null, 'cartera_valor' => '20.000'],
    ])->assertRedirect();

    $movimientos = CarteraMovimiento::all();

    expect($movimientos)->toHaveCount(1)
        ->and($movimientos[0]->customer_id)->toBe($cliente->id)
        ->and($movimientos[0]->turno_id)->toBe(Turno::firstOrFail()->id)
        ->and($movimientos[0]->planillas)->toBe('15')
        ->and($movimientos[0]->fecha->toDateString())->toBe('2026-09-10')
        ->and($movimientos[0]->factura)->toBe('FE-101')
        ->and((float) $movimientos[0]->vr_neto_cargo)->toBe(350000.0)
        ->and((float) $movimientos[0]->saldo)->toBe(350000.0)
        ->and($movimientos[0]->concepto)->toBe('Venta a crédito')
        ->and($movimientos[0]->tercero)->toBe('Transportes Boyacá')
        ->and($movimientos[0]->nit)->toBe('900123456');
});

test('volver a guardar el turno actualiza el valor y conserva lo completado en cartera', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($admin, 3, '2026-09-10', [
        ['cartera_factura_no' => 'F-1', 'cliente_id' => $cliente->id, 'cartera_valor' => '100.000'],
    ]);

    $movimiento = CarteraMovimiento::firstOrFail();
    $movimiento->update(['placas' => 'ABC123', 'abonos' => 40000]);

    guardarTurnoConCartera($admin, 3, '2026-09-10', [
        ['cartera_factura_no' => 'F-1', 'cliente_id' => $cliente->id, 'cartera_valor' => '120.000'],
    ]);

    $movimiento->refresh();

    expect(CarteraMovimiento::count())->toBe(1)
        ->and((float) $movimiento->vr_neto_cargo)->toBe(120000.0)
        ->and($movimiento->placas)->toBe('ABC123')
        ->and((float) $movimiento->abonos)->toBe(40000.0)
        ->and((float) $movimiento->saldo)->toBe(80000.0);
});

test('quitar el renglon de la planilla elimina el movimiento de cartera', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($admin, 4, '2026-09-10', [
        ['cartera_factura_no' => 'F-1', 'cliente_id' => $cliente->id, 'cartera_valor' => '100.000'],
    ]);
    guardarTurnoConCartera($admin, 4, '2026-09-10', []);

    expect(CarteraMovimiento::count())->toBe(0);
});

test('al seleccionar un cliente se listan sus movimientos del rango con el saldo anterior', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);
    $otro = Customer::create(['name' => 'Cliente B', 'document' => '2']);

    guardarTurnoConCartera($jefe, 1, '2026-08-30', [
        ['cartera_factura_no' => 'F-AGO', 'cliente_id' => $cliente->id, 'cartera_valor' => '50.000'],
    ]);
    guardarTurnoConCartera($jefe, 2, '2026-09-05', [
        ['cartera_factura_no' => 'F-SEP', 'cliente_id' => $cliente->id, 'cartera_valor' => '70.000'],
        ['cartera_factura_no' => 'F-OTRO', 'cliente_id' => $otro->id, 'cartera_valor' => '99.000'],
    ]);

    $response = $this->actingAs($jefe)->get(route('cartera.index', [
        'customer_id' => $cliente->id,
        'fecha_inicial' => '2026-09-01',
        'fecha_final' => '2026-09-30',
    ]));

    $response->assertOk();

    expect($response->viewData('movimientos')->pluck('factura')->all())->toBe(['F-SEP'])
        ->and($response->viewData('saldoAnterior'))->toBe(50000.0);

    $response->assertSee('F-SEP')->assertDontSee('F-AGO')->assertDontSee('F-OTRO');
});

test('en cartera no se pueden cambiar los datos de planilla y se gestionan movimientos manuales', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($jefe, 7, '2026-09-10', [
        ['cartera_factura_no' => 'F-7', 'cliente_id' => $cliente->id, 'cartera_valor' => '200.000'],
    ]);
    $deTurno = CarteraMovimiento::firstOrFail();
    $manualViejo = CarteraMovimiento::create(['customer_id' => $cliente->id, 'fecha' => '2026-09-11', 'concepto' => 'Viejo']);

    $this->actingAs($jefe)->post(route('cartera.store'), [
        'customer_id' => $cliente->id,
        'eliminar' => [$manualViejo->id],
        'detalles' => [
            [
                'id' => $deTurno->id,
                'fecha' => '2026-01-01',
                'factura' => 'CAMBIADA',
                'concepto' => 'Otro concepto',
                'descuento' => '50.000',
                'placas' => 'XYZ987',
                'abonos' => '150.000',
            ],
            ['fecha' => '2026-09-12', 'concepto' => 'Abono en efectivo', 'abonos' => '30.000'],
        ],
    ])->assertRedirect(route('cartera.index', ['customer_id' => $cliente->id, 'fecha_inicial' => null, 'fecha_final' => null]));

    $deTurno->refresh();
    $manual = CarteraMovimiento::whereNull('turno_id')->firstOrFail();

    expect($deTurno->factura)->toBe('F-7')
        ->and($deTurno->concepto)->toBe('Venta a crédito')
        ->and($deTurno->fecha->toDateString())->toBe('2026-09-10')
        ->and((float) $deTurno->vr_neto_cargo)->toBe(200000.0)
        ->and($deTurno->placas)->toBe('XYZ987')
        ->and((float) $deTurno->abonos)->toBe(150000.0)
        ->and(CarteraMovimiento::find($manualViejo->id))->toBeNull()
        ->and($manual->concepto)->toBe('Abono en efectivo')
        ->and((float) $manual->saldo)->toBe(20000.0);
});

test('el islero no puede entrar al modulo de cartera', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $this->actingAs($islero)->get(route('cartera.index'))->assertForbidden();
});
