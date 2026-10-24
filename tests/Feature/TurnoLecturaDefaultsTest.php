<?php

use App\Models\Customer;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\TurnoVentasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('la planilla ya no tiene la lectura electronica: se registra por fecha en su propio informe', function () {
    $previousTurno = Turno::create(['fecha' => now()->toDateString(), 'numero_turno' => 1, 'precio_corriente' => 16500, 'precio_acpm' => 9850]);
    $previousTurno->surtidores()->create(['manguera' => 'PLUS 01', 'combustible' => 'corriente', 'lectura_inicial' => 0, 'lectura_final' => 1500, 'galones' => 1500]);

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('turnos.create'))
        ->assertOk()
        ->assertDontSee('LECTURA ELECTRÓNICA')
        ->assertDontSee('lectura-inicial', false)
        ->assertDontSee('VENTAS/SURTIDORES')
        ->assertDontSee('SOBRANTE o FALTANTE x LECTURA SURTIDORES')
        ->assertSee('SOBRANTE o FALTANTE SEGUN CIERRES DE IAPROPIADA')
        ->assertSee('PRECIOS ACTUALES');
});

test('save button form is not broken by a nested form and stays enabled while not revisado', function () {
    $today = now()->toDateString();

    $turno = Turno::create([
        'fecha' => $today,
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana',
        'precio_corriente' => 16.5,
        'precio_acpm' => 9.85,
        'revisado' => false,
    ]);

    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $response = $this->actingAs($admin)->get(route('turnos.create', [
        'fecha' => $today,
        'numero_turno' => $turno->numero_turno,
    ]));

    $response->assertOk();
    $response->assertSee('<button type="submit" form="turno-form"', false);
    expect($response->viewData('turno')?->id)->toBe($turno->id); // confirma que el turno SÍ fue encontrado (whereDate)
    $response->assertDontSee('<form method="POST" action="'.route('turnos.revisar', $turno).'" class="mt-2">', false);

    $mainFormOpen = strpos($response->getContent(), '<form method="POST" action="'.route('turnos.store').'" id="turno-form">');
    $mainFormClose = strpos($response->getContent(), '</form>', $mainFormOpen ?: 0);
    expect($mainFormOpen)->not->toBeFalse();
    expect($mainFormClose)->toBeGreaterThan($mainFormOpen);
});

test('saving an already existing turno updates it instead of creating a duplicate', function () {
    $today = now()->toDateString();

    $turno = Turno::create([
        'fecha' => $today,
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana',
        'precio_corriente' => 16.5,
        'precio_acpm' => 9.85,
    ]);

    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $response = $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => $today,
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana Actualizada',
        'ventas' => [],
        'lecturas' => [],
    ]);

    $response->assertRedirect();
    expect(Turno::count())->toBe(1);
    // El vendedor es quien registró la planilla: si el administrador la corrige, no se reemplaza.
    expect(Turno::first()->nombre_vendedor)->toBe('Ana');
    expect(Turno::first()->id)->toBe($turno->id);
});

test('save button is hidden when turno is revisado', function () {
    $today = now()->toDateString();

    $turno = Turno::create([
        'fecha' => $today,
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana',
        'precio_corriente' => 16.5,
        'precio_acpm' => 9.85,
        'revisado' => true,
        'revisado_por' => 'Otro Admin',
    ]);

    $vendedor = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $response = $this->actingAs($vendedor)->get(route('turnos.create', [
        'fecha' => $today,
        'numero_turno' => $turno->numero_turno,
    ]));

    $response->assertOk();
    $response->assertDontSee('<button type="submit" form="turno-form"', false);
    $response->assertSee('PLANILLA REVISADA.', false);
});

test('lubricantes table shows previously saved rows when consulting an existing turno', function () {
    $today = now()->toDateString();

    Lubricant::create([
        'reference' => 'MOBIL SUPER 20W50',
        'sale_price' => 25000,
        'iva' => 4750,
        'total' => 29750,
        'cost_price' => 20000,
        'active' => true,
    ]);

    $turno = Turno::create([
        'fecha' => $today,
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana',
        'precio_corriente' => 16.5,
        'precio_acpm' => 9.85,
    ]);

    $turno->lubricantes()->create([
        'cantidad' => 10,
        'producto' => 'MOBIL SUPER 20W50',
        'valor_sin_iva' => 250000,
        'iva' => 47500,
        'total' => 297500,
    ]);

    $user = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $response = $this->actingAs($user)->get(route('turnos.create', [
        'fecha' => $today,
        'numero_turno' => $turno->numero_turno,
    ]));

    $response->assertOk();
    $response->assertSee('value="10"', false);
    $response->assertSee('MOBIL SUPER 20W50', false);
    // Cantidad para 4 dígitos y valores para 8: el resto del ancho es para el nombre del producto.
    $response->assertSeeInOrder(['<th class="col-cantidad">', '<th>PRODUCTO</th>', '<th class="text-end col-valor">VR. SIN IVA</th>', '<th class="text-end col-valor">IVA</th>', '<th class="text-end col-valor">TOTAL</th>', '<th class="text-center col-accion">ACCIÓN</th>'], false);
    $response->assertSeeInOrder(['.lubricantes-table .col-cantidad {', 'width: 64px;', '.lubricantes-table .col-valor {', 'width: 100px;'], false);
    $response->assertSee('value="250.000"', false);
    $response->assertSee('value="47.500"', false);
    $response->assertSee('value="297.500"', false);
});

test('urea_lubricantes sent with dot-thousands money format are not truncated on save', function () {
    $today = now()->toDateString();

    Lubricant::create([
        'reference' => 'MOBIL SUPER 20W50',
        'sale_price' => 250000,
        'iva' => 47500,
        'total' => 297500,
        'cost_price' => 200000,
        'active' => true,
    ]);

    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $response = $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => $today,
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana',
        'urea_lubricantes' => [
            [
                'cantidad' => 1,
                'producto' => 'MOBIL SUPER 20W50',
                'valor_sin_iva' => '250.000',
                'iva' => '47.500',
                'total' => '297.500',
            ],
        ],
        'ventas' => [],
        'lecturas' => [],
    ]);

    $response->assertRedirect();

    $lubricante = Turno::first()->lubricantes()->firstOrFail();
    expect((float) $lubricante->valor_sin_iva)->toBe(250000.0);
    expect((float) $lubricante->iva)->toBe(47500.0);
    expect((float) $lubricante->total)->toBe(297500.0);
});

test('medios de pago renders as three independent tables with add row actions', function () {
    $today = now()->toDateString();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('turnos.create', ['fecha' => $today]));

    $response->assertOk();
    $response->assertSee('INFORMACION DE MEDIOS DE PAGO', false);
    $response->assertSee('CONSIGNACIONES', false);
    $response->assertSee('DESCUENTOS', false);
    $response->assertSee('CARTERA - CRÉDITO DIRECTO', false);
    // Pagos electrónicos va a la izquierda y cartera a la derecha.
    $response->assertSeeInOrder(['DESCUENTOS', 'PAGOS ELECTRONICOS Y TRANSFERENCIAS', 'CARTERA - CRÉDITO DIRECTO'], false);
    // Pagos electrónicos: el valor exige el concepto, y las filas nuevas no traen uno preseleccionado.
    $response->assertSeeInOrder(["valor: '.qr-valor'", "cliente: '.qr-concepto'", "campo: 'concepto'"], false);
    $response->assertSee('class="form-select form-select-sm border-0 bg-transparent qr-concepto">
                        <option value="" selected>Seleccione concepto</option>', false);
    $response->assertSee('+ AGREGAR FILA', false);
    $response->assertSee('remove-row', false);
});

test('independent payment tables persist rows and their totals', function () {
    $today = now()->toDateString();
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    $response = $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => $today,
        'numero_turno' => 1,
        'consignaciones' => [
            ['consignacion_no' => 'C-1', 'consignacion_valor' => '100.000'],
            ['consignacion_no' => 'C-2', 'consignacion_valor' => '50.000'],
        ],
        'descuentos' => [
            ['descuento_no' => '1', 'descuento' => '20.000'],
            ['descuento_no' => '2', 'descuento' => '5.000'],
        ],
        'cartera' => [
            ['cartera_factura_no' => 'F-1', 'cliente_id' => $cliente->id, 'cartera_valor' => '300.000'],
            ['cartera_factura_no' => 'F-2', 'cliente_id' => $cliente->id, 'cartera_valor' => '25.000'],
        ],
    ]);

    $response->assertRedirect();
    $turno = Turno::firstOrFail();

    expect($turno->consignaciones)->toHaveCount(2)
        ->and($turno->consignaciones->sum(fn ($row) => (float) $row->total))->toBe(300000.0)
        ->and($turno->descuentos)->toHaveCount(2)
        ->and($turno->descuentos->sum(fn ($row) => (float) $row->total))->toBe(50000.0)
        ->and($turno->cartera)->toHaveCount(2)
        ->and($turno->cartera->sum(fn ($row) => (float) $row->total))->toBe(650000.0);
});

test('ventas galones sent with a dot decimal (blade default format) are not inflated 1000x on save', function () {
    $today = now()->toDateString();

    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $response = $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => $today,
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana',
        'ventas' => [
            ['surtidor' => 'SURTIDOR 1 CTE', 'combustible' => 'CTE', 'galones' => '4.760'],
            ['surtidor' => 'SURTIDOR 2 CTE', 'combustible' => 'CTE', 'galones' => '100.000'],
            ['surtidor' => 'SURTIDOR 1 ACPM', 'combustible' => 'ACPM', 'galones' => '1.110'],
        ],
        'lecturas' => [],
    ]);

    $response->assertRedirect();

    $turno = Turno::first();
    expect((float) $turno->ventas()->where('surtidor', 'SURTIDOR 1 CTE')->first()->galones)->toBe(4.76);
    expect((float) $turno->ventas()->where('surtidor', 'SURTIDOR 2 CTE')->first()->galones)->toBe(100.0);
    expect((float) $turno->ventas()->where('surtidor', 'SURTIDOR 1 ACPM')->first()->galones)->toBe(1.11);
});

test('turno ventas seeder loads the default surtidores for the sales form', function () {
    Artisan::call('db:seed', ['--class' => TurnoVentasSeeder::class]);

    $turno = Turno::query()->firstOrFail();

    expect($turno->ventas()->count())->toBe(6)
        ->and($turno->ventas()->pluck('surtidor')->all())->toBe([
            'SURTIDOR 1 CTE',
            'SURTIDOR 1 ACPM',
            'SURTIDOR 2 CTE',
            'SURTIDOR 2 ACPM',
            'SURTIDOR 3 ACPM',
            'SURTIDOR 3 CTE',
        ]);
});

test('search still finds a turno by typing just the numero_turno', function () {
    $today = now()->toDateString();

    $turno = Turno::create(['fecha' => $today, 'numero_turno' => 4, 'nombre_vendedor' => 'Dana']);

    $user = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $response = $this->actingAs($user)->get(route('turnos.create', ['turno_busqueda' => 4]));

    $response->assertOk();
    $response->assertSee('value="004"', false);
    // Fecha y turno se resaltan en rojo y más grandes.
    $response->assertSee('id="turno-fecha" class="form-control form-control-sm d-inline-block w-auto dato-turno-destacado"', false);
    $response->assertSee('id="turno-numero" class="form-control form-control-sm d-inline-block dato-turno-destacado"', false);
    // Debe ganarle al color con !important de input.form-control en custom.css.
    $response->assertSeeInOrder(['input.form-control.dato-turno-destacado {', 'color: #d40000 !important;'], false);
    $response->assertSee('value="'.$today.'"', false);
});
