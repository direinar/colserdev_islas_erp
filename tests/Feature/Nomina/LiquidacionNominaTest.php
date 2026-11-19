<?php

use App\Models\Nomina\CuentaContableNomina;
use App\Models\Nomina\Empleado;
use App\Models\Nomina\LiquidacionNomina;
use App\Models\Nomina\ParametroLiquidacion;
use App\Models\Nomina\PlanillaTurno;
use App\Models\Nomina\Turno;
use App\Models\User;
use App\Services\Nomina\PlanillaTurnosService;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR, 'name' => 'GERMAN ADMIN']);
});

test('liquida la nomina con las formulas de la hoja NOMINA (enero 2025 de la plantilla)', function () {
    // Parámetros de enero 2025 de la plantilla: hora 1.423.500 / 230, festivo 75 %.
    ParametroLiquidacion::factory()->create([
        'vigente_desde' => '2025-01-01', 'salario_minimo' => 1423500, 'auxilio_transporte' => 200000,
        'horas_mensuales' => 230, 'porcentaje_recargo_festivo' => 75,
    ]);
    $mauricio = Empleado::factory()->create([
        'nombre_corto' => 'MAURICIO RIVERA', 'salario_basico' => 1423500, 'recibe_auxilio_transporte' => false,
        'fecha_ingreso' => '2020-01-01', 'banco' => 'BBVA',
    ]);

    $this->actingAs($this->admin)->post(route('nomina.nominas.store'), ['anio' => 2025, 'mes' => 1])
        ->assertRedirect(route('nomina.nominas.show', LiquidacionNomina::sole()));
    $liquidacion = LiquidacionNomina::sole();
    $fila = $liquidacion->empleados()->sole();
    expect($fila->dias_trabajados)->toBe(30)->and($fila->medio_pago)->toBe('BBVA');

    $this->actingAs($this->admin)->put(route('nomina.nominas.update', $liquidacion), [
        'filas' => [$fila->id => ['dias_trabajados' => 30, 'horas_nocturnas' => 201, 'horas_festivas' => 2, 'anticipos' => 500000, 'medio_pago' => 'BBVA']],
    ])->assertSessionHasNoErrors()->assertRedirect(route('nomina.nominas.show', $liquidacion));

    // Hoja NOMINA, columna MAURICIO RIVERA de ENERO-25 (redondeado a pesos).
    expect($fila->fresh()->only(['basico', 'recargo_nocturno', 'recargo_festivo', 'base_aportes', 'auxilio_transporte', 'total_devengado',
        'salud', 'pension', 'total_deducciones', 'neto_pagar', 'aporte_pension', 'aporte_salud', 'aporte_riesgos', 'aporte_caja',
        'provision_cesantias', 'provision_intereses', 'provision_vacaciones', 'provision_prima', 'costo_total']))->toBe([
            'basico' => 1423500.0, 'recargo_nocturno' => 435396.0, 'recargo_festivo' => 9284.0, 'base_aportes' => 1868180.0,
            'auxilio_transporte' => 0.0, 'total_devengado' => 1868180.0, 'salud' => 74727.0, 'pension' => 74727.0,
            'total_deducciones' => 649454.0, 'neto_pagar' => 1218726.0, 'aporte_pension' => 298909.0, 'aporte_salud' => 74727.0,
            'aporte_riesgos' => 45509.0, 'aporte_caja' => 74727.0, 'provision_cesantias' => 155619.0, 'provision_intereses' => 1556.0,
            'provision_vacaciones' => 77903.0, 'provision_prima' => 155619.0, 'costo_total' => 2752749.0,
        ]);

    $this->actingAs($this->admin)->get(route('nomina.nominas.show', $liquidacion))
        ->assertOk()
        ->assertSee('Nómina · ENERO 2025')
        ->assertSee('NETO A PAGAR POR EMPLEADO')
        ->assertSee('1.218.726');
});

test('sugiere dias proporcionales, toma los recargos de la planilla y agrega empleados faltantes', function () {
    ParametroLiquidacion::factory()->create();
    $noche = Turno::factory()->create(['nombre' => '10PM-6AM', 'hora_inicio' => '22:00', 'hora_fin' => '06:00']);
    $rosa = Empleado::factory()->create(['nombre_corto' => 'ROSA', 'fecha_ingreso' => '2024-03-01']);
    $nuevo = Empleado::factory()->create(['nombre_corto' => 'NUEVO', 'fecha_ingreso' => '2026-11-16', 'aplica_recargos' => false]);
    $retirado = Empleado::factory()->create(['nombre_corto' => 'RETIRADO', 'fecha_ingreso' => '2023-01-01', 'activo' => false, 'fecha_retiro' => '2026-11-10']);
    Empleado::factory()->create(['nombre_corto' => 'ANTIGUO', 'fecha_ingreso' => '2023-01-01', 'activo' => false, 'fecha_retiro' => '2026-10-31']);

    $planilla = PlanillaTurno::factory()->create(['anio' => 2026, 'mes' => 11]);
    app(PlanillaTurnosService::class)->guardar($planilla, [$rosa->id => ['2026-11-03' => $noche->id, '2026-11-04' => $noche->id]]);

    $this->actingAs($this->admin)->post(route('nomina.nominas.store'), ['anio' => 2026, 'mes' => 11])->assertSessionHasNoErrors();
    $liquidacion = LiquidacionNomina::sole();
    $filas = $liquidacion->empleados()->with('empleado')->get()->keyBy(fn ($fila) => $fila->empleado->nombre_corto);

    expect($filas->keys()->sort()->values()->all())->toBe(['NUEVO', 'RETIRADO', 'ROSA'])
        ->and($filas['ROSA']->dias_trabajados)->toBe(30)
        ->and($filas['NUEVO']->dias_trabajados)->toBe(15)
        ->and($filas['RETIRADO']->dias_trabajados)->toBe(10)
        // 2 noches de 10 PM a 6 AM = 16 horas nocturnas × $2.786 (7.959 × 35 %).
        ->and($filas['ROSA']->horas_nocturnas)->toBe(16.0)
        ->and($filas['ROSA']->recargo_nocturno)->toBe(round(16 * 7959 * 0.35))
        ->and($filas['NUEVO']->auxilio_transporte)->toBe(round(249095 / 30 * 15))
        ->and($filas['NUEVO']->horas_nocturnas)->toBe(0.0);

    // La planilla no está cerrada: se avisa.
    $this->actingAs($this->admin)->get(route('nomina.nominas.show', $liquidacion))
        ->assertSee('no está cerrada', false);

    // Un empleado que ingresa después de creada la nómina se agrega con el botón.
    Empleado::factory()->create(['nombre_corto' => 'TARDIO', 'fecha_ingreso' => '2026-11-20']);
    $this->actingAs($this->admin)->post(route('nomina.nominas.sincronizar', $liquidacion))
        ->assertSessionHas('success', '1 empleado(s) agregado(s) a la nómina.');

    // Se quita uno de la nómina.
    $this->actingAs($this->admin)->delete(route('nomina.nominas.quitar-empleado', [$liquidacion, $filas['RETIRADO']]))->assertSessionHas('success');
    expect($liquidacion->empleados()->count())->toBe(3);

    // No se crea dos veces ni sin parámetros vigentes.
    $this->actingAs($this->admin)->post(route('nomina.nominas.store'), ['anio' => 2026, 'mes' => 11])->assertSessionHasErrors('mes');
    $this->actingAs($this->admin)->post(route('nomina.nominas.store'), ['anio' => 2025, 'mes' => 12])->assertSessionHasErrors('mes');
});

test('forma de pago y contabilizacion cuadran; aprobada queda fija y emite desprendibles', function () {
    ParametroLiquidacion::factory()->create();
    CuentaContableNomina::query()->where('concepto', 'bancos')->update(['cuenta' => '11100501']);
    Empleado::factory()->create(['nombre_corto' => 'ROSA', 'fecha_ingreso' => '2024-01-01', 'banco' => 'Davivienda']);
    Empleado::factory()->create(['nombre_corto' => 'MARIA', 'fecha_ingreso' => '2024-01-01', 'forma_pago' => 'efectivo']);
    $this->actingAs($this->admin)->post(route('nomina.nominas.store'), ['anio' => 2026, 'mes' => 11]);
    $liquidacion = LiquidacionNomina::sole();
    $rosa = $liquidacion->empleados()->whereHas('empleado', fn ($query) => $query->where('nombre_corto', 'ROSA'))->sole();

    $this->actingAs($this->admin)->put(route('nomina.nominas.update', $liquidacion), [
        'filas' => [$rosa->id => ['dias_trabajados' => 30, 'faltantes' => 38000, 'medio_pago' => 'Davivienda']],
    ])->assertSessionHasNoErrors();

    $pantalla = $this->actingAs($this->admin)->get(route('nomina.nominas.show', $liquidacion))->assertOk();
    $formaPago = $pantalla->viewData('formaPago');
    $contable = $pantalla->viewData('contabilizacion');

    expect(collect($formaPago['medios'])->pluck('medio')->all())->toBe(['Davivienda', 'Efectivo'])
        ->and($formaPago['diferencia'])->toBe(0.0)
        ->and($contable['debito'])->toBe($contable['credito'])
        ->and($contable['debito'])->toBe((float) $liquidacion->empleados()->sum('total_devengado'))
        ->and(collect($contable['lineas'])->firstWhere('concepto', 'ANTICIPOS Y FALTANTES'))->toMatchArray(['cuenta' => '11050501', 'credito' => 38000.0])
        ->and(collect($contable['lineas'])->firstWhere('concepto', 'NETO A PAGAR - DAVIVIENDA')['cuenta'])->toBe('11100501');

    // Desprendibles en borrador salen marcados.
    $this->actingAs($this->admin)->get(route('nomina.nominas.desprendibles', $liquidacion))
        ->assertOk()->assertSee('DESPRENDIBLE DE NÓMINA DEL MES DE NOVIEMBRE 2026')->assertSee('BORRADOR');

    $this->actingAs($this->admin)->patch(route('nomina.nominas.aprobar', $liquidacion))->assertSessionHas('success');
    expect($liquidacion->fresh())->estado->toBe('aprobada')->aprobada_por->toBe('GERMAN ADMIN');

    // Aprobada: no se modifica ni se elimina.
    $this->actingAs($this->admin)->put(route('nomina.nominas.update', $liquidacion), ['filas' => [$rosa->id => ['dias_trabajados' => 10]]])
        ->assertSessionHas('error');
    $this->actingAs($this->admin)->delete(route('nomina.nominas.destroy', $liquidacion))->assertSessionHas('error');
    expect($rosa->fresh()->dias_trabajados)->toBe(30);

    $this->actingAs($this->admin)->get(route('nomina.nominas.desprendibles', [$liquidacion, 'fila' => $rosa->id]))
        ->assertOk()->assertDontSee('BORRADOR')->assertSee('He revisado y estoy de acuerdo');
    $this->actingAs($this->admin)->get(route('nomina.nominas.desprendibles.pdf', $liquidacion))->assertOk()
        ->assertDownload('desprendibles-nomina-2026-11.pdf');

    $this->actingAs($this->admin)->patch(route('nomina.nominas.reabrir', $liquidacion));
    expect($liquidacion->fresh()->estaAprobada())->toBeFalse();
});

test('la nomina del mes es solo para los roles de nomina y las cuentas contables se configuran', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $this->actingAs($islero)->get(route('nomina.nominas.index'))->assertForbidden();

    $this->actingAs($this->admin)->get(route('nomina.nominas.index'))->assertOk()->assertSee('Crear nómina del mes');
    $bancos = CuentaContableNomina::query()->where('concepto', 'bancos')->sole();
    $this->actingAs($this->admin)->put(route('nomina.cuentas-contables.update'), ['cuentas' => [$bancos->id => '11100505']])->assertSessionHasNoErrors();
    expect($bancos->fresh()->cuenta)->toBe('11100505');
    $this->actingAs($this->admin)->put(route('nomina.cuentas-contables.update'), ['cuentas' => [$bancos->id => '11-AB']])->assertSessionHasErrors('cuentas.'.$bancos->id);
});
