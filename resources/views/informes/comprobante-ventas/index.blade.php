@extends('layouts.app')

@section('title', 'Comprobante contable de ventas')

@php
    use Illuminate\Support\Carbon;

    $pesos = fn(float $valor): string => $valor ? number_format($valor, 0, ',', '.') : '';
    $pesosSiempre = fn(float $valor): string => number_format($valor, 0, ',', '.');
@endphp

@section('content')
    <style>
        .cc-tabla {
            border-collapse: collapse;
            font-size: .78rem;
            color: #000;
            width: 100%;
        }

        .cc-tabla th,
        .cc-tabla td {
            border: 1px solid #8c8c8c;
            padding: .2rem .4rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        .cc-tabla thead th {
            text-align: center;
            font-weight: 700;
            font-size: .7rem;
            line-height: 1.15;
        }

        .cc-tabla .cc-grupo {
            font-size: .95rem;
            letter-spacing: .05rem;
        }

        .cc-tabla td.num {
            text-align: right;
        }

        .cc-tabla .cc-negativo {
            color: #ff0000;
        }

        .cc-tabla tfoot td {
            font-weight: 800;
            background: #fff2cc;
        }

        @page {
            size: landscape;
            margin: 10mm;
        }

        @media print {

            nav,
            .navbar {
                display: none !important;
            }

            body>.container-fluid {
                padding: 0 !important;
            }

            .card,
            .card-body {
                border: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .cc-tabla {
                font-size: 8px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <x-page-header title="Comprobante contable de ventas" icon="journal-text" :subtitle="'Desde ' .
        Carbon::parse($desde)->format('d/m/Y') .
        ' — hasta ' .
        Carbon::parse($hasta)->format('d/m/Y') .
        '. Toda la información viene de la planilla de turnos.'">
        @include('informes.partials.filtro-rango', [
            'ruta' => 'informes.comprobante-ventas.index',
            'rutaExportar' => 'informes.comprobante-ventas.exportar',
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

    {{-- Sobrantes o faltantes sin trasladar: ya están en FALTANTES/SOBRANTES (el comprobante cuadra),
         pero conviene revisarlos en su planilla. --}}
    @if (($comprobante['sin_trasladar'] ?? []) !== [])
        <div class="alert alert-warning d-print-none" id="sin-trasladar">
            <strong>Planillas con sobrante o faltante sin trasladar</strong> (incluidos en FALTANTES/SOBRANTES por
            ${{ number_format(array_sum(array_column($comprobante['sin_trasladar'], 'pendiente')), 0, ',', '.') }}).
            Revíselas y use TRASLADAR en cada una:
            <div class="small mt-1">
                @foreach ($comprobante['sin_trasladar'] as $planilla)
                    <a href="{{ route('turnos.create', ['turno_busqueda' => $planilla['numero_turno']]) }}"
                        class="me-2 text-nowrap">#{{ $planilla['numero_turno'] }}
                        ({{ \Illuminate\Support\Carbon::parse($planilla['fecha'])->format('d/m') }}):
                        <span @class(['text-danger' => $planilla['pendiente'] < 0])>{{ number_format($planilla['pendiente'], 0, ',', '.') }}</span></a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="cc-tabla">
                        <thead>
                            <tr>
                                <th colspan="6" class="cc-grupo" style="background:#ccccff;">COMPROBANTE CONTABLE DE
                                    VENTAS</th>
                            </tr>
                            <tr style="background:#ccccff;">
                                <th>CUENTA</th>
                                <th>CONCEPTO</th>
                                <th>CLIENTE/TERCERO</th>
                                <th>NIT</th>
                                <th>DEBITO</th>
                                <th>CREDITO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comprobante['lineas'] as $linea)
                                <tr>
                                    <td>{{ $linea['cuenta'] }}</td>
                                    <td>{{ $linea['concepto'] }}</td>
                                    <td>{{ $linea['tercero'] }}</td>
                                    <td>{{ $linea['nit'] }}</td>
                                    <td class="num">{{ $pesos($linea['debito']) }}</td>
                                    <td class="num">{{ $pesos($linea['credito']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end">TOTALES</td>
                                <td class="num">{{ $pesosSiempre($comprobante['debito']) }}</td>
                                <td class="num">{{ $pesosSiempre($comprobante['credito']) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end">DIFERENCIA</td>
                                <td colspan="2"
                                    class="num {{ round($comprobante['diferencia']) != 0 ? 'cc-negativo' : '' }}">
                                    {{ $pesosSiempre($comprobante['diferencia']) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @if (round($comprobante['diferencia']) != 0)
                <div class="alert alert-warning mt-2 small d-print-none">
                    El comprobante no cuadra: normalmente es un sobrante o faltante de alguna planilla que no se ha
                    trasladado.
                </div>
            @endif
        </div>

        <div class="col-12 col-xl-4 d-print-none">
            <details class="card">
                <summary class="card-body py-2 fw-semibold small" style="cursor:pointer;">Cuentas contables del comprobante
                </summary>
                <form method="POST" action="{{ route('informes.comprobante-ventas.cuentas') }}" class="card-body pt-0">
                    @csrf
                    <input type="hidden" name="desde" value="{{ $desde }}">
                    <input type="hidden" name="hasta" value="{{ $hasta }}">
                    <table class="table table-sm align-middle mb-2">
                        <tbody>
                            @foreach ($cuentas as $clave => $cuenta)
                                <tr>
                                    <td class="small">
                                        <label for="cuenta-{{ $clave }}">{{ $cuenta->descripcion }}</label>
                                    </td>
                                    <td style="width:9rem;">
                                        <input type="text" id="cuenta-{{ $clave }}"
                                            name="cuentas[{{ $clave }}][cuenta]"
                                            class="form-control form-control-sm" value="{{ $cuenta->cuenta }}" required
                                            maxlength="30">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Guardar cuentas</button>
                </form>
            </details>

            <div class="small text-muted mt-2">
                Ventas de combustible según cierres de IAPROPIADA. Costo de ventas: salidas al costo promedio de los
                inventarios de gasolina, ACPM y canastilla. Los recaudos y anticipos por islas se restan de las
                consignaciones y se contabilizan en el comprobante de Recaudos de cartera.
            </div>
        </div>
    </div>
@endsection
