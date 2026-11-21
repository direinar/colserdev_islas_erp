<?php

use App\Models\Conteo;
use App\Models\ConteoDestino;
use App\Models\ConteoTercero;
use App\Models\Turno;
use App\Models\User;
use App\Services\ConteoService;

function crearConteo(User $user, array $datos = [])
{
    return test()->actingAs($user)->post(route('conteos.store'), $datos + [
        'fecha' => '2026-10-27',
        'consignacion_anterior' => '4559',
        'saldo_moneda_inicial' => '2.248.100',
    ]);
}

function guardarSeccion(User $user, Conteo $conteo, string $seccion, array $datos)
{
    return test()->actingAs($user)->post(route('conteos.guardar', [$conteo, $seccion]), $datos);
}

/**
 * Planilla de turno con consignaciones numeradas: de ahí el conteo toma su valor.
 *
 * @param  array<string, float>  $consignaciones  No. => valor
 */
function planillaConConsignaciones(int $numero, array $consignaciones): Turno
{
    $turno = Turno::create(['fecha' => '2026-10-26', 'numero_turno' => $numero]);

    foreach ($consignaciones as $no => $valor) {
        $turno->consignaciones()->create(['consignacion_no' => (string) $no, 'valor' => $valor, 'total' => $valor]);
    }

    return $turno;
}

test('solo el administrador y el gerente entran al conteo, que aparece en el menu', function () {
    $gerente = User::factory()->create(['role' => User::ROLE_GERENTE]);

    foreach ([User::ROLE_ISLERO, User::ROLE_JEFE_PATIOS] as $rol) {
        $this->actingAs(User::factory()->create(['role' => $rol]))->get(route('conteos.index'))->assertForbidden();
    }

    $this->actingAs($gerente)->get(route('conteos.index'))
        ->assertOk()
        ->assertSee('NUEVO CONTEO')
        ->assertSee('Conteos (nuevo y consulta)');
});

test('el primer conteo pide el punto de partida y arranca con las denominaciones; el No. es consecutivo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    crearConteo($admin, ['consignacion_anterior' => '', 'saldo_moneda_inicial' => ''])
        ->assertSessionHasErrors(['consignacion_anterior', 'saldo_moneda_inicial']);

    crearConteo($admin)->assertRedirect(route('conteos.seccion', [Conteo::query()->sole(), 'efectivo']));

    $primero = Conteo::query()->sole();
    expect($primero->numero)->toBe(1)
        ->and($primero->consignacion_anterior)->toBe(4559)
        ->and((float) $primero->saldo_moneda_inicial)->toBe(2248100.0)
        ->and($primero->billetes->pluck('denominacion')->all())->toBe(Conteo::DENOMINACIONES);

    // El siguiente ya no pide el punto de partida: lo arrastra del conteo anterior.
    $this->actingAs($admin)->get(route('conteos.index'))->assertDontSee('SALDO INICIAL DE MONEDA');
    crearConteo($admin, ['fecha' => '2026-10-30', 'consignacion_anterior' => '', 'saldo_moneda_inicial' => ''])->assertSessionHasNoErrors();

    expect(Conteo::query()->orderBy('numero')->pluck('numero')->all())->toBe([1, 2]);
});

test('el conteo cuadra efectivo, consignaciones internas, gastos, consignaciones en bancos y moneda como la plantilla', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    planillaConConsignaciones(80, [4560 => 600000, 4561 => 1600000]);
    planillaConConsignaciones(81, [4563 => 2450000]);
    crearConteo($admin);
    $conteo = Conteo::query()->sole();

    // Cuadre de efectivo: lo que suma cada denominación (sin multiplicar) y la moneda.
    guardarSeccion($admin, $conteo, 'efectivo', [
        'billetes' => [
            ['denominacion' => '100 MIL', 'valor' => '7.700.000'],
            ['denominacion' => '50 MIL', 'valor' => '3.750.000'],
            ['denominacion' => '20 MIL', 'valor' => '740.000'],
            ['denominacion' => '10 MIL', 'valor' => '700.000'],
            ['denominacion' => '5 MIL', 'valor' => '265.000'],
            ['denominacion' => 'DOS MIL', 'valor' => '178.000'],
            ['denominacion' => 'FAJOS', 'valor' => '17.400.000'],
        ],
        'moneda' => '209.800',
        // Retiros antes de este cierre: en la misma pantalla del cuadre de efectivo.
        'filas' => [
            ['nombre' => 'Carlos', 'concepto' => 'Cambio', 'valor' => '100.000'],
            ['nombre' => '', 'concepto' => '', 'valor' => ''],
        ],
    ])->assertSessionHasNoErrors();

    // Del 4560 (siguiente al 4559 del conteo anterior) al 4563; el 4562 no está en planillas.
    guardarSeccion($admin, $conteo, 'consignaciones', ['ultima_consignacion' => '4563'])->assertSessionHasNoErrors();

    guardarSeccion($admin, $conteo, 'gastos', ['filas' => [['concepto' => 'FLETES COMBUSTIBLE', 'valor' => '5.000.000']]])
        ->assertSessionHasNoErrors();

    $mayorista = ConteoDestino::query()->where('nombre', 'Abono a Mayorista')->sole();
    $cuentaByH = ConteoDestino::query()->where('nombre', 'Consigna en Cuenta ByH De:')->sole();
    $chevron = ConteoTercero::query()->where('nombre', 'Chevron Petroleum Company')->sole();
    $davivienda = ConteoTercero::query()->where('nombre', 'DAVIVIENDA CTA CTE')->sole();

    guardarSeccion($admin, $conteo, 'gastos', ['aplicaciones' => [
        ['conteo_destino_id' => $mayorista->id, 'conteo_tercero_id' => $chevron->id, 'valor' => '25.000.000'],
        ['conteo_destino_id' => $cuentaByH->id, 'conteo_tercero_id' => $davivienda->id, 'valor' => '733.000'],
    ]])->assertSessionHasNoErrors();

    guardarSeccion($admin, $conteo, 'moneda', ['filas' => [['concepto' => 'Cambio a banco', 'valor' => '50.000']], 'saldo_moneda_inicial' => '2.248.100'])
        ->assertSessionHasNoErrors();

    $resumen = app(ConteoService::class)->resumen($conteo->fresh());

    expect($resumen)->toMatchArray([
        'billetes' => 30733000.0,
        'moneda' => 209800.0,
        'subtotal' => 30942800.0,
        'retiros' => 100000.0,
        'total_cierre' => 31042800.0,
        'consignacion_anterior' => 4559,
        'ultima_consignacion' => 4563,
        'total_consignaciones' => 4650000.0,
        'diferencia' => 26392800.0,
        'gastos' => 5000000.0,
        'a_consignar' => 25733000.0,
        'aplicaciones' => 25733000.0,
        'no_consignado' => 0.0,
        'saldo_moneda_anterior' => 2248100.0,
        'retiros_moneda' => 50000.0,
        'saldo_moneda_pasa' => 2407900.0,
    ])
        ->and($conteo->consignaciones()->get()->map(fn ($c) => [$c->numero, (float) $c->valor, $c->turno_id !== null])->all())
        ->toBe([[4560, 600000.0, true], [4561, 1600000.0, true], [4562, 0.0, false], [4563, 2450000.0, true]]);

    // El número que no está en ninguna planilla se avisa en rojo.
    $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, 'consignaciones']))
        ->assertOk()
        ->assertSee('1 número(s) no están en ninguna planilla de turno')
        ->assertSee('Turno #80');

    // Cada tabla es un submódulo con su pestaña.
    foreach (array_keys(Conteo::SECCIONES) as $seccion) {
        $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, $seccion]))->assertOk()->assertSee(Conteo::SECCIONES[$seccion]);
    }

    // Los retiros antes del cierre van en el cuadre de efectivo, con RETIROS y TOTAL CIERRE; ya no tienen pestaña.
    $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, 'efectivo']))
        ->assertSeeInOrder(['CUADRE DE EFECTIVO', 'SUB TOTAL', 'RETIROS ANTES DE ESTE CIERRE', 'Carlos', 'TOTAL RETIROS', 'TOTAL CIERRE', '31.042.800'])
        ->assertDontSee('Retiros antes del cierre</a>', false);
    $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, 'retiros']))->assertRedirect(route('conteos.seccion', [$conteo, 'efectivo']));

    // La consignación en bancos va en el cuadre del conteo, debajo de gastos y retiros; ya no tiene pestaña.
    $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, 'gastos']))
        ->assertSeeInOrder(['GASTOS Y RETIROS EN ESTE CONTEO', 'CUADRE DE ESTE CONTEO', 'CONSIGNACIÓN EN BANCOS Y OTRAS APLICACIONES', 'TOTAL CONSIGNACIONES Y OTROS'])
        ->assertSee('name="aplicaciones[0][conteo_destino_id]"', false)
        ->assertDontSee('Consignación en bancos</a>', false);
    $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, 'aplicaciones']))->assertRedirect(route('conteos.seccion', [$conteo, 'gastos']).'#aplicaciones');

    // Imprimir, Excel y PDF.
    $this->actingAs($admin)->get(route('conteos.reporte', $conteo))->assertOk()->assertSee('C O N T E O')->assertSee('31.042.800');
    $this->actingAs($admin)->get(route('conteos.exportar', $conteo))->assertDownload('conteo-1-2026-10-27.xlsx');
    expect(substr((string) $this->actingAs($admin)->get(route('conteos.pdf', $conteo))->assertDownload('conteo-1-2026-10-27.pdf')->getContent(), 0, 5))->toBe('%PDF-');
});

test('el conteo siguiente arranca en el ultimo No. y el saldo de moneda del anterior, que ya no se modifica', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearConteo($admin);
    $primero = Conteo::query()->sole();
    guardarSeccion($admin, $primero, 'efectivo', ['billetes' => [['denominacion' => '100 MIL', 'valor' => '1.000.000']], 'moneda' => '100.000']);
    guardarSeccion($admin, $primero, 'consignaciones', ['ultima_consignacion' => '4565']);

    crearConteo($admin, ['fecha' => '2026-10-30']);
    $segundo = Conteo::query()->where('numero', 2)->sole();
    $resumen = app(ConteoService::class)->resumen($segundo);

    expect($resumen['consignacion_anterior'])->toBe(4565)
        ->and($resumen['saldo_moneda_anterior'])->toBe(2348100.0);

    guardarSeccion($admin, $segundo, 'consignaciones', ['ultima_consignacion' => '4565'])
        ->assertSessionHasErrors(['ultima_consignacion' => 'El último No. de consignación debe ser mayor que el último del conteo anterior (4565).']);

    guardarSeccion($admin, $primero, 'efectivo', ['moneda' => '1'])->assertSessionHasErrors('conteo');
    expect((float) $primero->fresh()->moneda)->toBe(100000.0);

    $this->actingAs($admin)->get(route('conteos.seccion', [$primero, 'efectivo']))
        ->assertSee('Este conteo ya tiene conteos posteriores: solo se puede')
        ->assertDontSee('<i class="bi bi-save" aria-hidden="true"></i> GUARDAR', false);
});

test('en la planilla el No. de consignacion es obligatorio, solo digitos y no se repite en otra planilla', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    planillaConConsignaciones(80, [4560 => 600000]);

    $guardar = fn (array $consignaciones) => $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => '2026-10-27', 'numero_turno' => 90, 'consignaciones' => $consignaciones,
    ]);

    $guardar([['consignacion_no' => '', 'consignacion_valor' => '50.000']])
        ->assertSessionHasErrors(['consignaciones' => 'En CONSIGNACIONES hay valores sin No. de consignación. El No. es obligatorio: el conteo toma el valor de cada consignación por su número.']);
    $guardar([['consignacion_no' => 'C-1', 'consignacion_valor' => '50.000']])->assertSessionHasErrors('consignaciones.0.consignacion_no');
    $guardar([['consignacion_no' => '4560', 'consignacion_valor' => '50.000']])
        ->assertSessionHasErrors(['consignaciones' => 'El No. de consignación 4560 ya está en la planilla del turno #80.']);
    $guardar([['consignacion_no' => '4561', 'consignacion_valor' => '50.000'], ['consignacion_no' => '4561', 'consignacion_valor' => '1']])
        ->assertSessionHasErrors(['consignaciones' => 'En CONSIGNACIONES el No. 4561 está repetido.']);

    $guardar([['consignacion_no' => '4561', 'consignacion_valor' => '50.000']])->assertSessionHasNoErrors();
    // Volver a guardar la misma planilla con su propio No. no es repetido.
    $guardar([['consignacion_no' => '4561', 'consignacion_valor' => '60.000']])->assertSessionHasNoErrors();
});

test('destinos, bancos y terceros se administran y no se eliminan si ya se usaron en un conteo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('conteos.terceros.store'), ['nombre' => 'BANCO DE BOGOTA', 'tipo' => 'banco'])->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('conteos.destinos.store'), ['nombre' => 'Pago de nómina'])->assertSessionHasNoErrors();

    $usado = ConteoTercero::query()->where('nombre', 'Chevron Petroleum Company')->sole();
    crearConteo($admin);
    guardarSeccion($admin, Conteo::query()->sole(), 'gastos', ['aplicaciones' => [['conteo_destino_id' => '', 'conteo_tercero_id' => $usado->id, 'valor' => '1.000']]]);

    $this->actingAs($admin)->delete(route('conteos.terceros.destroy', $usado))->assertSessionHasErrors('catalogo');
    $this->actingAs($admin)->delete(route('conteos.terceros.destroy', ConteoTercero::query()->where('nombre', 'BANCO DE BOGOTA')->sole()))->assertSessionHasNoErrors();

    expect(ConteoTercero::query()->pluck('nombre')->all())->toContain('Chevron Petroleum Company')->not->toContain('BANCO DE BOGOTA')
        ->and(ConteoDestino::query()->pluck('nombre')->all())->toContain('Pago de nómina');
});

test('los isleros comparten un solo consecutivo de consignaciones y lo usan en desorden entre planillas', function () {
    $rosa = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $andres = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $maria = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $guardar = fn (User $islero, int $turno, array $numeros) => $this->actingAs($islero)->post(route('turnos.store'), [
        'fecha' => '2026-11-04',
        'numero_turno' => $turno,
        'consignaciones' => array_map(fn (string $no) => ['consignacion_no' => $no, 'consignacion_valor' => '100.000'], $numeros),
    ]);

    // Sin consecutivo propio por islero, intercalados y sin orden dentro de la planilla.
    $guardar($andres, 230, ['2390', '2387'])->assertSessionHasNoErrors();
    $guardar($rosa, 231, ['2394', '2389', '2391'])->assertSessionHasNoErrors();
    $guardar($maria, 232, ['2388', '2395'])->assertSessionHasNoErrors();

    // Lo único que se impide es repetir un número que ya usó otro islero.
    $guardar($maria, 233, ['2389'])
        ->assertSessionHasErrors(['consignaciones' => 'El No. de consignación 2389 ya está en la planilla del turno #231.']);

    // El conteo los recorre en orden sin importar de quién o de qué planilla son.
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearConteo($admin, ['consignacion_anterior' => '2386']);
    guardarSeccion($admin, Conteo::query()->sole(), 'consignaciones', ['ultima_consignacion' => '2395'])->assertSessionHasNoErrors();

    expect(Conteo::query()->sole()->consignaciones()->get()->map(fn ($c) => [$c->numero, $c->turno?->numero_turno])->all())->toBe([
        [2387, 230], [2388, 232], [2389, 231], [2390, 230], [2391, 231], [2392, null], [2393, null], [2394, 231], [2395, 232],
    ]);
});

test('con cambio de talonario el consecutivo salta los numeros que no existen', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearConteo($admin, ['consignacion_anterior' => '2497']);
    $conteo = Conteo::query()->sole();
    planillaConConsignaciones(1, [2498 => 100000, 2499 => 200000, 2700 => 300000, 2701 => 400000]);

    guardarSeccion($admin, $conteo, 'consignaciones', ['ultima_consignacion' => '2701', 'talonario_fin' => '2499', 'talonario_inicio' => '2700'])
        ->assertSessionHasNoErrors();

    $conteo->refresh();
    expect($conteo->consignaciones->pluck('numero')->all())->toBe([2498, 2499, 2700, 2701])
        ->and(app(ConteoService::class)->resumen($conteo)['total_consignaciones'])->toBe(1000000.0)
        ->and([$conteo->talonario_fin, $conteo->talonario_inicio])->toBe([2499, 2700]);

    $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, 'consignaciones']))
        ->assertSee('Cambio de talonario: el anterior terminó en 2499; el nuevo inicia en 2700.');

    // El talonario nuevo debe quedar dentro del conteo y después del anterior.
    guardarSeccion($admin, $conteo, 'consignaciones', ['ultima_consignacion' => '2701', 'talonario_fin' => '2499', 'talonario_inicio' => '2499'])
        ->assertSessionHasErrors('talonario_inicio');
    guardarSeccion($admin, $conteo, 'consignaciones', ['ultima_consignacion' => '2701', 'talonario_fin' => '2499'])
        ->assertSessionHasErrors('talonario_inicio');
});

test('las filas manuales de consignaciones llevan concepto, suman al total y se conservan', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearConteo($admin, ['consignacion_anterior' => '2427']);
    $conteo = Conteo::query()->sole();
    planillaConConsignaciones(1, [2428 => 567000]);

    guardarSeccion($admin, $conteo, 'consignaciones', [
        'ultima_consignacion' => '2428',
        'manuales' => [
            ['numero' => '9001', 'valor' => '50.000', 'concepto' => 'Consignación de recaudo de cartera'],
            ['numero' => '', 'valor' => '', 'concepto' => ''],
            ['numero' => '', 'valor' => '20.000', 'concepto' => 'Ajuste de caja'],
            // El No. de un registro manual puede ser alfanumérico.
            ['numero' => 'rc-15a', 'valor' => '10.000', 'concepto' => 'Recibo de caja'],
        ],
    ])->assertSessionHasNoErrors();

    $conteo->refresh();
    expect($conteo->consignaciones->map(fn ($c) => [$c->numeroVisible(), (float) $c->valor, $c->manual, $c->concepto])->all())->toBe([
        ['2428', 567000.0, false, null],
        ['', 20000.0, true, 'Ajuste de caja'],
        ['RC-15A', 10000.0, true, 'Recibo de caja'],
        ['9001', 50000.0, true, 'Consignación de recaudo de cartera'],
    ])
        ->and(app(ConteoService::class)->resumen($conteo)['total_consignaciones'])->toBe(647000.0);

    $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, 'consignaciones']))
        ->assertSee('REGISTROS MANUALES (no consecutivos)')
        ->assertSee('value="Ajuste de caja"', false)
        ->assertSee('value="RC-15A"', false)
        ->assertSee('+ AGREGAR FILA MANUAL');

    // Concepto obligatorio en una fila con datos.
    guardarSeccion($admin, $conteo, 'consignaciones', ['ultima_consignacion' => '2428', 'manuales' => [['numero' => '', 'valor' => '1.000', 'concepto' => '']]])
        ->assertSessionHasErrors('manuales.0.concepto');

    $this->actingAs($admin)->get(route('conteos.reporte', $conteo))->assertSee('MANUAL: Ajuste de caja');
});

test('la diferencia no consignada pasa al siguiente conteo con referencia al conteo de origen', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearConteo($admin);
    $primero = Conteo::query()->sole();
    guardarSeccion($admin, $primero, 'efectivo', ['billetes' => [['denominacion' => '100 MIL', 'valor' => '9.235.000']]]);
    guardarSeccion($admin, $primero, 'consignaciones', ['ultima_consignacion' => '4560']);
    $destino = ConteoDestino::firstOrCreate(['nombre' => 'Abono a Mayorista']);
    $tercero = ConteoTercero::firstOrCreate(['nombre' => 'Chevron Petroleum Company'], ['tipo' => 'tercero']);
    guardarSeccion($admin, $primero, 'gastos', ['aplicaciones' => [['conteo_destino_id' => $destino->id, 'conteo_tercero_id' => $tercero->id, 'valor' => '9.200.000']]]);

    expect(app(ConteoService::class)->resumen($primero)['no_consignado'])->toBe(35000.0);

    crearConteo($admin, ['fecha' => '2026-10-30']);
    $segundo = Conteo::query()->where('numero', 2)->sole();
    guardarSeccion($admin, $segundo, 'efectivo', ['billetes' => [['denominacion' => '100 MIL', 'valor' => '1.000.000']]]);
    guardarSeccion($admin, $segundo, 'gastos', ['aplicaciones' => [['conteo_destino_id' => $destino->id, 'conteo_tercero_id' => $tercero->id, 'valor' => '1.035.000']]]);

    $resumen = app(ConteoService::class)->resumen($segundo);
    expect($resumen['no_consignado_anterior'])->toBe(35000.0)
        // 1.000.000 a consignar + 35.000 que venían − 1.035.000 consignado.
        ->and($resumen['no_consignado'])->toBe(0.0);

    $this->actingAs($admin)->get(route('conteos.seccion', [$segundo, 'gastos']))
        ->assertSee('DIFERENCIAS NO CONSIGNADAS DE CONTEOS ANTERIORES')
        ->assertSee(route('conteos.seccion', [$primero, 'gastos']).'#aplicaciones')
        ->assertSee('conteo No. 1');

    // Sin diferencia anterior la fila no aparece.
    $this->actingAs($admin)->get(route('conteos.seccion', [$primero, 'gastos']))
        ->assertDontSee('id="no-consignado-anterior"', false);
});

test('el resumen del conteo muestra cada valor en la misma linea de su texto', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearConteo($admin);
    $conteo = Conteo::query()->sole();

    $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, 'efectivo']))
        ->assertSee('<div class="conteo-resumen-item">', false)
        ->assertSeeInOrder(['<span>TOTAL CIERRE</span>', '<span class="valor', '<span>CONSIGNACIONES INTERNAS</span>'], false);
});

test('alerta cuando una planilla usa un No. por fuera del consecutivo, salvo el hueco del cambio de talonario', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearConteo($admin, ['consignacion_anterior' => '2497']);
    $conteo = Conteo::query()->sole();
    // 2550 está en el hueco entre talonarios: se usó un No. que ya no debía existir.
    planillaConConsignaciones(1, [2498 => 100000, 2499 => 200000, 2700 => 300000]);
    planillaConConsignaciones(2, [2550 => 45000, 2701 => 10000]);

    guardarSeccion($admin, $conteo, 'consignaciones', ['ultima_consignacion' => '2700', 'talonario_fin' => '2499', 'talonario_inicio' => '2700']);

    expect(app(ConteoService::class)->fueraDeConsecutivo($conteo->fresh())->pluck('consignacion_no')->all())->toBe(['2550']);

    $this->actingAs($admin)->get(route('conteos.seccion', [$conteo, 'consignaciones']))
        ->assertSee('1 consignación(es) de planillas por fuera del consecutivo')
        ->assertSee('No. 2550');

    // Agregada como registro manual con su No., deja de estar por fuera; 2701 es del próximo conteo.
    guardarSeccion($admin, $conteo, 'consignaciones', ['ultima_consignacion' => '2700', 'talonario_fin' => '2499', 'talonario_inicio' => '2700',
        'manuales' => [['numero' => '2550', 'valor' => '45.000', 'concepto' => 'No. del talonario anterior usado tarde']]]);
    expect(app(ConteoService::class)->fueraDeConsecutivo($conteo->fresh()))->toBeEmpty();
});

test('un exceso consignado (no consignado negativo) no pasa al siguiente conteo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    crearConteo($admin);
    $primero = Conteo::query()->sole();
    $destino = ConteoDestino::firstOrCreate(['nombre' => 'Abono a Mayorista']);
    $tercero = ConteoTercero::firstOrCreate(['nombre' => 'Chevron Petroleum Company'], ['tipo' => 'tercero']);
    guardarSeccion($admin, $primero, 'efectivo', ['billetes' => [['denominacion' => '100 MIL', 'valor' => '1.000.000']]]);
    guardarSeccion($admin, $primero, 'consignaciones', ['ultima_consignacion' => '4560']);
    // Se consignaron 998.000 de más.
    guardarSeccion($admin, $primero, 'gastos', ['aplicaciones' => [['conteo_destino_id' => $destino->id, 'conteo_tercero_id' => $tercero->id, 'valor' => '1.998.000']]]);

    expect(app(ConteoService::class)->resumen($primero)['no_consignado'])->toBe(-998000.0);

    crearConteo($admin, ['fecha' => '2026-10-30']);
    $segundo = Conteo::query()->where('numero', 2)->sole();

    expect(app(ConteoService::class)->resumen($segundo)['no_consignado_anterior'])->toBe(0.0);
    $this->actingAs($admin)->get(route('conteos.seccion', [$segundo, 'gastos']))
        ->assertDontSee('id="no-consignado-anterior"', false);
});
