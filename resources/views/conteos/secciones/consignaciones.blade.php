@extends('layouts.app')

@section('title', 'Conteo - Consignaciones internas')

@section('content')
    @include('conteos.partials.encabezado')

    @php
        $pesos = fn ($valor): string => number_format((float) $valor, 0, ',', '.');
        $sinPlanilla = $conteo->consignaciones->whereNull('turno_id')->count();
    @endphp

    <form method="POST" action="{{ route('conteos.guardar', [$conteo, 'consignaciones']) }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header fw-bold">DETALLE DE NUMERACIÓN DE CONSIGNACIONES DE COMBUSTIBLE</div>
            <div class="card-body">
                <p class="small text-muted mb-3">El primer número sigue al último del conteo anterior; se digita el
                    <strong>último No.</strong> de este conteo y el sistema genera los intermedios y trae el valor de cada uno
                    de las planillas de turno (CONSIGNACIONES). El último No. de este conteo es el punto de partida del próximo.</p>

                <div class="row g-3 align-items-end">
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold" for="consignacion-anterior">ÚLTIMO No. DEL CONTEO ANTERIOR</label>
                        @if ($conteo->esPrimero())
                            {{-- Primer conteo: el punto de partida se digita (como un saldo inicial). --}}
                            <input type="number" name="consignacion_anterior" id="consignacion-anterior" min="0"
                                class="form-control" value="{{ $resumen['consignacion_anterior'] }}" @disabled(! $editable)>
                        @else
                            <input type="text" id="consignacion-anterior" class="form-control" readonly
                                value="{{ $resumen['consignacion_anterior'] }}">
                        @endif
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold" for="ultima-consignacion">ÚLTIMO No. DE ESTE CONTEO</label>
                        <input type="number" name="ultima_consignacion" id="ultima-consignacion" min="1" class="form-control"
                            value="{{ old('ultima_consignacion', $conteo->ultima_consignacion) }}" @disabled(! $editable) required>
                    </div>
                    @if ($editable)
                        <div class="col-sm-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i> GUARDAR Y TRAER VALORES
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>

    @if ($sinPlanilla > 0)
        <div class="alert alert-warning">
            {{ $sinPlanilla }} número(s) no están en ninguna planilla de turno (en rojo, con valor 0). Revise que la
            consignación tenga su No. en la planilla y vuelva a GUARDAR Y TRAER VALORES.
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 conteo-tabla">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>VALOR</th>
                        <th>PLANILLA DE TURNO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-warning">
                        <td>{{ $resumen['consignacion_anterior'] }}</td>
                        <td colspan="2" class="small">Último No. del conteo anterior</td>
                    </tr>
                    @foreach ($conteo->consignaciones as $consignacion)
                        <tr @class(['table-danger' => ! $consignacion->turno_id])>
                            <td>{{ $consignacion->numero }}</td>
                            <td class="text-end">{{ $pesos($consignacion->valor) }}</td>
                            <td>
                                @if ($consignacion->turno)
                                    <a href="{{ route('turnos.create', ['turno_busqueda' => $consignacion->turno->numero_turno]) }}">
                                        Turno #{{ $consignacion->turno->numero_turno }} — {{ $consignacion->turno->fecha->format('d/m/Y') }}
                                    </a>
                                @else
                                    <span class="text-danger fw-semibold">No está en ninguna planilla</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total">
                        <td>TOTAL CONSIGNACIONES INTERNAS</td>
                        <td class="text-end">{{ $pesos($resumen['total_consignaciones']) }}</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>TOTAL CIERRE <span class="small">(cuadre de efectivo)</span></td>
                        <td class="text-end">{{ $pesos($resumen['total_cierre']) }}</td>
                        <td></td>
                    </tr>
                    <tr class="total">
                        <td>DIFERENCIA <span class="small fw-normal">(total cierre − consignaciones internas)</span></td>
                        <td @class(['text-end', 'text-danger' => round($resumen['diferencia']) < 0])>{{ $pesos($resumen['diferencia']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
