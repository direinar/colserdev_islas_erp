@extends('layouts.app')

@section('title', 'Comisiones de isleros')

@php
    use Illuminate\Support\Carbon;

    $pesos = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $galones = fn (float $valor): string => number_format($valor, 3, '.', ',');
    $porcentaje = fn ($valor): string => rtrim(rtrim(number_format((float) $valor, 3, ',', '.'), '0'), ',');
@endphp

@section('content')
    <style>
        .com-tabla {
            border-collapse: collapse;
            width: 100%;
            font-size: .85rem;
        }

        .com-tabla th,
        .com-tabla td {
            border: 1px solid #8c8c8c;
            padding: .3rem .5rem;
            white-space: nowrap;
        }

        .com-tabla thead th {
            text-align: center;
            background: #fce4d6;
        }

        .com-tabla thead .com-comision {
            background: #e2efda;
        }

        .com-tabla td.num {
            text-align: right;
        }

        .com-tabla tfoot td {
            font-weight: 800;
            background: #fff2cc;
        }

        @media print {
            nav,
            .navbar {
                display: none !important;
            }

            .com-tabla {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <x-page-header title="Ventas y comisiones de isleros" icon="people"
        :subtitle="'Desde '.Carbon::parse($desde)->format('d/m/Y').' — hasta '.Carbon::parse($hasta)->format('d/m/Y').'. Ventas de las planillas de turno de cada islero (sin las anuladas).'">
        @include('informes.partials.filtro-rango', [
            'ruta' => 'informes.comisiones.index',
            'rutaExportar' => 'informes.comisiones.exportar',
        ])
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger d-print-none">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Porcentajes iguales para todos los isleros, sobre el valor vendido. --}}
    <div class="card mb-3 d-print-none">
        <form method="POST" action="{{ route('informes.comisiones.porcentajes') }}"
            class="card-body py-2 d-flex flex-wrap align-items-end gap-3">
            @csrf
            <input type="hidden" name="desde" value="{{ $desde }}">
            <input type="hidden" name="hasta" value="{{ $hasta }}">
            <div class="fw-bold small">% de comisión<br><span class="fw-normal text-muted">igual para todos los isleros</span></div>
            <div>
                <label class="form-label small mb-0" for="porcentaje-combustible">COMBUSTIBLE (%)</label>
                <input type="text" inputmode="decimal" name="combustible" id="porcentaje-combustible"
                    class="form-control form-control-sm text-end" style="width: 8rem"
                    value="{{ old('combustible', $porcentaje($porcentajes->combustible)) }}" required>
            </div>
            <div>
                <label class="form-label small mb-0" for="porcentaje-canastilla">CANASTILLA (%)</label>
                <input type="text" inputmode="decimal" name="canastilla" id="porcentaje-canastilla"
                    class="form-control form-control-sm text-end" style="width: 8rem"
                    value="{{ old('canastilla', $porcentaje($porcentajes->canastilla)) }}" required>
            </div>
            <button type="submit" class="btn btn-outline-primary btn-sm">Guardar porcentajes</button>
        </form>
    </div>

    <div class="card mb-3">
        <div class="table-responsive titulos-fijos">
            <table class="com-tabla">
                <thead>
                    <tr>
                        <th rowspan="2">ISLERO</th>
                        <th rowspan="2">PLANILLAS</th>
                        <th colspan="3">COMBUSTIBLE ({{ $porcentaje($porcentajes->combustible) }}%)</th>
                        <th colspan="2">CANASTILLA ({{ $porcentaje($porcentajes->canastilla) }}%)</th>
                        <th rowspan="2" class="com-comision">TOTAL<br>COMISIÓN</th>
                    </tr>
                    <tr>
                        <th>GALONES</th>
                        <th>VENTA</th>
                        <th class="com-comision">COMISIÓN</th>
                        <th>VENTA</th>
                        <th class="com-comision">COMISIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $fila)
                        <tr>
                            <td>{{ $fila['islero'] }}</td>
                            <td class="num">{{ $fila['planillas'] }}</td>
                            <td class="num">{{ $galones($fila['galones']) }}</td>
                            <td class="num">{{ $pesos($fila['combustible']) }}</td>
                            <td class="num">{{ $pesos($fila['comision_combustible']) }}</td>
                            <td class="num">{{ $pesos($fila['canastilla']) }}</td>
                            <td class="num">{{ $pesos($fila['comision_canastilla']) }}</td>
                            <td class="num fw-bold">{{ $pesos($fila['comision_total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No hay planillas de turno en este periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($filas !== [])
                    <tfoot>
                        <tr>
                            <td>TOTALES</td>
                            <td class="num">{{ $totales['planillas'] }}</td>
                            <td class="num">{{ $galones($totales['galones']) }}</td>
                            <td class="num">{{ $pesos($totales['combustible']) }}</td>
                            <td class="num">{{ $pesos($totales['comision_combustible']) }}</td>
                            <td class="num">{{ $pesos($totales['canastilla']) }}</td>
                            <td class="num">{{ $pesos($totales['comision_canastilla']) }}</td>
                            <td class="num">{{ $pesos($totales['comision_total']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="small text-muted">Combustible: valor vendido según cierres de IAPROPIADA (corriente + ACPM). Canastilla:
        total vendido con IVA. Cada planilla cuenta para el vendedor registrado en ella.</div>
@endsection
