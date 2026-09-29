<?php

use App\Models\Compra;
use App\Models\User;

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
