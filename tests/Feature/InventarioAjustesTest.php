<?php

use App\Models\InventarioAjuste;
use App\Models\InventarioSaldoInicial;
use App\Models\Lubricant;
use App\Models\Turno;
use App\Models\User;
use App\Services\ControlCajaService;
use App\Services\KardexInventarioService;

beforeEach(function () {
    Lubricant::create(['reference' => 'MOBIL 20W50', 'sale_price' => 50000, 'iva' => 0, 'total' => 50000, 'cost_price' => 30000, 'active' => true]);
    InventarioSaldoInicial::create(['inventario' => InventarioSaldoInicial::CANASTILLA, 'referencia' => 'MOBIL 20W50', 'fecha_corte' => '2026-09-01', 'unidades' => 12, 'costo_unitario' => 30000]);
});

test('administracion registra una averia y un sobrante y el kardex los lleva al costo promedio', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('inventarios-ajustes.store'), [
        'fecha' => '2026-09-10', 'inventario' => 'canastilla', 'referencia' => 'MOBIL 20W50',
        'motivo' => 'averia', 'unidades' => '2', 'observacion' => 'Envase roto en bodega',
    ])->assertSessionHasNoErrors()->assertRedirect();
    $this->actingAs($admin)->post(route('inventarios-ajustes.store'), [
        'fecha' => '2026-09-15', 'inventario' => 'canastilla', 'referencia' => 'MOBIL 20W50',
        'motivo' => 'sobrante', 'unidades' => '1', 'observacion' => 'Conteo físico',
    ])->assertSessionHasNoErrors();

    $kardex = app(KardexInventarioService::class)->canastilla('MOBIL 20W50');
    $filas = array_map(fn (array $f): array => [$f['tipo'], $f['factura'], $f['entradas'], $f['salidas'], round($f['valor_entradas']), round($f['valor_salidas']), round($f['promedio'])], $kardex['rows']);

    expect($filas)->toBe([
        ['saldo_inicial', 'SALDO INICIAL', 12.0, 0.0, 360000.0, 0.0, 30000.0],
        ['ajuste', 'AJUSTE: AVERÍA', 0.0, 2.0, 0.0, 60000.0, 30000.0],
        ['ajuste', 'AJUSTE: SOBRANTE EN CONTEO', 1.0, 0.0, 30000.0, 0.0, 30000.0],
    ])
        ->and($kardex['totales']['saldo_unidades'])->toBe(11.0)
        ->and(round($kardex['totales']['valor_saldo']))->toBe(330000.0);

    $ajuste = InventarioAjuste::query()->where('motivo', 'averia')->sole();
    expect($ajuste->user_id)->toBe($admin->id);

    // En pantalla: el ajuste en el kardex y en la lista de ajustes.
    $this->actingAs($admin)->get(route('inventarios-lubricantes.create', ['producto' => 'MOBIL 20W50', 'desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertSee('AJUSTE: AVERÍA');
    $this->actingAs($admin)->get(route('inventarios-ajustes.index', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
        ->assertSee('Envase roto en bodega');

    // Borrarlo devuelve las unidades al kardex.
    $this->actingAs($admin)->delete(route('inventarios-ajustes.destroy', $ajuste))->assertRedirect();
    app()->forgetScopedInstances();
    expect(app(KardexInventarioService::class)->canastilla('MOBIL 20W50')['totales']['saldo_unidades'])->toBe(13.0);
});

test('un ajuste de combustible baja el kardex de gasolina y no cuenta como costo de ventas', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    InventarioSaldoInicial::create(['inventario' => InventarioSaldoInicial::GASOLINA, 'fecha_corte' => '2026-09-01', 'unidades' => 1000, 'costo_unitario' => 10000]);
    Turno::create(['fecha' => '2026-09-05', 'numero_turno' => 1, 'precio_corriente' => 16000, 'tirillas_galones_corriente' => 10, 'tirillas_valor_corriente' => 160000]);

    $this->actingAs($admin)->post(route('inventarios-ajustes.store'), [
        'fecha' => '2026-09-05', 'inventario' => 'gasolina', 'referencia' => 'MOBIL 20W50',
        'motivo' => 'perdida', 'unidades' => '5.5', 'observacion' => 'Derrame al descargar',
    ])->assertSessionHasNoErrors();

    expect(InventarioAjuste::query()->sole()->referencia)->toBe('')
        ->and(app(KardexInventarioService::class)->gasolina()['totales']['saldo_unidades'])->toBe(984.5);

    // Costo de ventas de combustible: solo los 10 galones vendidos, no la pérdida.
    $comprobante = app(ControlCajaService::class)->comprobanteVentas('2026-09-01', '2026-09-30')['comprobante']['lineas'];
    expect(collect($comprobante)->firstWhere('concepto', 'COSTO DE VENTAS DE COMBUSTIBLE')['debito'])->toBe(100000.0);
});

test('el ajuste exige producto en canastilla, cantidad positiva y detalle; solo administracion', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);

    $this->actingAs($admin)->post(route('inventarios-ajustes.store'), [
        'fecha' => '2026-09-10', 'inventario' => 'canastilla', 'referencia' => '',
        'motivo' => 'averia', 'unidades' => '0', 'observacion' => '',
    ])->assertSessionHasErrors(['referencia', 'observacion']);
    $this->actingAs($admin)->post(route('inventarios-ajustes.store'), [
        'fecha' => '2026-09-10', 'inventario' => 'canastilla', 'referencia' => 'MOBIL 20W50',
        'motivo' => 'averia', 'unidades' => '0', 'observacion' => 'x',
    ])->assertSessionHasErrors('unidades');

    expect(InventarioAjuste::query()->count())->toBe(0);

    $this->actingAs($jefe)->get(route('inventarios-ajustes.index'))->assertForbidden();
});
