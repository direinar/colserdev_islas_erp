<?php

use App\Models\Compra;
use App\Models\Turno;
use App\Models\User;

test('el inventario de gasolina conserva enteros y decimales de la compra sin forzar cero fijo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    Compra::create([
        'fecha' => '2026-09-15',
        'factura' => 'F-100',
        'vr_total_fra' => 3000,
        'gasolina' => 30,
        'acpm' => 12.5,
        'total' => 42.5,
        'distribucion_gasolina' => 2400,
        'distribucion_acpm' => 600,
    ]);

    Compra::create([
        'fecha' => '2026-09-16',
        'factura' => 'F-101',
        'vr_total_fra' => 2800,
        'gasolina' => 16.25,
        'acpm' => 9,
        'total' => 25.25,
        'distribucion_gasolina' => 1800,
        'distribucion_acpm' => 1000,
    ]);

    $turno = Turno::create([
        'fecha' => '2026-09-15',
        'numero_turno' => 15,
        'lecturas_galones_corriente' => 10.5,
    ]);

    Turno::create([
        'fecha' => '2026-09-15',
        'numero_turno' => 17,
        'lecturas_galones_corriente' => 7,
    ]);

    expect(Turno::whereDate('fecha', '2026-09-15')->count())->toBe(2);

    $response = $this->actingAs($admin)
        ->get(route('inventarios-gasolina.create'))
        ->assertOk();

    $html = $response->getContent();
    $rows = $response->viewData('rows');

    expect($html)
        ->toContain('value="30"')
        ->toContain('value="16.25"')
        ->toContain(route('turnos.create', ['turno_busqueda' => 15]))
        ->toContain('>15</a>')
        ->toContain('>17</a>')
        ->not->toContain('(15/09/2026)')
        ->not->toContain('value="30.000"')
        ->not->toContain('value="16.250"');

    expect($rows)->toHaveCount(4)
        ->and($rows[1]['numero_turno'])->toBe(15)
        ->and($rows[1]['salidas_galones'])->toBe('10,5')
        ->and($rows[2]['numero_turno'])->toBe(17)
        ->and($rows[2]['salidas_galones'])->toBe('7');

    $turnoResponse = $this->actingAs($admin)
        ->get(route('turnos.create', ['turno_busqueda' => $turno->numero_turno]));

    expect($turnoResponse->viewData('turno')->id)->toBe($turno->id);
});

test('guardar compras no devuelve las filas al formulario para evitar que se guarden dos veces', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)
        ->post(route('compras.store'), [
            'compras' => [
                ['fecha' => '2026-09-20', 'factura' => '', 'gasolina' => '10', 'acpm' => '0', 'distribucion_gasolina' => '1000'],
            ],
        ])
        ->assertRedirect(route('compras.create'))
        ->assertSessionHas('success');

    expect(Compra::whereDate('fecha', '2026-09-20')->count())->toBe(1);

    $this->actingAs($admin)
        ->get(route('compras.create'))
        ->assertOk()
        ->assertDontSee('value="1000"', false);
});

test('una fila de compra que solo trae la fecha no se guarda', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)
        ->post(route('compras.store'), [
            'compras' => [
                ['fecha' => '2026-09-21', 'factura' => '', 'gasolina' => '', 'acpm' => '', 'distribucion_gasolina' => '', 'distribucion_acpm' => '', 'vr_total_fra' => '0', 'total' => '0'],
                ['fecha' => '2026-09-21', 'factura' => 'F-300', 'gasolina' => '5', 'acpm' => '0', 'distribucion_gasolina' => '500'],
            ],
        ])
        ->assertRedirect(route('compras.create'));

    expect(Compra::whereDate('fecha', '2026-09-21')->pluck('factura')->all())->toBe(['F-300']);
});
