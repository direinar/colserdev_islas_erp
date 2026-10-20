<?php

use App\Models\Turno;
use App\Models\User;
use App\Services\DashboardService;

/**
 * @param  array<string, mixed>  $atributos
 */
function turnoDashboard(array $atributos): Turno
{
    return Turno::create($atributos + [
        'nombre_vendedor' => 'Ana',
        'tirillas_valor_corriente' => 0,
        'tirillas_valor_acpm' => 0,
        'tirillas_galones_corriente' => 0,
        'tirillas_galones_acpm' => 0,
    ]);
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('el gerente, el super administrador y el administrador ven el dashboard; el jefe de patios y el islero van a la planilla', function (string $role, bool $veDashboard) {
    $response = $this->actingAs(User::factory()->create(['role' => $role]))->get(route('dashboard'));

    $veDashboard ? $response->assertOk() : $response->assertRedirect(route('turnos.create'));
})->with([
    [User::ROLE_SUPERADMIN, true],
    [User::ROLE_GERENTE, true],
    [User::ROLE_ADMINISTRADOR, true],
    [User::ROLE_JEFE_PATIOS, false],
    [User::ROLE_ISLERO, false],
]);

test('el dashboard resume las planillas de turnos del rango', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $primero = turnoDashboard([
        'fecha' => '2026-10-01', 'numero_turno' => 1, 'user_id' => $admin->id,
        'tirillas_valor_corriente' => 100000, 'tirillas_valor_acpm' => 50000,
        'tirillas_galones_corriente' => 6.5, 'tirillas_galones_acpm' => 3.25,
        'sobrante_pendiente' => 0, 'revisado' => true,
    ]);
    $primero->lubricantes()->create(['producto' => 'UREA', 'cantidad' => 2, 'valor_sin_iva' => 8000, 'iva' => 0, 'total' => 8000]);
    $primero->consignaciones()->create(['consignacion_no' => 'C-1', 'valor' => 120000]);
    $primero->qrPagos()->create(['concepto' => 'QR', 'valor' => 38000]);

    turnoDashboard([
        'fecha' => '2026-10-03', 'numero_turno' => 2, 'user_id' => $admin->id,
        'tirillas_valor_corriente' => 20000, 'tirillas_galones_corriente' => 1.3,
        'sobrante_pendiente' => -5000, 'revisado' => false,
    ]);
    // Fuera del rango: no cuenta.
    turnoDashboard(['fecha' => '2026-09-30', 'numero_turno' => 3, 'tirillas_valor_corriente' => 999999]);

    $resumen = app(DashboardService::class)->resumen('2026-10-01', '2026-10-03', $admin);

    expect($resumen['kpis'])->toMatchArray([
        'ventas' => 178000.0,
        'combustible' => 170000.0,
        'canastilla' => 8000.0,
        'galones_corriente' => 7.8,
        'galones_acpm' => 3.25,
        'turnos' => 2,
        'pendientes_revision' => 1,
        'descuadrados' => 1,
    ])
        ->and($resumen['agrupacion'])->toBe('dia')
        // Un punto por día, incluido el día sin planillas.
        ->and(array_column($resumen['periodos'], 'etiqueta'))->toBe(['01/10', '02/10', '03/10'])
        ->and(array_column($resumen['periodos'], 'gasolina'))->toBe([100000.0, 0.0, 20000.0])
        ->and(array_column($resumen['periodos'], 'canastilla'))->toBe([8000.0, 0.0, 0.0])
        ->and($resumen['formas_pago'])->toBe([
            ['etiqueta' => 'Consignaciones', 'valor' => 120000.0],
            ['etiqueta' => 'Pagos electrónicos', 'valor' => 38000.0],
        ])
        ->and($resumen['productos'])->toBe([['producto' => 'UREA', 'cantidad' => 2.0, 'total' => 8000.0]])
        ->and($resumen['ultimos']->pluck('numero_turno')->all())->toBe([2, 1]);

    // Galones vendidos por día: corriente, ACPM y la línea verde del total.
    $this->actingAs($admin)
        ->get(route('dashboard', ['desde' => '2026-10-01', 'hasta' => '2026-10-03']))
        ->assertOk()
        ->assertSee("linea('Total', periodos.map((p) => Math.round((p.galones_corriente + p.galones_acpm) * 1000) / 1000), color.totalGalones)", false)
        ->assertSee("totalGalones: '#15803d'", false);
});

test('los rangos largos se agrupan por mes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $resumen = app(DashboardService::class)->resumen('2026-01-15', '2026-04-10', $admin);

    expect($resumen['agrupacion'])->toBe('mes')
        ->and($resumen['periodos'])->toHaveCount(4);
});

test('el islero solo ve en el dashboard sus propias planillas', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $otro = User::factory()->create(['role' => User::ROLE_ISLERO]);

    turnoDashboard(['fecha' => '2026-10-01', 'numero_turno' => 1, 'user_id' => $islero->id, 'tirillas_valor_corriente' => 1000]);
    turnoDashboard(['fecha' => '2026-10-01', 'numero_turno' => 2, 'user_id' => $otro->id, 'tirillas_valor_corriente' => 5000]);

    $kpis = app(DashboardService::class)->resumen('2026-10-01', '2026-10-01', $islero)['kpis'];

    expect($kpis['turnos'])->toBe(1)
        ->and($kpis['ventas'])->toBe(1000.0);
});

test('sin planillas en el rango el dashboard lo indica en lugar de mostrar gráficas vacías', function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]))
        ->get(route('dashboard', ['desde' => '2026-10-01', 'hasta' => '2026-10-05']))
        ->assertOk()
        ->assertSee('No hay planillas de turno en este periodo')
        ->assertDontSee('ventasChart');
});
