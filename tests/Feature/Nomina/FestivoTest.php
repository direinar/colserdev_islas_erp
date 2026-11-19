<?php

use App\Models\Nomina\Festivo;
use App\Models\User;
use App\Services\Nomina\FestivosColombiaService;

test('calcula los festivos de Colombia con los traslados al lunes y la Semana Santa', function () {
    $festivos = app(FestivosColombiaService::class)->festivosDelAnio(2026);

    expect($festivos)->toHaveCount(18)
        ->and(array_keys($festivos))->toBe([
            '2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03', '2026-05-01',
            '2026-05-18', '2026-06-08', '2026-06-15', '2026-06-29', '2026-07-20', '2026-08-07',
            '2026-08-17', '2026-10-12', '2026-11-02', '2026-11-16', '2026-12-08', '2026-12-25',
        ]);
});

test('cargar los festivos del ano no duplica los que ya existen', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Festivo::create(['fecha' => '2026-01-01', 'nombre' => 'Año Nuevo']);

    $this->actingAs($admin)->post(route('nomina.festivos.generar'), ['anio' => 2026])
        ->assertRedirect(route('nomina.festivos.index', ['anio' => 2026]))
        ->assertSessionHas('success', 'Se cargaron 17 festivos de 2026.');

    $this->actingAs($admin)->post(route('nomina.festivos.generar'), ['anio' => 2026]);

    expect(Festivo::whereYear('fecha', 2026)->count())->toBe(18);
});

test('no permite registrar dos veces la misma fecha festiva', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Festivo::create(['fecha' => '2026-12-31', 'nombre' => 'Fin de año']);

    $this->actingAs($admin)->post(route('nomina.festivos.store'), ['fecha' => '2026-12-31', 'nombre' => 'Otro'])
        ->assertSessionHasErrors('fecha');
});

test('lista los festivos del ano seleccionado', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Festivo::create(['fecha' => '2026-12-25', 'nombre' => 'Navidad']);
    Festivo::create(['fecha' => '2025-12-25', 'nombre' => 'Navidad pasada']);

    $this->actingAs($admin)->get(route('nomina.festivos.index', ['anio' => 2026]))
        ->assertOk()
        ->assertSee('25/12/2026')
        ->assertDontSee('Navidad pasada');
});
