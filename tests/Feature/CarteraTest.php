<?php

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Models\Turno;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

function guardarTurnoConCartera(User $user, int $numero, string $fecha, array $cartera, array $extra = [])
{
    return test()->actingAs($user)->post(route('turnos.store'), array_merge([
        'fecha' => $fecha,
        'numero_turno' => $numero,
        'cartera' => $cartera,
    ], $extra));
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
        ->and($movimientos[0]->origen)->toBe(CarteraMovimiento::ORIGEN_CREDITO_DIRECTO)
        ->and($movimientos[0]->planillas)->toBe('15')
        ->and($movimientos[0]->fecha->toDateString())->toBe('2026-09-10')
        ->and($movimientos[0]->factura)->toBe('FE-101')
        ->and((float) $movimientos[0]->vr_neto_cargo)->toBe(350000.0)
        ->and((float) $movimientos[0]->saldo)->toBe(350000.0)
        ->and($movimientos[0]->concepto)->toBe('Venta a crédito')
        ->and($movimientos[0]->tercero)->toBe('Transportes Boyacá')
        ->and($movimientos[0]->nit)->toBe('900123456');
});

test('volver a guardar el turno actualiza el valor y conserva los datos del vehiculo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($admin, 3, '2026-09-10', [
        ['cartera_factura_no' => 'F-1', 'cliente_id' => $cliente->id, 'cartera_valor' => '100.000'],
    ]);

    $movimiento = CarteraMovimiento::firstOrFail();
    $movimiento->update(['placas' => 'ABC123', 'galones' => 10, 'vr_unitario' => 9000, 'bruto' => 90000]);

    guardarTurnoConCartera($admin, 3, '2026-09-10', [
        ['cartera_factura_no' => 'F-1', 'cliente_id' => $cliente->id, 'cartera_valor' => '120.000'],
    ]);

    $movimiento->refresh();

    expect(CarteraMovimiento::count())->toBe(1)
        ->and((float) $movimiento->vr_neto_cargo)->toBe(120000.0)
        ->and($movimiento->placas)->toBe('ABC123')
        ->and((float) $movimiento->bruto)->toBe(90000.0)
        ->and((float) $movimiento->saldo)->toBe(120000.0);
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

test('los recaudos por islas y por administracion de la planilla son abonos del cliente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'ARKATEC SAS', 'document' => '900306424']);

    guardarTurnoConCartera($admin, 8, '2026-09-12', [
        ['cartera_factura_no' => 'F-8', 'cliente_id' => $cliente->id, 'cartera_valor' => '100.000'],
    ], [
        'recaudos' => [['cliente_id' => $cliente->id, 'valor' => '30.000']],
        'recaudos_admin' => [['banco' => 'Caja general', 'responsable_id' => $cliente->id, 'valor' => '20.000']],
    ])->assertRedirect();

    $abonos = CarteraMovimiento::query()->where('abonos', '>', 0)->orderBy('abonos')->get();

    expect($abonos->pluck('concepto')->all())->toBe(['Recaudo por administración', 'Recaudo por islas'])
        ->and($abonos->pluck('origen')->all())->toBe([CarteraMovimiento::ORIGEN_RECAUDO_ADMIN, CarteraMovimiento::ORIGEN_RECAUDO_ISLAS])
        ->and($abonos->pluck('planillas')->unique()->all())->toBe(['8'])
        ->and($abonos->every(fn ($m) => $m->fecha->toDateString() === '2026-09-12'))->toBeTrue()
        ->and((float) $abonos->sum('abonos'))->toBe(50000.0)
        ->and((float) CarteraMovimiento::query()->where('customer_id', $cliente->id)->max('saldo'))->toBe(100000.0);

    $this->actingAs($admin)->get(route('cartera.index', [
        'customer_id' => $cliente->id,
        'fecha_inicial' => '2026-09-01',
        'fecha_final' => '2026-09-30',
    ]))->assertOk()->assertSee('Recaudo por islas')->assertSee('Recaudo por administración');

    expect($this->actingAs($admin)->get(route('cartera.index', [
        'customer_id' => $cliente->id,
        'fecha_inicial' => '2026-09-01',
        'fecha_final' => '2026-09-30',
    ]))->viewData('saldoFinal'))->toBe(50000.0);
});

test('el recaudo por administracion sigue en cartera cuando el islero guarda la planilla', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($admin, 9, '2026-09-12', [], [
        'recaudos_admin' => [['banco' => 'Caja', 'responsable_id' => $cliente->id, 'valor' => '40.000']],
    ]);
    guardarTurnoConCartera($islero, 9, '2026-09-12', [], [
        'recaudos_admin' => [],
    ]);

    $movimiento = CarteraMovimiento::sole();

    expect($movimiento->origen)->toBe(CarteraMovimiento::ORIGEN_RECAUDO_ADMIN)
        ->and((float) $movimiento->abonos)->toBe(40000.0);
});

test('un anticipo deja el saldo del cliente en negativo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($admin, 10, '2026-09-12', [], [
        'recaudos' => [['cliente_id' => $cliente->id, 'valor' => '200.000']],
    ]);

    $response = $this->actingAs($admin)->get(route('cartera.index', [
        'customer_id' => $cliente->id,
        'fecha_inicial' => '2026-09-01',
        'fecha_final' => '2026-09-30',
    ]));

    expect($response->viewData('saldoFinal'))->toBe(-200000.0)
        ->and((float) CarteraMovimiento::sole()->saldo)->toBe(-200000.0);

    $response->assertSee('-200.000');
});

test('el recaudo por administracion guardado muestra su cliente al volver a abrir la planilla', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($admin, 11, '2026-09-12', [], [
        'recaudos_admin' => [['banco' => 'Caja', 'responsable_id' => $cliente->id, 'valor' => '40.000']],
    ]);

    $html = $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 11]))
        ->assertOk()
        ->assertSee('name="recaudos_admin[0][responsable_id]"', false)
        ->assertDontSee('name="recaudos_admin[0][cliente_id]"', false)
        ->getContent();

    $tablaAdmin = str($html)->after('id="recaudos-admin-body"')->before('</tbody>')->toString();

    expect(preg_match('/<option value="'.$cliente->id.'"\s+selected>/', $tablaAdmin))->toBe(1);
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
        ->and($response->viewData('saldoAnterior'))->toBe(50000.0)
        ->and($response->viewData('saldoFinal'))->toBe(120000.0);

    $response->assertSee('ESTADO DE CUENTA')
        ->assertSee('SALDO ANTERIOR al 31/08/2026')
        ->assertSee('F-SEP')->assertDontSee('F-AGO')->assertDontSee('F-OTRO');
});

test('el candado del estado de cuenta indica si la planilla esta revisada', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($admin, 5, '2026-09-10', [
        ['cartera_factura_no' => 'F-5', 'cliente_id' => $cliente->id, 'cartera_valor' => '10.000'],
    ]);

    $url = route('cartera.index', ['customer_id' => $cliente->id, 'fecha_inicial' => '2026-09-01', 'fecha_final' => '2026-09-30']);

    $this->actingAs($admin)->get($url)
        ->assertSee('bi-unlock', false)
        ->assertDontSee('bi-lock-fill', false);

    $this->actingAs($admin)->post(route('turnos.revisar', Turno::firstOrFail()));

    $this->actingAs($admin)->get($url)
        ->assertSee('bi-lock-fill', false)
        ->assertSee('Planilla REVISADA por '.$admin->name);
});

test('guardar datos del vehiculo solo cambia placas, producto, galones, vr unitario y descuento', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($jefe, 7, '2026-09-10', [
        ['cartera_factura_no' => 'F-7', 'cliente_id' => $cliente->id, 'cartera_valor' => '200.000'],
    ], [
        'recaudos' => [['cliente_id' => $cliente->id, 'valor' => '50.000']],
    ]);
    $credito = CarteraMovimiento::where('origen', CarteraMovimiento::ORIGEN_CREDITO_DIRECTO)->firstOrFail();
    $recaudo = CarteraMovimiento::where('origen', CarteraMovimiento::ORIGEN_RECAUDO_ISLAS)->firstOrFail();

    $this->actingAs($jefe)->post(route('cartera.store'), [
        'customer_id' => $cliente->id,
        'detalles' => [
            [
                'id' => $credito->id,
                'fecha' => '2026-01-01',
                'factura' => 'CAMBIADA',
                'concepto' => 'Otro concepto',
                'abonos' => '150.000',
                'placas' => 'XYZ987',
                'producto' => 'ACPM',
                'galones' => '1.500,5',
                'vr_unitario' => '10.000',
                'descuento' => '5.000',
            ],
            ['id' => $recaudo->id, 'placas' => 'NO-APLICA', 'abonos' => '1'],
        ],
    ])->assertRedirect(route('cartera.index', ['customer_id' => $cliente->id, 'fecha_inicial' => null, 'fecha_final' => null]));

    $credito->refresh();
    $recaudo->refresh();

    expect($credito->factura)->toBe('F-7')
        ->and($credito->concepto)->toBe('Venta a crédito')
        ->and($credito->fecha->toDateString())->toBe('2026-09-10')
        ->and((float) $credito->abonos)->toBe(0.0)
        ->and($credito->placas)->toBe('XYZ987')
        ->and($credito->producto)->toBe('ACPM')
        ->and((float) $credito->galones)->toBe(1500.5)
        ->and((float) $credito->bruto)->toBe(15005000.0)
        ->and((float) $credito->descuento)->toBe(5000.0)
        ->and((float) $credito->vr_neto_cargo)->toBe(200000.0)
        ->and($recaudo->placas)->toBeEmpty()
        ->and((float) $recaudo->abonos)->toBe(50000.0)
        ->and(CarteraMovimiento::count())->toBe(2)
        ->and((float) CarteraMovimiento::orderByDesc('id')->first()->saldo)->toBe(150000.0);
});

test('el estado de cuenta se descarga en excel con saldo anterior, movimientos y saldo final', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    guardarTurnoConCartera($jefe, 1, '2026-08-30', [
        ['cartera_factura_no' => 'F-AGO', 'cliente_id' => $cliente->id, 'cartera_valor' => '50.000'],
    ]);
    guardarTurnoConCartera($jefe, 2, '2026-09-05', [
        ['cartera_factura_no' => 'F-SEP', 'cliente_id' => $cliente->id, 'cartera_valor' => '70.000'],
    ], [
        'recaudos' => [['cliente_id' => $cliente->id, 'valor' => '130.000']],
    ]);

    $response = $this->actingAs($jefe)->get(route('cartera.exportar', [
        'customer_id' => $cliente->id,
        'fecha_inicial' => '2026-09-01',
        'fecha_final' => '2026-09-30',
    ]));

    $response->assertOk()->assertDownload('estado-de-cuenta-cliente-a-2026-09-01-a-2026-09-30.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'estado').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    expect($hoja->getCell('J8')->getValue())->toBe('SALDO ANTERIOR al 31/08/2026')
        ->and((float) $hoja->getCell('M8')->getValue())->toBe(50000.0)
        ->and($hoja->getCell('C9')->getValue())->toBe('F-SEP')
        ->and($hoja->getCell('J11')->getValue())->toBe('TOTALES')
        ->and((float) $hoja->getCell('M11')->getValue())->toBe(-10000.0);
});

test('el islero no puede entrar al modulo de cartera', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $this->actingAs($islero)->get(route('cartera.index'))->assertForbidden();
    $this->actingAs($islero)->get(route('cartera.exportar'))->assertForbidden();
});
