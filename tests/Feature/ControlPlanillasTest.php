<?php

use App\Models\Conteo;
use App\Models\FuelPrice;
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

    // Administración la ve en Control de planillas, con contador en el menú, y la marca revisada.
    $this->actingAs($admin)->get(route('control-planillas.index'))
        ->assertOk()
        ->assertSee('ALERTAS: GUARDADOS QUE QUITARON DATOS (1)')
        ->assertSee('ROSA RIVERA')
        ->assertSee('Control de planillas');
    $this->actingAs($admin)->get(route('control-planillas.historial', 152))->assertOk()->assertSee('Historial del turno #152');

    $this->actingAs($admin)->post(route('control-planillas.atender', $alerta))->assertRedirect(route('control-planillas.index'));
    expect($alerta->fresh()->atendida_at)->not->toBeNull()
        ->and(TurnoAuditoria::query()->pendientes()->count())->toBe(0);

    // El islero no entra al control.
    $this->actingAs($rosa)->get(route('control-planillas.index'))->assertForbidden();
});

test('el control lista las planillas incompletas y los numeros de turno que faltan', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    guardarControl($admin, 1, [['SURTIDOR 1 CTE', '10']], [['5001', '163.950']]);
    // Sin ventas.
    guardarControl($admin, 2, []);
    // Con ventas pero sin dinero recibido.
    guardarControl($admin, 4, [['SURTIDOR 2 CTE', '5']]);

    $pantalla = $this->actingAs($admin)->get(route('control-planillas.index'))->assertOk();

    expect($pantalla->viewData('incompletas')->map(fn ($f) => [$f['turno']->numero_turno, $f['motivo']])->all())->toBe([
        [4, 'Tiene ventas pero ningún dinero recibido (consignaciones, pagos, cartera…)'],
        [2, 'Sin ventas (combustible ni canastilla)'],
    ])
        ->and($pantalla->viewData('faltantes'))->toBe([3]);

    $pantalla->assertSee('NÚMEROS DE TURNO FALTANTES (1)');
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
