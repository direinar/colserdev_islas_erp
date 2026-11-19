<?php

use App\Models\Conteo;
use App\Models\FuelPrice;
use App\Models\InventarioSaldoInicial;
use App\Models\LecturaElectronica;
use App\Models\TanqueSaldoInicial;
use App\Models\Turno;
use App\Models\TurnoAuditoria;
use App\Models\User;
use App\Services\ConteoService;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    FuelPrice::create(['name' => 'Gasolina', 'price' => 16395, 'turno_desde' => 1, 'active' => true]);
    FuelPrice::create(['name' => 'ACPM', 'price' => 11725, 'turno_desde' => 1, 'active' => true]);
});

/**
 * @param  list<array{0: string, 1: string}>  $ventas  [surtidor, galones]
 * @param  list<array{0: string, 1: string}>  $consignaciones  [No., valor]
 */
function guardarControl(User $user, int $numero, array $ventas, array $consignaciones = [], array $extra = [])
{
    return test()->actingAs($user)->post(route('turnos.store'), array_merge([
        'fecha' => '2026-11-08',
        'numero_turno' => $numero,
        'ventas' => array_map(fn (array $v) => ['surtidor' => $v[0], 'combustible' => str_ends_with($v[0], 'ACPM') ? 'ACPM' : 'CTE', 'no_cierre' => '1', 'galones' => $v[1]], $ventas),
        'consignaciones' => array_map(fn (array $c) => ['consignacion_no' => $c[0], 'consignacion_valor' => $c[1]], $consignaciones),
    ], $extra));
}

test('si un guardado quita filas o baja el total de ventas queda una alerta con quien y cuando', function () {
    $rosa = User::factory()->create(['role' => User::ROLE_ISLERO, 'name' => 'ROSA RIVERA']);
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    guardarControl($rosa, 152, [['SURTIDOR 1 CTE', '12.8'], ['SURTIDOR 2 CTE', '98.26'], ['SURTIDOR 3 ACPM', '21.85']], [['2232', '1.000.000'], ['2233', '1.225.700']])
        ->assertSessionHasNoErrors();
    // Volver a guardarla igual no es alerta.
    guardarControl($rosa, 152, [['SURTIDOR 1 CTE', '12.8'], ['SURTIDOR 2 CTE', '98.26'], ['SURTIDOR 3 ACPM', '21.85']], [['2232', '1.000.000'], ['2233', '1.225.700']]);
    expect(TurnoAuditoria::query()->pendientes()->count())->toBe(0);

    // Se pierden dos ventas y una consignación.
    guardarControl($rosa, 152, [['SURTIDOR 1 CTE', '12.8']], [['2232', '1.000.000']])->assertSessionHasNoErrors();

    $alerta = TurnoAuditoria::query()->pendientes()->sole();
    expect($alerta->numero_turno)->toBe(152)
        ->and($alerta->user_id)->toBe($rosa->id)
        ->and($alerta->alerta)->toContain('VENTAS: 3 → 1 filas')
        ->and($alerta->alerta)->toContain('CONSIGNACIONES: 2 → 1 filas ($2.225.700 → $1.000.000)')
        ->and($alerta->alerta)->toContain('TOTAL VENTAS: $2.077.020 → $209.856')
        ->and(TurnoAuditoria::query()->where('numero_turno', 152)->pluck('accion')->all())->toBe(['creada', 'guardada', 'guardada']);

    // Administración la ve en Auditoría del administrador, con contador en el menú, y la marca revisada.
    $this->actingAs($admin)->get(route('control-planillas.index'))
        ->assertOk()
        ->assertSee('ALERTAS: GUARDADOS QUE QUITARON DATOS (1)')
        ->assertSee('ROSA RIVERA')
        ->assertSee('Auditoría del administrador')
        ->assertSee('<span class="badge text-bg-danger">No atendida</span>', false);
    $this->actingAs($admin)->get(route('control-planillas.historial', 152))->assertOk()->assertSee('Historial del turno #152');

    $this->actingAs($admin)->post(route('control-planillas.atender', $alerta))->assertRedirect(route('control-planillas.index'));
    expect($alerta->fresh()->atendida_at)->not->toBeNull()
        ->and(TurnoAuditoria::query()->pendientes()->count())->toBe(0);
    $this->actingAs($admin)->get(route('control-planillas.index'))
        ->assertSee('<span class="badge text-bg-success">Atendida</span>', false)
        ->assertDontSee('No atendida');

    // El islero no entra al control.
    $this->actingAs($rosa)->get(route('control-planillas.index'))->assertForbidden();
});

test('el control lista las planillas incompletas con lo que les falta y los numeros de turno que faltan', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    guardarControl($admin, 1, [['SURTIDOR 1 CTE', '10']], [['5001', '163.950']]);
    // Guardada sin diligenciar.
    guardarControl($admin, 2, []);
    // Con ventas pero sin dinero recibido.
    guardarControl($admin, 4, [['SURTIDOR 2 CTE', '5']]);
    // Con dinero recibido pero sin ventas.
    guardarControl($admin, 5, [], [['5002', '10.000']]);

    $pantalla = $this->actingAs($admin)->get(route('control-planillas.index', ['desde' => '2026-11-01', 'hasta' => '2026-11-30']))->assertOk();

    expect($pantalla->viewData('incompletas')->map(fn ($f) => [$f['turno']->numero_turno, $f['motivo'], $f['sin_diligenciar']])->all())->toBe([
        [5, 'Falta ventas del turno (combustible ni canastilla)', false],
        [4, 'Falta medios de pago (consignaciones, pagos electrónicos, cartera…)', false],
        [2, 'Planilla guardada sin diligenciar: anúlela', true],
    ])
        ->and($pantalla->viewData('faltantes'))->toBe([3]);

    $pantalla->assertSee('NÚMEROS DE TURNO FALTANTES (1)')
        ->assertSee('Anular planilla')
        ->assertSee('Completar');

    // Fuera del periodo no aparecen.
    expect($this->actingAs($admin)->get(route('control-planillas.index', ['desde' => '2026-10-01', 'hasta' => '2026-10-31']))
        ->viewData('incompletas'))->toBeEmpty();
});

test('la auditoria muestra el estado de revision del periodo y la planilla reabierta queda en 2a revision', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR, 'name' => 'GERMAN ADMIN']);
    $periodo = ['desde' => '2026-11-01', 'hasta' => '2026-11-30'];
    foreach ([20, 21, 22] as $numero) {
        guardarControl($admin, $numero, [['SURTIDOR 1 CTE', '10']], [[(string) (6000 + $numero), '163.950']])->assertSessionHasNoErrors();
    }
    guardarControl($admin, 23, []);
    $turno = fn (int $numero): Turno => Turno::query()->where('numero_turno', $numero)->sole();

    $this->actingAs($admin)->post(route('turnos.revisar', $turno(20)));
    $this->actingAs($admin)->post(route('turnos.reabrir', $turno(20)));
    $this->actingAs($admin)->post(route('turnos.revisar', $turno(21)));

    // La planilla guardada sin diligenciar se anula desde la auditoría y se vuelve a ella.
    $auditoria = route('control-planillas.index', $periodo);
    $this->actingAs($admin)->from($auditoria)
        ->post(route('turnos.anular', $turno(23)), ['motivo_anulacion' => 'Planilla guardada sin diligenciar', 'volver' => 'auditoria'])
        ->assertRedirect($auditoria)
        ->assertSessionHas('success', 'Turno #23 ANULADO.');

    $pantalla = $this->actingAs($admin)->get($auditoria)->assertOk()
        ->assertSee('CONTROL PLANILLAS — ESTADO DE REVISIÓN DEL PERIODO (4)')
        ->assertSee('2ª REVISIÓN: 1')
        ->assertSee('Devuelta a pendiente por GERMAN ADMIN')
        ->assertSee('Anulada por GERMAN ADMIN');
    $revision = $pantalla->viewData('revision');

    expect($revision['conteos'])->toBe(['revisada' => 1, 'segunda_revision' => 1, 'pendiente' => 1, 'anulada' => 1])
        ->and($revision['planillas']->mapWithKeys(fn ($p) => [$p['turno']->numero_turno => $p['estado']])->all())
        ->toBe([20 => 'segunda_revision', 21 => 'revisada', 22 => 'pendiente', 23 => 'anulada'])
        ->and($pantalla->viewData('incompletas'))->toBeEmpty()
        ->and(TurnoAuditoria::query()->where('numero_turno', 20)->pluck('accion')->last())->toBe('reabierta');

    // Al revisarla de nuevo sale de 2ª revisión.
    $this->actingAs($admin)->post(route('turnos.revisar', $turno(20)));
    expect($this->actingAs($admin)->get($auditoria)->viewData('revision')['conteos'])
        ->toBe(['revisada' => 2, 'segunda_revision' => 0, 'pendiente' => 1, 'anulada' => 1]);
});

test('la auditoria cruza lecturas con IAPROPIADA, tanques con inventario, ultimas planillas y comprobantes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $periodo = ['desde' => '2026-11-01', 'hasta' => '2026-11-04'];
    $turnoDelDia = fn (string $fecha, int $numero, float $corriente, float $acpm): Turno => Turno::create([
        'fecha' => $fecha, 'numero_turno' => $numero, 'precio_corriente' => 16000, 'precio_acpm' => 10000,
        'tirillas_galones_corriente' => $corriente, 'tirillas_valor_corriente' => $corriente * 16000,
        'tirillas_galones_acpm' => $acpm, 'tirillas_valor_acpm' => $acpm * 10000,
    ]);
    $turnoDelDia('2026-11-03', 30, 10, 5);
    $turnoDelDia('2026-11-04', 31, 2, 0);

    // Lectura completa del 3: 10,5 galones de PLUS 01 y 5 de ACPM 03.
    foreach (LecturaElectronica::MANGUERAS as $manguera => $combustible) {
        $galones = ['PLUS 01' => 10.5, 'ACPM 03' => 5.0][$manguera] ?? 0.0;
        LecturaElectronica::create(['fecha' => '2026-11-03', 'manguera' => $manguera, 'combustible' => $combustible,
            'lectura_inicial' => 100, 'lectura_final' => 100 + $galones, 'galones' => $galones]);
    }

    // El tanque arranca con 1.000 galones y el inventario con 990: difieren en 10.
    TanqueSaldoInicial::create(['combustible' => 'corriente', 'fecha_corte' => '2026-11-01', 'galones' => 1000]);
    InventarioSaldoInicial::create(['inventario' => 'gasolina', 'referencia' => '', 'fecha_corte' => '2026-11-01', 'unidades' => 990, 'costo_unitario' => 10000]);

    $pantalla = $this->actingAs($admin)->get(route('control-planillas.index', $periodo))->assertOk()
        ->assertSee('LECTURAS ELECTRÓNICAS')
        ->assertSee('Sin lectura completa')
        ->assertSee('INVENTARIOS — UNIDADES (GALONES)')
        ->assertSee('ESTADO DE ÚLTIMAS PLANILLAS DEL PERIODO')
        ->assertSee('COMPROBANTES DESCUADRADOS');

    expect($pantalla->viewData('lecturas')['filas'])->toBe([
        ['fecha' => '2026-11-03', 'lectura' => 218000.0, 'iapropiada' => 210000.0, 'diferencia' => 8000.0, 'completa' => true],
        ['fecha' => '2026-11-04', 'lectura' => null, 'iapropiada' => 32000.0, 'diferencia' => null, 'completa' => false],
    ]);

    $inventarios = collect($pantalla->viewData('inventarios'))->keyBy('fecha');
    expect($inventarios['2026-11-03']['corriente'])->toBe(['tanques' => 990.0, 'inventario' => 980.0, 'diferencia' => 10.0])
        ->and($inventarios['2026-11-04']['corriente'])->toBe(['tanques' => 988.0, 'inventario' => 978.0, 'diferencia' => 10.0])
        // Sin movimientos ni saldo inicial el inventario de ACPM aún no existe.
        ->and($inventarios['2026-11-01']['acpm']['diferencia'])->toBeNull()
        ->and($pantalla->viewData('ultimas')->pluck('numero_turno')->all())->toBe([31, 30])
        ->and(array_column($pantalla->viewData('comprobantes'), 'diferencia'))->toBe([0.0, 0.0, 0.0]);

    // Las últimas planillas ya no están en el dashboard.
    $this->actingAs($admin)->get(route('dashboard', $periodo))->assertOk()->assertDontSee('Últimas planillas del periodo');
});

test('consignaciones sin imprimir ni Excel y con TOTAL a la derecha; sin Limpiar y con Anular solo para administracion', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    guardarControl($admin, 7, [['SURTIDOR 1 CTE', '10']], [['2390', '100.000'], ['2387', '63.950']]);

    $html = (string) $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 7]))
        ->assertDontSee('IMPRIMIR')
        ->assertDontSee('consignaciones/exportar')
        ->assertSee('<td class="text-end">TOTAL</td>', false)
        ->assertSee('<td colspan="2" id="total-consignaciones" class="text-end">', false)
        ->assertDontSee('Limpiar')
        ->assertSee('id="anular-planilla"', false)
        ->assertSee('id="anular-planilla-form"', false)
        ->assertSee('Esta acción no se puede deshacer.')
        // Sin la lista de números digitados antes que despliega el navegador.
        ->assertSee('id="turno-form" autocomplete="off"', false)
        ->getContent();

    // Livewire inyecta sus scripts antes del primer </body>: un script con esa etiqueta literal
    // se corta, sale como texto y desacomoda los recuadros.
    expect(substr_count($html, '</head>'))->toBe(1)
        ->and(substr_count($html, '</body>'))->toBe(1);

    // NUEVO abre la planilla en blanco y sin Anular.
    $this->actingAs($admin)->get(route('turnos.create'))
        ->assertDontSee('id="anular-planilla"', false)
        ->assertDontSee('value="2390"', false);

    // El islero no ve Anular ni puede anular.
    $this->actingAs($islero)->get(route('turnos.create', ['turno_busqueda' => 7]))->assertDontSee('id="anular-planilla"', false);
    $turno = Turno::query()->sole();
    $this->actingAs($islero)->post(route('turnos.anular', $turno), ['motivo_anulacion' => 'x'])->assertForbidden();

    $this->actingAs($admin)->post(route('turnos.anular', $turno), ['motivo_anulacion' => 'Duplicada'])
        ->assertSessionHas('success', 'Turno #7 ANULADO: quedó bloqueado, nadie lo puede modificar y ya no se tiene en cuenta en informes, cartera, conteo ni control de tanques.');
    expect($turno->fresh()->anulada)->toBeTrue();
    $this->actingAs($admin)->get(route('turnos.create', ['turno_busqueda' => 7]))
        ->assertSee('A N U L A D A')
        ->assertDontSee('id="anular-planilla"', false);
});

test('el informe de gestion se descarga en PDF y se comparte por WhatsApp', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $rango = ['desde' => '2026-11-01', 'hasta' => '2026-11-30'];

    $this->actingAs($admin)->get(route('informes.cierre.index', $rango))
        ->assertSee('id="compartir-pdf"', false)
        ->assertSee(route('informes.cierre.pdf', $rango));

    $pdf = $this->actingAs($admin)->get(route('informes.cierre.pdf', $rango))
        ->assertOk()
        ->assertDownload('informes-de-gestion-2026-11-01-a-2026-11-30.pdf');

    expect(substr((string) $pdf->getContent(), 0, 5))->toBe('%PDF-');
});

test('el conteo no toma las consignaciones de una planilla anulada', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    guardarControl($admin, 10, [['SURTIDOR 1 CTE', '10']], [['3001', '100.000']]);
    guardarControl($admin, 11, [['SURTIDOR 1 CTE', '10']], [['3002', '200.000']]);
    $this->actingAs($admin)->post(route('turnos.anular', Turno::query()->where('numero_turno', 11)->sole()), ['motivo_anulacion' => 'Duplicada']);

    $this->actingAs($admin)->post(route('conteos.store'), ['fecha' => '2026-11-08', 'consignacion_anterior' => '3000', 'saldo_moneda_inicial' => '0']);
    $conteo = Conteo::query()->sole();
    $this->actingAs($admin)->post(route('conteos.guardar', [$conteo, 'consignaciones']), ['ultima_consignacion' => '3002'])->assertSessionHasNoErrors();

    expect($conteo->consignaciones()->get()->map(fn ($c) => [$c->numero, (float) $c->valor])->all())->toBe([[3001, 100000.0], [3002, 0.0]])
        ->and(app(ConteoService::class)->resumen($conteo)['total_consignaciones'])->toBe(100000.0)
        // La anulación queda en el historial de la planilla.
        ->and(TurnoAuditoria::query()->where('numero_turno', 11)->pluck('accion')->last())->toBe('anulada');

    // El No. de la planilla anulada se puede volver a usar en otra planilla.
    guardarControl($admin, 12, [['SURTIDOR 1 CTE', '10']], [['3002', '200.000']])->assertSessionHasNoErrors();
});

test('el comando recalcula solo el sobrante de $1 del redondeo y sin --aplicar no cambia nada', function () {
    $conRedondeo = Turno::create(['fecha' => '2026-10-03', 'numero_turno' => 15, 'sobrante_pendiente' => 1]);
    $conRedondeo->ventas()->create(['surtidor' => 'SURTIDOR 2 ACPM', 'combustible' => 'ACPM', 'galones' => 44.47, 'valor' => 521410.75]);
    $conRedondeo->consignaciones()->create(['consignacion_no' => '9001', 'valor' => 521411]);
    $real = Turno::create(['fecha' => '2026-10-22', 'numero_turno' => 144, 'sobrante_pendiente' => -15034]);
    $real->consignaciones()->create(['consignacion_no' => '9002', 'valor' => 1000]);
    $real->ventas()->create(['surtidor' => 'SURTIDOR 1 CTE', 'combustible' => 'CTE', 'galones' => 1, 'valor' => 16034]);

    Artisan::call('planillas:recalcular-pendientes');
    expect((float) $conRedondeo->fresh()->sobrante_pendiente)->toBe(1.0);

    Artisan::call('planillas:recalcular-pendientes', ['--aplicar' => true]);
    expect((float) $conRedondeo->fresh()->sobrante_pendiente)->toBe(0.0)
        // Un faltante real no se toca: se traslada en la planilla.
        ->and((float) $real->fresh()->sobrante_pendiente)->toBe(-15034.0);
});
