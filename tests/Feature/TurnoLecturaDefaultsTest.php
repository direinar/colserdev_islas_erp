<?php

use App\Models\Lubricant;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\TurnoVentasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('traditional form preloads previous final readings as initial values', function () {
    $today = now()->toDateString();

    $previousTurno = Turno::create([
        'fecha' => $today,
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana',
        'precio_corriente' => 16.5,
        'precio_acpm' => 9.85,
    ]);

    $previousTurno->surtidores()->createMany([
        ['manguera' => 'PLUS 01', 'combustible' => 'corriente', 'lectura_inicial' => 0, 'lectura_final' => 1500, 'galones' => 1500],
        ['manguera' => 'PLUS 02', 'combustible' => 'corriente', 'lectura_inicial' => 0, 'lectura_final' => 1250, 'galones' => 1250],
        ['manguera' => 'ACPM 03', 'combustible' => 'acpm', 'lectura_inicial' => 0, 'lectura_final' => 980, 'galones' => 980],
        ['manguera' => 'ACPM 04', 'combustible' => 'acpm', 'lectura_inicial' => 0, 'lectura_final' => 1015, 'galones' => 1015],
        ['manguera' => 'PLUS 05', 'combustible' => 'corriente', 'lectura_inicial' => 0, 'lectura_final' => 1100, 'galones' => 1100],
        ['manguera' => 'PLUS 06', 'combustible' => 'corriente', 'lectura_inicial' => 0, 'lectura_final' => 1205, 'galones' => 1205],
        ['manguera' => 'ACPM 07', 'combustible' => 'acpm', 'lectura_inicial' => 0, 'lectura_final' => 805, 'galones' => 805],
        ['manguera' => 'ACPM 08', 'combustible' => 'acpm', 'lectura_inicial' => 0, 'lectura_final' => 910, 'galones' => 910],
        ['manguera' => 'PLUS 09', 'combustible' => 'corriente', 'lectura_inicial' => 0, 'lectura_final' => 1300, 'galones' => 1300],
        ['manguera' => 'PLUS 10', 'combustible' => 'corriente', 'lectura_inicial' => 0, 'lectura_final' => 1410, 'galones' => 1410],
        ['manguera' => 'ACPM 11', 'combustible' => 'acpm', 'lectura_inicial' => 0, 'lectura_final' => 875, 'galones' => 875],
        ['manguera' => 'ACPM 12', 'combustible' => 'acpm', 'lectura_inicial' => 0, 'lectura_final' => 930, 'galones' => 930],
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('turnos.create', ['fecha' => $today]));

    $response->assertOk();
    // Values should appear in the HTML, formatted with the file's convention: decimal '.', thousands ','
    $response->assertSee('value="1,500.000"', false);
    $response->assertSee('value="1,250.000"', false);
    $response->assertSee('value="980.000"', false);
    $response->assertSee('value="1,015.000"', false);
    $response->assertSee('value="1,100.000"', false);
    $response->assertSee('value="1,205.000"', false);
    $response->assertSee('value="805.000"', false);
    $response->assertSee('value="910.000"', false);
    $response->assertSee('value="1,300.000"', false);
    $response->assertSee('value="1,410.000"', false);
    $response->assertSee('value="875.000"', false);
    $response->assertSee('value="930.000"', false);
});

test('lectura inicial is locked from turno 2 onward', function () {
    $today = now()->toDateString();

    $previousTurno = Turno::create([
        'fecha' => $today,
        'numero_turno' => 1,
        'nombre_vendedor' => 'Ana',
        'precio_corriente' => 16.5,
        'precio_acpm' => 9.85,
    ]);

    $previousTurno->surtidores()->create([
        'manguera' => 'PLUS 01',
        'combustible' => 'corriente',
        'lectura_inicial' => 0,
        'lectura_final' => 1500,
        'galones' => 1500,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('turnos.create', ['fecha' => $today]));

    $response->assertOk();
    $response->assertSee('lectura-inicial', false);
    $response->assertSee('readonly', false);
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
    $response->assertSee('Guardar Turno', false);
    $response->assertSee('value="Ana"', false); // confirma que el turno SÍ fue encontrado (whereDate)
    $response->assertDontSee('<form method="POST" action="'.route('turnos.revisar', $turno).'" class="mt-2">', false);

    $mainFormOpen = strpos($response->getContent(), '<form method="POST" action="'.route('turnos.store').'">');
    $mainFormClose = strpos($response->getContent(), 'Guardar Turno');
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
    expect(Turno::first()->nombre_vendedor)->toBe('Ana Actualizada');
    expect(Turno::first()->id)->toBe($turno->id);
});

test('save button is hidden for non admins when turno is revisado', function () {
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

    $vendedor = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $response = $this->actingAs($vendedor)->get(route('turnos.create', [
        'fecha' => $today,
        'numero_turno' => $turno->numero_turno,
    ]));

    $response->assertOk();
    $response->assertDontSee('Guardar Turno', false);
    $response->assertSee('Planilla revisada', false);
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

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('turnos.create', [
        'fecha' => $today,
        'numero_turno' => $turno->numero_turno,
    ]));

    $response->assertOk();
    $response->assertSee('value="10"', false);
    $response->assertSee('MOBIL SUPER 20W50', false);
    $response->assertSee('value="250,000"', false);
    $response->assertSee('value="47,500"', false);
    $response->assertSee('value="297,500"', false);
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
    $response->assertSee('+ Agregar fila', false);
    $response->assertSee('remove-row', false);
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

test('search datalist lists the turno numbers already registered for the queried date', function () {
    $today = now()->toDateString();

    Turno::create(['fecha' => $today, 'numero_turno' => 1, 'nombre_vendedor' => 'Ana']);
    Turno::create(['fecha' => $today, 'numero_turno' => 2, 'nombre_vendedor' => 'Beto']);
    Turno::create(['fecha' => $today, 'numero_turno' => 3, 'nombre_vendedor' => 'Caro']);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('turnos.create', ['fecha' => $today]));

    $response->assertOk();
    $response->assertSee('id="turnos-del-dia"', false);
    $response->assertSee('<option value="1">Turno 1</option>', false);
    $response->assertSee('<option value="2">Turno 2</option>', false);
    $response->assertSee('<option value="3">Turno 3</option>', false);
});

test('search still finds a turno by typing just the numero_turno', function () {
    $today = now()->toDateString();

    $turno = Turno::create(['fecha' => $today, 'numero_turno' => 4, 'nombre_vendedor' => 'Dana']);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('turnos.create', [
        'fecha' => $today,
        'numero_turno' => 4,
    ]));

    $response->assertOk();
    $response->assertSee('value="Dana"', false);
});
