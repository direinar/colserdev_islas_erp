<?php

use App\Models\LecturaElectronica;
use App\Models\Turno;
use App\Models\User;

/**
 * Turno con su precio y su venta según cierres de IAPROPIADA (galones y valor por combustible).
 */
function turnoLectura(string $fecha, int $numero, float $galonesCorriente, float $galonesAcpm): Turno
{
    return Turno::create([
        'fecha' => $fecha,
        'numero_turno' => $numero,
        'precio_corriente' => 16000,
        'precio_acpm' => 10000,
        'tirillas_galones_corriente' => $galonesCorriente,
        'tirillas_galones_acpm' => $galonesAcpm,
        'tirillas_valor_corriente' => $galonesCorriente * 16000,
        'tirillas_valor_acpm' => $galonesAcpm * 10000,
    ]);
}

function guardarLectura(User $user, string $fecha, array $finales, array $iniciales = [])
{
    return test()->actingAs($user)->post(route('lectura-electronica.guardar'), [
        'fecha' => $fecha,
        'iniciales' => $iniciales,
        'finales' => $finales,
    ]);
}

test('sin días registrados, la lectura inicial es la última que quedó en las planillas de turno y no se edita', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $turno = turnoLectura('2026-10-07', 40, 0, 0);
    $turno->surtidores()->create(['manguera' => 'PLUS 01', 'combustible' => 'corriente', 'lectura_inicial' => 0, 'lectura_final' => 1500, 'galones' => 1500]);

    $pantalla = $this->actingAs($jefe)->get(route('lectura-electronica.index', ['fecha' => '2026-10-22']))
        ->assertOk()
        ->assertSee('LECTURA ELECTRÓNICA — 22/10/2026')
        ->assertSee('COPIAR SALDOS INICIALES A FINALES')
        ->assertSee('SOBRANTE O FALTANTE x LECTURA SURTIDORES')
        ->assertSee('le-resumen le-resumen-campos', false)
        ->assertSee('VENTAS SEGÚN LECTURA ELECTRÓNICA')
        // Las letras A, B y C solo servían para identificar las tablas en la solicitud.
        ->assertDontSee('(A)')->assertDontSee('(B)')->assertDontSee('(C)');

    expect($pantalla->getContent())
        ->toMatch('/name="iniciales\[PLUS 01\]"[^>]*value="1,500.000"\s+readonly/')
        // Una manguera sin lectura anterior se digita.
        ->not->toMatch('/name="iniciales\[PLUS 02\]"[^>]*readonly/');
});

test('el informe del día compara la venta según lecturas con los cierres de IAPROPIADA de sus turnos', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    turnoLectura('2026-10-22', 41, 60, 20);
    turnoLectura('2026-10-22', 42, 30, 0);
    turnoLectura('2026-10-23', 43, 999, 999);

    guardarLectura($admin, '2026-10-22',
        ['PLUS 01' => '1,100.000', 'ACPM 12' => '515.5'],
        ['PLUS 01' => '1,000.000', 'ACPM 12' => '500'],
    )->assertRedirect(route('lectura-electronica.index', ['fecha' => '2026-10-22']));

    $informe = $this->actingAs($admin)->get(route('lectura-electronica.index', ['fecha' => '2026-10-22']));

    // Ventas según lectura electrónica: 100 galones de corriente y 15,5 de ACPM al precio del último turno del día.
    expect($informe->viewData('lecturas'))->toBe([
        'corriente' => ['galones' => 100.0, 'valor' => 1600000.0],
        'acpm' => ['galones' => 15.5, 'valor' => 155000.0],
        'total' => ['galones' => 115.5, 'valor' => 1755000.0],
    ])
        // Ventas según cierres de IAPROPIADA: suma de los turnos de la fecha, sin los de otro día.
        ->and($informe->viewData('iapropiada')['total'])->toBe(['galones' => 110.0, 'valor' => 1640000.0])
        // Sobrante o faltante = lectura - IAPROPIADA: sobrante en corriente, faltante en ACPM.
        ->and($informe->viewData('diferencia'))->toBe([
            'corriente' => ['galones' => 10.0, 'valor' => 160000.0],
            'acpm' => ['galones' => -4.5, 'valor' => -45000.0],
            'total' => ['galones' => 5.5, 'valor' => 115000.0],
        ]);

    $informe->assertSee('<td class="le-negativo">-45.000</td>', false);
});

test('el día siguiente arranca con la final del día anterior y corregir esa final actualiza el día siguiente', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);

    guardarLectura($jefe, '2026-10-22', ['PLUS 09' => '1500'], ['PLUS 09' => '1000']);
    // Aunque el navegador envíe otra inicial, se toma la final del día anterior.
    guardarLectura($jefe, '2026-10-23', ['PLUS 09' => '1700'], ['PLUS 09' => '0']);

    $siguiente = fn () => LecturaElectronica::query()->whereDate('fecha', '2026-10-23')->where('manguera', 'PLUS 09')->sole();

    expect((float) $siguiente()->lectura_inicial)->toBe(1500.0)
        ->and((float) $siguiente()->galones)->toBe(200.0);

    guardarLectura($jefe, '2026-10-22', ['PLUS 09' => '1550'])->assertSessionHasNoErrors()->assertRedirect();

    expect((float) $siguiente()->lectura_inicial)->toBe(1550.0)
        ->and((float) $siguiente()->galones)->toBe(150.0)
        ->and(LecturaElectronica::query()->count())->toBe(24);
});

test('el islero no entra a la lectura electrónica', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $this->actingAs($islero)->get(route('lectura-electronica.index'))->assertForbidden();
    guardarLectura($islero, '2026-10-22', ['PLUS 01' => '1'])->assertForbidden();
});
