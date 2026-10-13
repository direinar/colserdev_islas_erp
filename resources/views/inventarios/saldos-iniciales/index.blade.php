@extends('layouts.app')

@section('title', 'Saldos iniciales de inventario')

@php
    use App\Models\InventarioSaldoInicial;

    $cantidad = fn (?InventarioSaldoInicial $saldo): string => $saldo ? rtrim(rtrim(number_format((float) $saldo->unidades, 3, ',', '.'), '0'), ',') : '';
    $costo = fn (?InventarioSaldoInicial $saldo): string => $saldo ? rtrim(rtrim(number_format((float) $saldo->costo_unitario, 2, ',', '.'), '0'), ',') : '';
    $valor = fn (?InventarioSaldoInicial $saldo): string => $saldo ? number_format((float) $saldo->unidades * (float) $saldo->costo_unitario, 0, ',', '.') : '';

    $filas = collect([
        ['inventario' => InventarioSaldoInicial::GASOLINA, 'referencia' => '', 'nombre' => 'GASOLINA (CORRIENTE)', 'unidad' => 'Galones'],
        ['inventario' => InventarioSaldoInicial::ACPM, 'referencia' => '', 'nombre' => 'ACPM', 'unidad' => 'Galones'],
    ])->concat($referencias->map(fn (string $referencia): array => [
        'inventario' => InventarioSaldoInicial::CANASTILLA, 'referencia' => $referencia, 'nombre' => $referencia, 'unidad' => 'Unidades',
    ]));
@endphp

@section('content')
    <x-page-header title="Saldos iniciales de inventario" icon="box-seam"
        subtitle="Existencia y costo unitario con que arranca cada kardex (gasolina, ACPM y canastilla). Se cargan una sola vez, al iniciar; desde ahí el inventario se mueve con compras y planillas de turno." />

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('inventarios-saldos-iniciales.store') }}">
        @csrf

        <x-erp-card title="SALDOS INICIALES">
            <div class="p-3 pb-0 d-flex flex-wrap gap-3 align-items-end">
                <div>
                    <label for="fecha_corte" class="form-label small mb-1">Fecha de corte</label>
                    <input type="date" id="fecha_corte" name="fecha_corte" class="form-control form-control-sm" required
                        value="{{ old('fecha_corte', $fechaCorte) }}" aria-describedby="ayuda-fecha-corte">
                </div>
                <div id="ayuda-fecha-corte" class="small text-muted pb-1" style="max-width: 46rem;">
                    Existencia al iniciar ese día. Las compras y ventas anteriores a la fecha de corte no cuentan en el kardex.
                    Deje en blanco la cantidad de un producto para que su kardex arranque en cero.
                </div>
            </div>

            <div class="table-responsive p-3">
                <table class="table table-bordered table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>INVENTARIO</th>
                            <th class="text-center" style="width: 11rem;">CANTIDAD</th>
                            <th class="text-center" style="width: 11rem;">COSTO UNITARIO</th>
                            <th class="text-center" style="width: 11rem;">VALOR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filas as $indice => $fila)
                            @php($saldo = $saldos->get($fila['inventario'].'|'.$fila['referencia']))
                            <tr>
                                <td>
                                    {{ $fila['nombre'] }}
                                    <span class="text-muted small">({{ $fila['unidad'] }})</span>
                                    <input type="hidden" name="saldos[{{ $indice }}][inventario]" value="{{ $fila['inventario'] }}">
                                    <input type="hidden" name="saldos[{{ $indice }}][referencia]" value="{{ $fila['referencia'] }}">
                                </td>
                                <td>
                                    <input type="text" name="saldos[{{ $indice }}][unidades]" class="form-control form-control-sm text-end"
                                        inputmode="decimal" aria-label="Cantidad de {{ $fila['nombre'] }}"
                                        value="{{ old('saldos.'.$indice.'.unidades', $cantidad($saldo)) }}">
                                </td>
                                <td>
                                    <input type="text" name="saldos[{{ $indice }}][costo_unitario]" class="form-control form-control-sm text-end"
                                        inputmode="decimal" aria-label="Costo unitario de {{ $fila['nombre'] }}"
                                        value="{{ old('saldos.'.$indice.'.costo_unitario', $costo($saldo)) }}">
                                </td>
                                <td class="text-end">{{ $valor($saldo) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-3 pb-3 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-save" aria-hidden="true"></i> Guardar saldos iniciales
                </button>
            </div>
        </x-erp-card>
    </form>

    <div class="small text-muted">
        El saldo de los tanques para el informe Control de tanques se carga aparte, en
        <a href="{{ route('control-tanques.index') }}">Informes → Control de tanques</a>.
    </div>
@endsection
