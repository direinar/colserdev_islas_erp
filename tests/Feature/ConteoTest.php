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
    ])->assertSessionHasNoErrors();

    guardarSeccion($admin, $conteo, 'retiros', ['filas' => [
        ['nombre' => 'Carlos', 'concepto' => 'Cambio', 'valor' => '100.000'],
        ['nombre' => '', 'concepto' => '', 'valor' => ''],
    ]])->assertSessionHasNoErrors();

    // Del 4560 (siguiente al 4559 del conteo anterior) al 4563; el 4562 no está en planillas.
    guardarSeccion($admin, $conteo, 'consignaciones', ['ultima_consignacion' => '4563'])->assertSessionHasNoErrors();

    guardarSeccion($admin, $conteo, 'gastos', ['filas' => [['concepto' => 'FLETES COMBUSTIBLE', 'valor' => '5.000.000']]])
        ->assertSessionHasNoErrors();

    $mayorista = ConteoDestino::query()->where('nombre', 'Abono a Mayorista')->sole();
    $cuentaByH = ConteoDestino::query()->where('nombre', 'Consigna en Cuenta ByH De:')->sole();
    $chevron = ConteoTercero::query()->where('nombre', 'Chevron Petroleum Company')->sole();
    $davivienda = ConteoTercero::query()->where('nombre', 'DAVIVIENDA CTA CTE')->sole();

    guardarSeccion($admin, $conteo, 'aplicaciones', ['filas' => [
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
    guardarSeccion($admin, Conteo::query()->sole(), 'aplicaciones', ['filas' => [['conteo_destino_id' => '', 'conteo_tercero_id' => $usado->id, 'valor' => '1.000']]]);

    $this->actingAs($admin)->delete(route('conteos.terceros.destroy', $usado))->assertSessionHasErrors('catalogo');
    $this->actingAs($admin)->delete(route('conteos.terceros.destroy', ConteoTercero::query()->where('nombre', 'BANCO DE BOGOTA')->sole()))->assertSessionHasNoErrors();

    expect(ConteoTercero::query()->pluck('nombre')->all())->toContain('Chevron Petroleum Company')->not->toContain('BANCO DE BOGOTA')
        ->and(ConteoDestino::query()->pluck('nombre')->all())->toContain('Pago de nómina');
});
