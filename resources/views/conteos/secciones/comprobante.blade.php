@extends('layouts.app')

@section('title', 'Conteo - Comprobante contable')

@section('content')
    @include('conteos.partials.encabezado')

    @php
        $pesos = fn ($valor): string => round((float) $valor, 2) == 0.0 ? '' : number_format((float) $valor, 0, ',', '.');
        $cuadrado = round($comprobante['debito'] - $comprobante['credito'], 2) == 0.0;
    @endphp

    <form method="POST" action="{{ route('conteos.guardar', [$conteo, 'comprobante']) }}">
        @csrf
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <strong>COMPROBANTE CONTABLE DEL CONTEO No. {{ $conteo->numero }}</strong>
                <span class="d-flex flex-wrap gap-2">
                    <a href="{{ route('conteos.comprobante.index', ['desde' => $conteo->fecha->toDateString(), 'hasta' => $conteo->fecha->toDateString()]) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-printer" aria-hidden="true"></i> Imprimir / Excel
                    </a>
                    <a href="{{ route('conteos.comprobante.index') }}" class="btn btn-sm btn-outline-secondary">Comprobantes del periodo</a>
                    <a href="{{ route('conteos.catalogos') }}#cuentas" class="btn btn-sm btn-outline-secondary">Tabla de cuentas</a>
                </span>
            </div>
            <div class="card-body">
                @if ($comprobante['lineas'] === [])
                    <div class="text-center text-muted py-4">
                        Este conteo no tiene consignaciones en bancos ni otras aplicaciones.
                        <a href="{{ route('conteos.seccion', [$conteo, 'gastos']) }}#aplicaciones">Regístrelas en «Cuadre del conteo» → Consignación en bancos</a>.
                    </div>
                @else
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @if ($cuadrado)
                            <span class="badge text-bg-success"><i class="bi bi-check-circle" aria-hidden="true"></i> Sumas iguales</span>
                        @else
                            <span class="badge text-bg-danger">Descuadre</span>
                        @endif
                        @if ($comprobante['sin_cuenta'] > 0)
                            <span class="badge text-bg-warning" id="lineas-sin-cuenta">{{ $comprobante['sin_cuenta'] }} línea(s) sin cuenta: elíjala y guarde</span>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 conteo-tabla" id="tabla-comprobante">
                            <thead>
                                <tr>
                                    <th style="min-width: 16rem;">CUENTA</th>
                                    <th>CONCEPTO</th>
                                    <th>TERCERO</th>
                                    <th>NIT</th>
                                    <th>DÉBITO</th>
                                    <th>CRÉDITO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($comprobante['lineas'] as $linea)
                                    <tr @class(['table-warning' => $linea['sin_cuenta']])>
                                        <td>
                                            @if ($linea['aplicacion_id'])
                                                @php($aplicacion = $conteo->aplicaciones->firstWhere('id', $linea['aplicacion_id']))
                                                <select name="cuentas[{{ $linea['aplicacion_id'] }}]" class="form-select form-select-sm" aria-label="Cuenta contable">
                                                    <option value="">Seleccione la cuenta…</option>
                                                    @foreach ($cuentas as $cuenta)
                                                        <option value="{{ $cuenta->id }}" @selected($aplicacion?->conteo_cuenta_id === $cuenta->id)>{{ $cuenta->etiqueta() }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="small text-muted mt-1">
                                                    {{ $aplicacion?->destino?->nombre ?? 'Sin destino' }} · {{ $aplicacion?->tercero?->nombre ?? 'Sin tercero' }}
                                                </div>
                                            @else
                                                <strong>{{ $linea['cuenta'] }}</strong>
                                                <div class="small text-muted">Caja · fijo por el total consignado</div>
                                            @endif
                                        </td>
                                        <td>{{ $linea['concepto'] }}</td>
                                        <td>{{ $linea['tercero'] }}</td>
                                        <td>{{ $linea['nit'] }}</td>
                                        <td class="text-end">{{ $pesos($linea['debito']) }}</td>
                                        <td class="text-end">{{ $pesos($linea['credito']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="total">
                                    <td colspan="4" class="text-end">SUMAS IGUALES</td>
                                    <td class="text-end">{{ number_format($comprobante['debito'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($comprobante['credito'], 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        Cada línea sale de «Consignación en bancos y otras aplicaciones». La cuenta, el tercero y el NIT vienen de la
                        tabla de cuentas y el concepto termina siempre en «CON CONTEO No. {{ $conteo->numero }}». Al registrar una línea
                        nueva, el sistema propone la última cuenta usada con el mismo destino y tercero.
                    </p>
                @endif
            </div>
        </div>

        @if ($comprobante['lineas'] !== [])
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> GUARDAR CUENTAS</button>
            </div>
        @endif
    </form>
@endsection
