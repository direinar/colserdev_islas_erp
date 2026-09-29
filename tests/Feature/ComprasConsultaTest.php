<?php

use App\Models\Compra;
use App\Models\CompraLubricante;
use App\Models\Lubricant;
use App\Models\User;

function crearCompra(string $fecha, string $factura): Compra
{
    return Compra::create([
        'fecha' => $fecha,
        'factura' => $factura,
        'vr_total_fra' => 40000000,
        'gasolina' => 3000,
        'acpm' => 1000,
        'total' => 4000,
        'distribucion_gasolina' => 30000000,
        'distribucion_acpm' => 10000000,
    ]);
}

test('compras de combustible muestra las compras registradas entre las fechas desde y hasta', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearCompra('2026-08-30', 'AGOSTO');
    $compra = crearCompra('2026-09-25', 'SEPTIEMBRE');

    $this->actingAs($admin)
        ->get(route('compras.create', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertOk()
        ->assertSee('COMPRAS REGISTRADAS')
        ->assertSee('id="compra-'.$compra->id.'"', false)
        ->assertSee('>SEPTIEMBRE<', false)
        ->assertSee('>30.000.000<', false)
        ->assertSee(route('compras.edit', $compra), false)
        ->assertDontSee('>AGOSTO<', false);
});

test('una compra de combustible se corrige desde compras', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $compra = crearCompra('2026-09-25', '1006');

    $this->actingAs($admin)
        ->get(route('compras.edit', $compra))
        ->assertOk()
        ->assertSee('value="3000"', false);

    $this->actingAs($admin)
        ->put(route('compras.update', $compra), [
            'fecha' => '2026-09-26',
            'factura' => '1006',
            'gasolina' => '2.500',
            'distribucion_gasolina' => '25.000.000',
            'acpm' => '1000',
            'distribucion_acpm' => '10000000',
        ])
        ->assertRedirect(route('compras.create', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']));

    expect($compra->fresh())
        ->fecha->toDateString()->toBe('2026-09-26')
        ->gasolina->toEqual('2500.000')
        ->total->toEqual('3500.000')
        ->vr_total_fra->toEqual('35000000.00');
});

test('al corregir una compra no se permite usar la factura de otra compra', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearCompra('2026-09-25', '1006');
    $compra = crearCompra('2026-09-26', '1007');

    $this->actingAs($admin)
        ->from(route('compras.edit', $compra))
        ->put(route('compras.update', $compra), ['fecha' => '2026-09-26', 'factura' => '1006', 'gasolina' => '10'])
        ->assertRedirect(route('compras.edit', $compra))
        ->assertSessionHasErrors('factura');

    expect($compra->fresh()->factura)->toBe('1007');
});

test('una compra de combustible se elimina desde compras', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $compra = crearCompra('2026-09-25', '1006');

    $this->actingAs($admin)
        ->delete(route('compras.destroy', $compra))
        ->assertRedirect(route('compras.create', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']));

    expect(Compra::count())->toBe(0);
});

test('compras de lubricantes guarda el producto, lo lista y permite eliminar la compra', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Lubricant::create(['reference' => '3-UREA', 'sale_price' => 1700, 'iva' => 0, 'total' => 1700, 'cost_price' => 1500, 'active' => true]);

    $this->actingAs($admin)
        ->post(route('compras-lubricantes.store'), [
            'detalles' => [
                ['fecha' => '2026-09-20', 'producto' => '3-UREA', 'no_fc' => 'L-2', 'unidades' => '50', 'valor_unitario' => '1.500', 'iva' => '0'],
                ['fecha' => '2026-09-20', 'producto' => '', 'no_fc' => '', 'unidades' => '', 'valor_unitario' => '', 'iva' => ''],
            ],
        ])
        ->assertRedirect(route('compras-lubricantes.create'));

    $compra = CompraLubricante::sole();

    expect($compra->nombre)->toBe('3-UREA')
        ->and((float) $compra->vr_sin_iva)->toBe(75000.0);

    $this->actingAs($admin)
        ->get(route('compras-lubricantes.create', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertOk()
        ->assertSee('id="compra-'.$compra->id.'"', false)
        ->assertSee('>3-UREA<', false);

    $this->actingAs($admin)
        ->delete(route('compras-lubricantes.destroy', $compra))
        ->assertRedirect(route('compras-lubricantes.create', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']));

    expect(CompraLubricante::count())->toBe(0);
});
