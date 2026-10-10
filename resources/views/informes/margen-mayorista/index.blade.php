@extends('layouts.app')

@section('title', 'Informe Margen Mayorista')

@php
    use Illuminate\Support\Carbon;

    $pesos = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $galones = fn (float $valor): string => number_format($valor, fmod(round($valor, 3), 1.0) == 0.0 ? 0 : 2, ',', '.');
    $porcentaje = fn (float $valor): string => rtrim(rtrim(number_format($valor, 4, ',', '.'), '0'), ',');
    $rango = ['desde' => $desde, 'hasta' => $hasta];
@endphp

@section('content')
    <style>
        .mm-tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: .85rem;
            color: #000;
        }

        .mm-tabla th,
        .mm-tabla td {
            border: 1px solid #8c8c8c;
            padding: .25rem .5rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        .mm-tabla thead th {
            background: #ddebf7;
            text-align: center;
            font-weight: 700;
            font-size: .76rem;
        }

        .mm-tabla .mm-titulo-tabla {
            background: #1f4e78;
            color: #fff;
            font-size: .9rem;
        }

        .mm-tabla td.num { text-align: right; }

        .mm-tabla tfoot td {
            background: #fff2cc;
            font-weight: 800;
        }

        .mm-resultado td {
            background: #e2efda;
            font-weight: 800;
            font-size: 1.05rem;
        }

        @page {
            size: portrait;
            margin: 8mm;
        }

        @media print {
            nav,
            .navbar {
                display: none !important;
            }

            body > .container-fluid {
                padding: 0 !important;
            }

            .card,
            .card-body {
                border: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .mm-tabla {
                font-size: 10px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <x-page-header title="Informe margen mayorista" icon="percent"
        :subtitle="'Desde '.Carbon::parse($desde)->format('d/m/Y').' — hasta '.Carbon::parse($hasta)->format('d/m/Y')">
        @include('informes.partials.filtro-rango', [
            'ruta' => 'informes.margen-mayorista.index',
            'rutaExportar' => 'informes.margen-mayorista.exportar',
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

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="table-responsive">
                    <table class="mm-tabla">
                        <thead>
                            <tr>
                                <th colspan="6" class="mm-titulo-tabla">COMPRAS DE COMBUSTIBLE DEL PERIODO</th>
                            </tr>
                            <tr>
                                <th>FECHA</th>
                                <th>No. FACTURA</th>
                                <th>VR. FACTURAS</th>
                                <th>GALONES<br>GASOLINA</th>
                                <th>GALONES<br>ACPM</th>
                                <th>GALONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($facturas as $factura)
                                <tr>
                                    <td class="text-center">{{ Carbon::parse($factura['fecha'])->format('d/m/Y') }}</td>
                                    <td>{{ $factura['factura'] }}</td>
                                    <td class="num">{{ $pesos($factura['valor']) }}</td>
                                    <td class="num">{{ $galones($factura['gasolina']) }}</td>
                                    <td class="num">{{ $galones($factura['acpm']) }}</td>
                                    <td class="num">{{ $galones($factura['galones']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No hay compras de combustible en el periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-end">TOTALES</td>
                                <td class="num">{{ $pesos($totales['valor']) }}</td>
                                <td class="num">{{ $galones($totales['gasolina']) }}</td>
                                <td class="num">{{ $galones($totales['acpm']) }}</td>
                                <td class="num">{{ $galones($totales['galones']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5 d-flex flex-column gap-3">
            <div class="card">
                <table class="mm-tabla">
                    <thead>
                        <tr>
                            <th colspan="4" class="mm-titulo-tabla">MARGEN MAYORISTA DEL PERIODO</th>
                        </tr>
                        <tr>
                            <th>GALONES</th>
                            <th>% MAYORISTA</th>
                            <th>MARGEN</th>
                            <th>VALOR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($liquidacion as $grupo)
                            <tr>
                                <td class="num">{{ $galones($grupo['galones']) }}</td>
                                <td class="num">{{ $porcentaje($grupo['porcentaje']) }}</td>
                                <td class="num">{{ number_format($grupo['margen'], 2, ',', '.') }}</td>
                                <td class="num">{{ $pesos($grupo['valor']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Sin galones comprados.</td>
                            </tr>
                        @endforelse
                        <tr class="mm-resultado">
                            <td colspan="3">MARGEN MAYORISTA</td>
                            <td class="num">{{ $pesos($margen_mayorista) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="small text-muted p-2">Total galones del periodo × porcentaje de mayorista × margen por galón.
                    Este valor va al Comprobante Contable de Compras como descuentos compras.</div>
            </div>

            @if (auth()->user()->isAdministrador())
                <details class="card d-print-none">
                    <summary class="card-body py-2 fw-semibold small" style="cursor:pointer;">Información del convenio de mayorista</summary>
                    <div class="card-body pt-0">
                        <p class="small text-muted mb-2">Cada factura se liquida con los parámetros vigentes en su fecha.</p>
                        <table class="table table-sm mb-2">
                            <thead>
                                <tr>
                                    <th>Vigente desde</th>
                                    <th class="text-end">% mayorista</th>
                                    <th class="text-end">Margen por galón</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($parametros as $parametro)
                                    <tr>
                                        <td>{{ $parametro->vigente_desde->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ $porcentaje((float) $parametro->porcentaje) }}</td>
                                        <td class="text-end">{{ number_format((float) $parametro->margen, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <form method="POST" action="{{ route('informes.margen-mayorista.parametros') }}"
                            class="d-flex flex-wrap gap-2 align-items-end">
                            @csrf
                            <input type="hidden" name="desde" value="{{ $desde }}">
                            <input type="hidden" name="hasta" value="{{ $hasta }}">
                            <div>
                                <label for="campo-vigente_desde" class="form-label small mb-1">Vigente desde</label>
                                <input id="campo-vigente_desde" type="date" name="vigente_desde" class="form-control form-control-sm" required>
                            </div>
                            <div>
                                <label for="campo-porcentaje" class="form-label small mb-1">% mayorista</label>
                                <input id="campo-porcentaje" type="text" name="porcentaje" class="form-control form-control-sm text-end" style="width:6rem;"
                                    inputmode="decimal" placeholder="0,3" required>
                            </div>
                            <div>
                                <label for="campo-margen" class="form-label small mb-1">Margen (coma decimal)</label>
                                <input id="campo-margen" type="text" name="margen" class="form-control form-control-sm text-end" style="width:8rem;"
                                    inputmode="decimal" placeholder="585,71" required>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm">Guardar</button>
                        </form>
                    </div>
                </details>
            @endif
        </div>
    </div>
@endsection
