<?php

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Models\Turno;
use App\Models\User;
use App\Services\GuardarPlanillaTurnoService;

test('el servicio guarda la planilla completa sin pasar por la pantalla', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO, 'name' => 'Ana']);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    $turno = app(GuardarPlanillaTurnoService::class)->guardar([
        'fecha' => '2026-09-25',
        'numero_turno' => 7,
        'observaciones' => 'Turno tranquilo',
        'urea_lubricantes' => [['producto' => 'UREA', 'cantidad' => '2', 'valor_sin_iva' => '3.400', 'iva' => '0', 'total' => '3.400']],
        'cartera' => [['cartera_factura_no' => 'F-1', 'cliente_id' => $cliente->id, 'cartera_valor' => '3.400']],
        'varios' => [['concepto' => 'PEAJE', 'valor' => '0']],
        // El islero no alimenta recaudos por administración: se ignoran.
        'recaudos_admin' => [['banco' => 'CAJA', 'responsable_id' => $cliente->id, 'valor' => '1.000']],
        // Una fila que no es arreglo no rompe el guardado.
        'consignaciones' => ['basura'],
    ], $islero);

    $turno->load(['lubricantes', 'cartera', 'varios', 'recaudosAdmin', 'consignaciones']);

    expect($turno->numero_turno)->toBe(7)
        ->and($turno->user_id)->toBe($islero->id)
        ->and($turno->nombre_vendedor)->toBe('Ana')
        ->and($turno->observaciones)->toBe('Turno tranquilo')
        ->and($turno->lubricantes)->toHaveCount(1)
        ->and($turno->varios->pluck('concepto')->all())->toBe(['PEAJE'])
        ->and($turno->recaudosAdmin)->toHaveCount(0)
        ->and($turno->consignaciones)->toHaveCount(0)
        // Crédito de 3.400 contra venta de 3.400: cuadrado.
        ->and((float) $turno->fresh()->sobrante_pendiente)->toBe(0.0)
        // La cartera del cliente se sincroniza en la misma operación.
        ->and(CarteraMovimiento::query()->where('customer_id', $cliente->id)->count())->toBe(1);
});

test('si una sección falla, no queda nada guardado a medias', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    // Un cliente inexistente viola la llave foránea al guardar la cartera, después de crear el turno.
    expect(fn () => app(GuardarPlanillaTurnoService::class)->guardar([
        'fecha' => '2026-09-25',
        'numero_turno' => 8,
        'varios' => [['concepto' => 'PEAJE', 'valor' => '5.000']],
        'cartera' => [['cartera_factura_no' => 'F-9', 'cliente_id' => 999999, 'cartera_valor' => '1.000']],
    ], $admin))->toThrow(Exception::class);

    expect(Turno::count())->toBe(0);
});
