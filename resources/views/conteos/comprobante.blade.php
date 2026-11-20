@extends('layouts.app')

@section('title', 'Comprobante contable de conteo')

@php
    use Illuminate\Support\Carbon;

    $pesos = fn ($valor): string => round((float) $valor, 2) == 0.0 ? '' : number_format((float) $valor, 0, ',', '.');
    $cuadrado = round($periodo['debito'] - $periodo['credito'], 2) == 0.0;
@endphp

@section('content')
    @include('informes.partials.impresion')

    <style>
        .cc-tabla th { background: #eeece1; text-align: center; }
        .cc-tabla td.num { text-align: right; white-space: nowrap; }
        .cc-tabla tr.cc-conteo td { background: #f2f2f2; font-weight: 700; }
        .cc-tabla tr.cc-total td { background: #fff2cc; font-weight: 800; }
        .cc-tabla tr.cc-sin-cuenta td { background: #fff3cd; }
    </style>

    <x-page-header title="Comprobante contable de conteo" icon="journal-text"
        :subtitle="'DESDE '.Carbon::parse($desde)->format('d/m/Y').' HASTA '.Carbon::parse($hasta)->format('d/m/Y').'. Débitos por la consignación en bancos y otras aplicaciones; crédito a caja por el total consignado.'">
        @include('informes.partials.filtro-rango', ['ruta' => 'conteos.comprobante.index', 'rutaExportar' => 'conteos.comprobante.exportar'])
    </x-page-header>

    <div class="d-flex flex-wrap gap-2 mb-2 d-print-none">
        <span class="badge {{ $cuadrado ? 'text-bg-success' : 'text-bg-danger' }}">{{ $cuadrado ? 'Sumas iguales' : 'Descuadre' }}</span>
        <span class="badge text-bg-light border">{{ count($periodo['comprobantes']) }} conteo(s)</span>
        @if ($periodo['sin_cuenta'] > 0)
            <span class="badge text-bg-warning">{{ $periodo['sin_cuenta'] }} línea(s) sin cuenta contable: asígnela en el comprobante del conteo</span>
        @endif
        <a href="{{ route('conteos.catalogos') }}#cuentas" class="small ms-auto">Tabla de cuentas, conceptos, terceros y NIT</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm cc-tabla" id="comprobante-conteo">
            <thead>
                <tr>
                    <th>CUENTA</th>
                    <th>CONCEPTO</th>
                    <th>TERCERO</th>
                    <th>NIT</th>
                    <th>DÉBITO</th>
                    <th>CRÉDITO</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($periodo['comprobantes'] as $comprobante)
                    <tr class="cc-conteo">
                        <td colspan="6">
                            CONTEO No. {{ $comprobante['conteo']->numero }} — {{ $comprobante['conteo']->fecha->format('d/m/Y') }}
                            <a href="{{ route('conteos.seccion', [$comprobante['conteo'], 'comprobante']) }}" class="small fw-normal ms-2 d-print-none">Asignar cuentas</a>
                        </td>
                    </tr>
                    @foreach ($comprobante['lineas'] as $linea)
                        <tr @class(['cc-sin-cuenta' => $linea['sin_cuenta']])>
                            <td>{!! $linea['sin_cuenta'] ? '<span class="text-danger small">SIN CUENTA</span>' : e($linea['cuenta']) !!}</td>
                            <td>{{ $linea['concepto'] }}</td>
                            <td>{{ $linea['tercero'] }}</td>
                            <td>{{ $linea['nit'] }}</td>
                            <td class="num">{{ $pesos($linea['debito']) }}</td>
                            <td class="num">{{ $pesos($linea['credito']) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No hay conteos con consignaciones en el periodo.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="cc-total">
                    <td colspan="4" class="text-end">SUMAS IGUALES</td>
                    <td class="num">{{ number_format($periodo['debito'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($periodo['credito'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
