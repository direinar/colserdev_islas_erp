<?php

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

test('el saldo inicial entra al saldo anterior del estado de cuenta', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    $this->actingAs($admin)->post(route('cartera-saldos-iniciales.store'), [
        'customer_id' => $cliente->id,
        'fecha' => '2026-10-31',
        'valor' => '1.250.000',
    ])->assertRedirect(route('cartera-saldos-iniciales.index'));

    $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => '2026-11-03',
        'numero_turno' => 1,
        'cartera' => [['cartera_factura_no' => 'F-1', 'cliente_id' => $cliente->id, 'cartera_valor' => '100.000']],
    ]);

    $response = $this->actingAs($admin)->get(route('cartera.index', [
        'customer_id' => $cliente->id,
        'fecha_inicial' => '2026-11-01',
        'fecha_final' => '2026-11-30',
    ]));

    expect($response->viewData('saldoAnterior'))->toBe(1250000.0)
        ->and($response->viewData('saldoFinal'))->toBe(1350000.0);
});

test('un saldo inicial negativo es saldo a favor del cliente', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    $this->actingAs($admin)->post(route('cartera-saldos-iniciales.store'), [
        'customer_id' => $cliente->id,
        'fecha' => '2026-10-31',
        'valor' => '-80.000',
    ]);

    $saldo = CarteraMovimiento::sole();

    expect($saldo->origen)->toBe(CarteraMovimiento::ORIGEN_SALDO_INICIAL)
        ->and((float) $saldo->saldo_inicial)->toBe(-80000.0)
        ->and((float) $saldo->abonos)->toBe(80000.0)
        ->and((float) $saldo->vr_neto_cargo)->toBe(0.0)
        ->and((float) $saldo->saldo)->toBe(-80000.0);
});

test('cada cliente tiene un solo saldo inicial que se edita y se elimina', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    $this->actingAs($admin)->post(route('cartera-saldos-iniciales.store'), [
        'customer_id' => $cliente->id, 'fecha' => '2026-10-31', 'valor' => '100.000',
    ]);
    $this->actingAs($admin)->post(route('cartera-saldos-iniciales.store'), [
        'customer_id' => $cliente->id, 'fecha' => '2026-10-31', 'valor' => '200.000',
    ])->assertSessionHasErrors('customer_id');

    $saldo = CarteraMovimiento::sole();

    $this->actingAs($admin)->put(route('cartera-saldos-iniciales.update', $saldo), [
        'fecha' => '2026-10-30', 'valor' => '300.000',
    ])->assertRedirect(route('cartera-saldos-iniciales.index'));

    $saldo->refresh();

    expect($saldo->fecha->toDateString())->toBe('2026-10-30')
        ->and((float) $saldo->saldo)->toBe(300000.0);

    $this->actingAs($admin)->delete(route('cartera-saldos-iniciales.destroy', $saldo))->assertRedirect();

    expect(CarteraMovimiento::count())->toBe(0);
});

test('los movimientos de planilla no se editan ni eliminan como saldo inicial', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => '2026-11-03',
        'numero_turno' => 1,
        'cartera' => [['cartera_factura_no' => 'F-1', 'cliente_id' => $cliente->id, 'cartera_valor' => '100.000']],
    ]);
    $credito = CarteraMovimiento::sole();

    $this->actingAs($admin)->delete(route('cartera-saldos-iniciales.destroy', $credito))->assertNotFound();

    expect(CarteraMovimiento::count())->toBe(1);
});

test('los saldos iniciales se importan desde excel y se informan las filas con error', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $arkatec = Customer::create(['name' => 'ARKATEC SAS', 'document' => '900306424']);
    $otro = Customer::create(['name' => 'Cliente B', 'document' => '7184725']);

    $libro = new Spreadsheet;
    $libro->getActiveSheet()->fromArray([
        ['NIT', 'FECHA CORTE', 'VALOR'],
        ['900.306.424', '2026-10-31', '1.500.000'],
        ['7184725', '31/10/2026', -25000],
        ['999', '2026-10-31', 1000],
        ['7184725', 'no es fecha', 1000],
    ]);
    $archivo = tempnam(sys_get_temp_dir(), 'saldos').'.xlsx';
    (new Xlsx($libro))->save($archivo);

    $this->actingAs($admin)->post(route('cartera-saldos-iniciales.importar'), [
        'archivo' => new UploadedFile($archivo, 'saldos.xlsx', null, null, true),
    ])->assertRedirect(route('cartera-saldos-iniciales.index'))
        ->assertSessionHas('success', 'Importación terminada: 2 saldo(s) cargado(s).')
        ->assertSessionHas('errores_importacion', [
            'Fila 4: no existe un cliente con NIT/documento "999".',
            'Fila 5: la fecha de corte no es válida.',
        ]);

    unlink($archivo);

    $saldos = CarteraMovimiento::query()->where('origen', CarteraMovimiento::ORIGEN_SALDO_INICIAL)->get()->keyBy('customer_id');

    expect($saldos)->toHaveCount(2)
        ->and((float) $saldos[$arkatec->id]->saldo_inicial)->toBe(1500000.0)
        ->and($saldos[$otro->id]->fecha->toDateString())->toBe('2026-10-31')
        ->and((float) $saldos[$otro->id]->saldo_inicial)->toBe(-25000.0);
});

test('solo el administrador gestiona los saldos iniciales', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);
    $cliente = Customer::create(['name' => 'Cliente A', 'document' => '1']);

    $this->actingAs($jefe)->get(route('cartera-saldos-iniciales.index'))->assertForbidden();
    $this->actingAs($jefe)->post(route('cartera-saldos-iniciales.store'), [
        'customer_id' => $cliente->id, 'fecha' => '2026-10-31', 'valor' => '1',
    ])->assertForbidden();

    expect(CarteraMovimiento::count())->toBe(0);
});

test('la plantilla trae todos los clientes y al importarla se ignoran los que no tienen saldo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $arkatec = Customer::create(['name' => 'ARKATEC SAS', 'document' => '900306424']);
    Customer::create(['name' => 'Cliente sin saldo', 'document' => '7184725']);

    $response = $this->actingAs($admin)->get(route('cartera-saldos-iniciales.plantilla'));
    $response->assertOk()->assertDownload('plantilla-saldos-iniciales.xlsx');

    $archivo = tempnam(sys_get_temp_dir(), 'plantilla').'.xlsx';
    file_put_contents($archivo, $response->streamedContent());
    $libro = IOFactory::load($archivo);
    $hoja = $libro->getActiveSheet();

    expect((string) $hoja->getCell('A2')->getValue())->toBe('900306424')
        ->and($hoja->getCell('D2')->getValue())->toBe('ARKATEC SAS')
        ->and($hoja->getCell('D3')->getValue())->toBe('Cliente sin saldo');

    $hoja->setCellValue('B2', '2026-10-31')->setCellValue('C2', 500000);
    (new Xlsx($libro))->save($archivo);

    $this->actingAs($admin)->post(route('cartera-saldos-iniciales.importar'), [
        'archivo' => new UploadedFile($archivo, 'saldos.xlsx', null, null, true),
    ])->assertSessionHas('success', 'Importación terminada: 1 saldo(s) cargado(s).')
        ->assertSessionHas('errores_importacion', []);

    unlink($archivo);

    expect((float) CarteraMovimiento::sole()->saldo_inicial)->toBe(500000.0)
        ->and(CarteraMovimiento::sole()->customer_id)->toBe($arkatec->id);
});
