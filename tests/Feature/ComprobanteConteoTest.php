<?php

use App\Models\Conteo;
use App\Models\ConteoCuenta;
use App\Models\ConteoDestino;
use App\Models\ConteoTercero;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $this->destino = fn (string $nombre): int => ConteoDestino::query()->where('nombre', $nombre)->value('id');
    $this->tercero = fn (string $nombre): int => ConteoTercero::query()->where('nombre', $nombre)->value('id');
    $this->cuenta = fn (string $cuenta): int => ConteoCuenta::query()->where('cuenta', $cuenta)->value('id');
});

/**
 * Consignación en bancos del ejemplo de la plantilla (conteo No. 2).
 *
 * @return list<array<string, mixed>>
 */
function aplicacionesDelEjemplo(object $test, array $cuentas = []): array
{
    return [
        ['conteo_destino_id' => ($test->destino)('Abono a Mayorista'), 'conteo_tercero_id' => ($test->tercero)('Chevron Petroleum Company'), 'conteo_cuenta_id' => $cuentas[0] ?? '', 'valor' => '9.200.000'],
        ['conteo_destino_id' => ($test->destino)('Abono a Crédito - CAPITAL'), 'conteo_tercero_id' => ($test->tercero)('BANCO BBVA CTA CTE'), 'conteo_cuenta_id' => $cuentas[1] ?? '', 'valor' => '1.200.000'],
        ['conteo_destino_id' => ($test->destino)('Abono a Crédito - INTERESES'), 'conteo_tercero_id' => '', 'conteo_cuenta_id' => $cuentas[2] ?? '', 'valor' => '100.000'],
    ];
}

test('el comprobante del conteo debita cada consignacion con su cuenta y acredita caja por el total, como la plantilla', function () {
    $this->actingAs($this->admin)->post(route('conteos.store'), ['fecha' => '2026-11-17', 'consignacion_anterior' => '100', 'saldo_moneda_inicial' => '0']);
    $conteo = Conteo::query()->sole();
    $this->actingAs($this->admin)->post(route('conteos.guardar', [$conteo, 'aplicaciones']), ['filas' => aplicacionesDelEjemplo($this)])
        ->assertSessionHasNoErrors();

    // Sin cuentas todavía: se avisa.
    $this->actingAs($this->admin)->get(route('conteos.seccion', [$conteo, 'comprobante']))
        ->assertOk()
        ->assertSee('COMPROBANTE CONTABLE DEL CONTEO No. 1')
        ->assertSee('3 línea(s) sin cuenta', false);

    $aplicaciones = $conteo->aplicaciones()->orderBy('id')->pluck('id');
    $this->actingAs($this->admin)->post(route('conteos.guardar', [$conteo, 'comprobante']), ['cuentas' => [
        $aplicaciones[0] => ($this->cuenta)('23050501'),
        $aplicaciones[1] => ($this->cuenta)('210507'),
        $aplicaciones[2] => ($this->cuenta)('530520'),
    ]])->assertSessionHasNoErrors()->assertRedirect(route('conteos.seccion', [$conteo, 'comprobante']));

    $comprobante = $this->actingAs($this->admin)->get(route('conteos.seccion', [$conteo, 'comprobante']))
        ->assertOk()->assertSee('Sumas iguales')->assertDontSee('sin cuenta')
        ->viewData('comprobante');

    expect(array_map(fn (array $linea): array => [$linea['cuenta'], $linea['concepto'], $linea['tercero'], $linea['nit'], $linea['debito'], $linea['credito']], $comprobante['lineas']))->toBe([
        ['23050501', 'ABONO A CUENTA CON CONTEO No.1', 'CHEVRON PETROLEUM COMPANY', '860005223', 9200000.0, 0.0],
        ['210507', 'CUOTA CREDITO FINAGRO BBVA CON CONTEO No.1', 'BANCO BBVA', '860003020', 1200000.0, 0.0],
        ['530520', 'INTERESES CREDITO CON CONTEO No.1', 'BANCO BBVA', '860003020', 100000.0, 0.0],
        ['11050501', 'CONTEO No.1', null, null, 0.0, 10500000.0],
    ])
        ->and($comprobante['debito'])->toBe(10500000.0)
        ->and($comprobante['credito'])->toBe(10500000.0);
});

test('el siguiente conteo propone la ultima cuenta usada y el anterior sigue aceptando cuentas', function () {
    $this->actingAs($this->admin)->post(route('conteos.store'), ['fecha' => '2026-11-08', 'consignacion_anterior' => '100', 'saldo_moneda_inicial' => '0']);
    $primero = Conteo::query()->sole();
    $cuentas = [($this->cuenta)('23050501'), ($this->cuenta)('210512'), ($this->cuenta)('530520')];
    $this->actingAs($this->admin)->post(route('conteos.guardar', [$primero, 'aplicaciones']), ['filas' => aplicacionesDelEjemplo($this, $cuentas)]);

    $this->actingAs($this->admin)->post(route('conteos.store'), ['fecha' => '2026-11-17']);
    $segundo = Conteo::query()->where('numero', 2)->sole();
    $this->actingAs($this->admin)->post(route('conteos.guardar', [$segundo, 'aplicaciones']), ['filas' => aplicacionesDelEjemplo($this)])
        ->assertSessionHasNoErrors();

    expect($segundo->aplicaciones()->orderBy('id')->pluck('conteo_cuenta_id')->all())->toBe($cuentas);

    // El conteo No. 1 ya no se modifica, pero sus cuentas sí se pueden corregir.
    $primeraLinea = $primero->aplicaciones()->orderBy('id')->first();
    $this->actingAs($this->admin)->post(route('conteos.guardar', [$primero, 'comprobante']), ['cuentas' => [$primeraLinea->id => ($this->cuenta)('11100507')]])
        ->assertSessionHasNoErrors();
    expect($primeraLinea->fresh()->conteo_cuenta_id)->toBe(($this->cuenta)('11100507'));

    // Comprobante del periodo, en pantalla y en Excel.
    $periodo = $this->actingAs($this->admin)->get(route('conteos.comprobante.index', ['desde' => '2026-11-01', 'hasta' => '2026-11-30']))
        ->assertOk()->assertSee('CONTEO No. 1')->assertSee('CONTEO No. 2')->assertSee('Sumas iguales')
        ->viewData('periodo');
    expect($periodo['debito'])->toBe(21000000.0)->and($periodo['credito'])->toBe(21000000.0);

    $respuesta = $this->actingAs($this->admin)->get(route('conteos.comprobante.exportar', ['desde' => '2026-11-01', 'hasta' => '2026-11-30']))
        ->assertOk()->assertDownload('comprobante-conteo-2026-11-01-a-2026-11-30.xlsx');
    $archivo = tempnam(sys_get_temp_dir(), 'cc');
    file_put_contents($archivo, $respuesta->streamedContent());
    $valores = collect(IOFactory::load($archivo)->getActiveSheet()->toArray())->flatten()->filter()->values();
    unlink($archivo);
    expect($valores)->toContain('COMPROBANTE CONTABLE DE CONTEO', '11100507', 'ABONO A CUENTA CON CONTEO No.2', 'SUMAS IGUALES', 'CONTEO No.1');

    // Solo administración.
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);
    $this->actingAs($islero)->get(route('conteos.comprobante.index'))->assertForbidden();
});

test('la tabla de cuentas se amplia y no se elimina una cuenta ya usada', function () {
    $this->actingAs($this->admin)->post(route('conteos.cuentas.store'), ['cuenta' => '210518', 'concepto' => 'cuota credito vehiculo', 'tercero' => 'Banco de Bogota', 'nit' => '860002964'])
        ->assertSessionHasNoErrors();
    expect(ConteoCuenta::query()->where('cuenta', '210518')->sole())->concepto->toBe('CUOTA CREDITO VEHICULO')->tercero->toBe('BANCO DE BOGOTA');

    $this->actingAs($this->admin)->post(route('conteos.cuentas.store'), ['cuenta' => '21-05', 'concepto' => 'X'])->assertSessionHasErrors('cuenta');
    $this->actingAs($this->admin)->post(route('conteos.cuentas.store'), ['cuenta' => '210518', 'concepto' => 'CUOTA CREDITO VEHICULO'])->assertSessionHasErrors('concepto');

    $this->actingAs($this->admin)->post(route('conteos.store'), ['fecha' => '2026-11-17', 'consignacion_anterior' => '100', 'saldo_moneda_inicial' => '0']);
    $this->actingAs($this->admin)->post(route('conteos.guardar', [Conteo::query()->sole(), 'aplicaciones']), ['filas' => aplicacionesDelEjemplo($this, [($this->cuenta)('23050501')])]);

    $this->actingAs($this->admin)->delete(route('conteos.cuentas.destroy', ($this->cuenta)('23050501')))->assertSessionHasErrors('catalogo');
    $this->actingAs($this->admin)->delete(route('conteos.cuentas.destroy', ($this->cuenta)('210518')))->assertSessionHasNoErrors();
    expect(ConteoCuenta::query()->where('cuenta', '210518')->exists())->toBeFalse();

    $this->actingAs($this->admin)->get(route('conteos.catalogos'))->assertOk()->assertSee('TABLA DE CUENTAS, CONCEPTOS, TERCEROS Y NIT');
});
