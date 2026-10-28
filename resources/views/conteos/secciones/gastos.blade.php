@extends('layouts.app')

@section('title', 'Conteo - Cuadre del conteo')

@section('content')
    @include('conteos.partials.encabezado')

    <form method="POST" action="{{ route('conteos.guardar', [$conteo, 'gastos']) }}">
        @csrf
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header fw-bold">GASTOS</div>
                    <div class="card-body">
                        @include('conteos.partials.filas', [
                            'filas' => $conteo->movimientos->where('tipo', \App\Models\ConteoMovimiento::TIPO_GASTO),
                            'columnas' => ['concepto' => 'CONCEPTO'],
                            'totalEtiqueta' => 'TOTAL GASTOS',
                            'total' => $resumen['gastos'],
                        ])
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header fw-bold">CUADRE DE ESTE CONTEO</div>
                    <div class="card-body">
                        <table class="table table-bordered table-sm mb-0 conteo-tabla">
                            <tbody>
                                <tr>
                                    <th>TOTAL BILLETES <span class="small fw-normal">(cuadre de efectivo)</span></th>
                                    <td class="text-end">{{ number_format($resumen['billetes'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>TOTAL GASTOS</th>
                                    <td class="text-end">{{ number_format($resumen['gastos'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="total">
                                    <th>A CONSIGNAR</th>
                                    <td @class(['text-end', 'text-danger' => round($resumen['a_consignar']) < 0])>{{ number_format($resumen['a_consignar'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="small text-muted mt-2 mb-0">A consignar = total billetes − total gastos. Se actualiza al GUARDAR.</p>
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
