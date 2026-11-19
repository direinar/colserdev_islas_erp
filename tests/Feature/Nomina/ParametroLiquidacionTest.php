<?php

use App\Models\Nomina\ParametroLiquidacion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Nomina\ParametroLiquidacionFactory;

test('crea una vigencia y calcula el valor de la hora y de los recargos', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $datos = array_merge((new ParametroLiquidacionFactory)->definition(), [
        'salario_minimo' => '1.750.905',
        'auxilio_transporte' => '249.095',
    ]);

    $this->actingAs($admin)->post(route('nomina.parametros-liquidacion.store'), $datos)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $parametro = ParametroLiquidacion::sole();

    expect((float) $parametro->salario_minimo)->toBe(1750905.0)
        ->and(round($parametro->valorHoraOrdinaria()))->toBe(7959.0)
        ->and(round($parametro->valorHoraRecargoNocturno()))->toBe(2786.0)
        ->and(round($parametro->valorHoraRecargoFestivo()))->toBe(6367.0);
});

test('no permite dos vigencias que empiecen el mismo dia', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    ParametroLiquidacion::factory()->create();

    $this->actingAs($admin)->post(route('nomina.parametros-liquidacion.store'), (new ParametroLiquidacionFactory)->definition())
        ->assertSessionHasErrors('vigente_desde');
});

test('la nueva vigencia se precarga con los valores de la mas reciente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    ParametroLiquidacion::factory()->create(['salario_minimo' => 1750905]);

    $this->actingAs($admin)->get(route('nomina.parametros-liquidacion.create'))
        ->assertOk()
        ->assertSee('value="1.750.905"', false);
});

test('toma la vigencia que aplica en cada fecha', function () {
    ParametroLiquidacion::factory()->create(['vigente_desde' => '2026-01-01']);
    $julio = ParametroLiquidacion::factory()->create(['vigente_desde' => '2026-07-01', 'porcentaje_recargo_festivo' => 90]);

    expect(ParametroLiquidacion::vigenteEn(CarbonImmutable::parse('2026-06-30'))->vigente_desde->toDateString())->toBe('2026-01-01')
        ->and(ParametroLiquidacion::vigenteEn(CarbonImmutable::parse('2026-07-01'))->is($julio))->toBeTrue()
        ->and(ParametroLiquidacion::vigenteEn(CarbonImmutable::parse('2025-12-31')))->toBeNull();
});

test('muestra el listado, la ficha y el formulario de edicion de la vigencia', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $parametro = ParametroLiquidacion::factory()->create(['vigente_desde' => now()->startOfYear()->toDateString()]);

    $this->actingAs($admin)->get(route('nomina.parametros-liquidacion.index'))->assertOk()->assertSee('Vigente');
    $this->actingAs($admin)->get(route('nomina.parametros-liquidacion.show', $parametro))->assertOk()->assertSee('$ 7.959');
    $this->actingAs($admin)->get(route('nomina.parametros-liquidacion.edit', $parametro))->assertOk()->assertSee('value="2.436"', false);
});
