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
        ->toContain('#15')
        ->toContain('#17')
        ->toContain('15/09/2026')
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
