@extends('layouts.app')

@section('title', 'Conteo - Control de moneda')

@section('content')
    @include('conteos.partials.encabezado')

    <form method="POST" action="{{ route('conteos.guardar', [$conteo, 'moneda']) }}">
        @csrf
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header fw-bold">RETIROS DE MONEDA</div>
                    <div class="card-body">
                        @include('conteos.partials.filas', [
                            'filas' => $conteo->movimientos->where('tipo', \App\Models\ConteoMovimiento::TIPO_RETIRO_MONEDA),
                            'columnas' => ['concepto' => 'CONCEPTO'],
                            'totalEtiqueta' => 'TOTAL RETIROS DE MONEDA',
                            'total' => $resumen['retiros_moneda'],
                        ])
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header fw-bold">SALDOS</div>
                    <div class="card-body">
                        <table class="table table-bordered table-sm mb-0 conteo-tabla">
                            <tbody>
                                <tr>
                                    <th>
                                        <label for="saldo-moneda-inicial" class="mb-0">Saldo que viene del conteo anterior</label>
                                        @if ($conteo->esPrimero())
                                            <div class="small fw-normal text-muted">Primer conteo: saldo inicial de la moneda.</div>
                                        @endif
                                    </th>
                                    <td class="text-end">
                                        @if ($conteo->esPrimero())
                                            <input type="text" name="saldo_moneda_inicial" id="saldo-moneda-inicial" inputmode="decimal"
                                                class="form-control form-control-sm text-end" @disabled(! $editable)
                                                value="{{ number_format((float) $conteo->saldo_moneda_inicial, 0, ',', '.') }}">
                                        @else
                                            {{ number_format($resumen['saldo_moneda_anterior'], 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Más: moneda del presente conteo <span class="small fw-normal">(cuadre de efectivo)</span></th>
                                    <td class="text-end">{{ number_format($resumen['moneda'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>Menos: retiros</th>
                                    <td class="text-end">{{ number_format($resumen['retiros_moneda'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="total">
                                    <th>Saldo que pasa</th>
                                    <td @class(['text-end', 'text-danger' => round($resumen['saldo_moneda_pasa']) < 0])>{{ number_format($resumen['saldo_moneda_pasa'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="small text-muted mt-2 mb-0">El saldo que pasa es el que viene en el próximo conteo. Se
                            actualiza al GUARDAR.</p>
                    </div>
                </div>
            </div>
        </div>

        @if ($editable)
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> GUARDAR</button>
            </div>
        @endif
    </form>
@endsection
