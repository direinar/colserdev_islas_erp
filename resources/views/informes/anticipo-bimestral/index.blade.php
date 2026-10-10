@extends('layouts.app')

@section('title', 'Informe Anticipo Bimestral')

@php
    use App\Services\AnticipoBimestralService;
    use Illuminate\Support\Carbon;

    $ordinales = [1 => 'PRIMER', 2 => 'SEGUNDO', 3 => 'TERCER', 4 => 'CUARTO', 5 => 'QUINTO', 6 => 'SEXTO'];
    $nombresMeses = AnticipoBimestralService::MESES;
    $pesos = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $galones = fn (float $valor): string => number_format($valor, fmod(round($valor, 3), 1.0) == 0.0 ? 0 : 2, ',', '.');
    $margen = fn (?float $valor): string => $valor === null ? 'Varios' : number_format($valor, 2, ',', '.');
    $esAdministrador = auth()->user()->isAdministrador();
    $periodo = ['anio' => $anio, 'bimestre' => $bimestre];
@endphp

@section('content')
    <style>
        .ab-tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
            color: #000;
        }

        .ab-tabla th,
        .ab-tabla td {
            border: 1px solid #8c8c8c;
            padding: .22rem .45rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        .ab-tabla thead th {
            background: #ddebf7;
            text-align: center;
            font-weight: 700;
            font-size: .74rem;
        }

        .ab-tabla .ab-titulo-tabla {
            background: #1f4e78;
            color: #fff;
            font-size: .9rem;
            letter-spacing: .03rem;
        }

        .ab-tabla td.num {
            text-align: right;
        }

        .ab-tabla .ab-subtotal td {
            background: #f2f2f2;
            font-weight: 700;
        }

        .ab-tabla .ab-total td {
            background: #fff2cc;
            font-weight: 800;
        }

        .ab-tabla .ab-base td {
            background: #e2efda;
            font-weight: 800;
        }

        .ab-tabla .ab-input {
            width: 100%;
            min-width: 8rem;
            border: 0;
            background: #fffbe6;
            text-align: right;
            font-weight: 600;
            padding: .1rem .3rem;
        }

        .ab-tabla .ab-input:focus {
            outline: 2px solid #1f6feb;
            outline-offset: -2px;
            background: #fff;
        }

        @page {
            size: landscape;
            margin: 7mm 6mm 8mm;
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

            .ab-tabla {
                font-size: 9px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <x-page-header title="Informe anticipo bimestral" icon="calendar2-range"
        :subtitle="'Desde '.Carbon::parse($desde)->format('d/m/Y').' — hasta '.Carbon::parse($hasta)->format('d/m/Y')">
            <form method="GET" action="{{ route('informes.anticipo-bimestral.index') }}"
                class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label" for="anio">AÑO</label>
                    <input type="number" name="anio" id="anio" class="form-control form-control-sm" style="width:6rem;"
                        min="2000" max="2100" value="{{ $anio }}" onchange="this.form.submit()">
                </div>
                <div>
                    <label class="form-label" for="bimestre">BIMESTRE</label>
                    <select name="bimestre" id="bimestre" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach (range(1, 6) as $numero)
                            <option value="{{ $numero }}" @selected($numero === $bimestre)>
                                {{ $numero }} - {{ $nombresMeses[$numero * 2 - 1] }} Y {{ $nombresMeses[$numero * 2] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i> IMPRIMIR
            </button>
            <a href="{{ route('informes.anticipo-bimestral.exportar', $periodo) }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> EXCEL
            </a>
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
        {{-- Respaldo: de dónde salen los galones (módulo de compras). --}}
        <div class="col-12 col-xxl-7">
            <div class="card">
                <div class="table-responsive">
                    <table class="ab-tabla">
                        <thead>
                            <tr>
                                <th colspan="8" class="ab-titulo-tabla">COMPRA DE COMBUSTIBLE EN BIMESTRE {{ $bimestre }}</th>
                            </tr>
                            <tr>
                                <th rowspan="2">FECHA</th>
                                <th>No.</th>
                                <th colspan="2">GASOLINA</th>
                                <th colspan="2">ACPM</th>
                                <th rowspan="2">VR TOTAL FRA</th>
                                <th rowspan="2">TOTAL<br>GALONES</th>
                            </tr>
                            <tr>
                                <th>FACTURA</th>
                                <th>UNIDADES</th>
                                <th>DISTRIB. COSTO</th>
                                <th>UNIDADES</th>
                                <th>DISTRIB. COSTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($compras as $mes)
                                @foreach ($mes['compras'] as $compra)
                                    <tr>
                                        <td class="text-center">{{ $compra->fecha->format('d/m/Y') }}</td>
                                        <td>{{ $compra->factura }}</td>
                                        <td class="num">{{ $galones((float) $compra->gasolina) }}</td>
                                        <td class="num">{{ $pesos((float) $compra->distribucion_gasolina) }}</td>
                                        <td class="num">{{ $galones((float) $compra->acpm) }}</td>
                                        <td class="num">{{ $pesos((float) $compra->distribucion_acpm) }}</td>
                                        <td class="num">{{ $pesos((float) $compra->vr_total_fra) }}</td>
                                        <td class="num">{{ $galones((float) $compra->gasolina + (float) $compra->acpm) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="ab-subtotal">
                                    <td colspan="2">TOTAL {{ $mes['nombre'] }}</td>
                                    <td class="num">{{ $galones($mes['gasolina']) }}</td>
                                    <td></td>
                                    <td class="num">{{ $galones($mes['acpm']) }}</td>
                                    <td></td>
                                    <td class="num">{{ $pesos($mes['valor']) }}</td>
                                    <td class="num">{{ $galones($mes['galones']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">No hay compras de combustible en el bimestre.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xxl-5 d-flex flex-column gap-3">
            <div class="card">
                <table class="ab-tabla">
                    <thead>
                        <tr>
                            <th colspan="4" class="ab-titulo-tabla">LIQUIDACIÓN {{ $ordinales[$bimestre] }} BIMESTRE</th>
                        </tr>
                        <tr>
                            <th>MES</th>
                            <th>GALONES</th>
                            <th>VR. INTERMEDIACIÓN</th>
                            <th>PESOS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($meses as $fila)
                            <tr>
                                <td>{{ $fila['nombre'] }}</td>
                                <td class="num">{{ $galones($fila['galones']) }}</td>
                                <td class="num">{{ $margen($fila['margen']) }}</td>
                                <td class="num">{{ $pesos($fila['pesos']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="ab-total">
                            <td>TOTAL</td>
                            <td class="num">{{ $galones($totalBimestre['galones']) }}</td>
                            <td></td>
                            <td class="num">{{ $pesos($totalBimestre['pesos']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <table class="ab-tabla">
                    <thead>
                        <tr>
                            <th colspan="4" class="ab-titulo-tabla">ACUMULADO: ENERO A {{ $nombresMeses[$bimestre * 2] }} {{ $anio }}</th>
                        </tr>
                        <tr>
                            <th>PERIODO</th>
                            <th>GALONES</th>
                            <th>VR. INTERMEDIACIÓN</th>
                            <th>PESOS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($acumulado['filas'] as $fila)
                            <tr>
                                <td>{{ $fila['nombre'] }}</td>
                                <td class="num">{{ $galones($fila['galones']) }}</td>
                                <td class="num">{{ $margen($fila['margen']) }}</td>
                                <td class="num">{{ $pesos($fila['pesos']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="ab-total">
                            <td>TOTAL</td>
                            <td class="num">{{ $galones($acumulado['total']['galones']) }}</td>
                            <td></td>
                            <td class="num">{{ $pesos($acumulado['total']['pesos']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <form method="POST" action="{{ route('informes.anticipo-bimestral.ajustes') }}">
                    @csrf
                    <input type="hidden" name="anio" value="{{ $anio }}">
                    <input type="hidden" name="bimestre" value="{{ $bimestre }}">
                    <table class="ab-tabla">
                        <thead>
                            <tr>
                                <th colspan="2" class="ab-titulo-tabla">LIQUIDACIÓN DEL ANTICIPO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>VENTAS DE COMBUSTIBLE DE ESTE BIMESTRE</td>
                                <td class="num">{{ $pesos($liquidacion['ventas_combustible']) }}</td>
                            </tr>
                            <tr>
                                <td>MÁS VENTAS DE LUBRICANTES <span class="small text-muted">(control canastilla, sin IVA)</span></td>
                                <td class="num">{{ $pesos($liquidacion['ventas_lubricantes']) }}</td>
                            </tr>
                            <tr class="ab-subtotal">
                                <td>SUBTOTAL</td>
                                <td class="num">{{ $pesos($liquidacion['subtotal']) }}</td>
                            </tr>
                            <tr>
                                <td>OTROS INGRESOS</td>
                                <td class="num">
                                    @if ($esAdministrador)
                                        <input type="text" name="otros_ingresos" class="ab-input" inputmode="numeric"
                                            value="{{ $liquidacion['otros_ingresos'] ? $pesos($liquidacion['otros_ingresos']) : '' }}"
                                            aria-label="Otros ingresos">
                                    @else
                                        {{ $pesos($liquidacion['otros_ingresos']) }}
                                    @endif
                                </td>
                            </tr>
                            <tr class="ab-base">
                                <td>BASE DE LIQUIDACIÓN RÉGIMEN SIMPLE</td>
                                <td class="num">{{ $pesos($liquidacion['base_simple']) }}</td>
                            </tr>
                            <tr>
                                <td>MENOS DESCUENTOS EN VENTAS</td>
                                <td class="num">
                                    @if ($esAdministrador)
                                        <input type="text" name="descuentos_ventas" class="ab-input" inputmode="numeric"
                                            value="{{ $liquidacion['descuentos_ventas'] ? $pesos($liquidacion['descuentos_ventas']) : '' }}"
                                            aria-label="Descuentos en ventas">
                                    @else
                                        {{ $pesos($liquidacion['descuentos_ventas']) }}
                                    @endif
                                </td>
                            </tr>
                            <tr class="ab-base">
                                <td>BASE DE LIQUIDACIÓN ICA</td>
                                <td class="num">{{ $pesos($liquidacion['base_ica']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    @if ($esAdministrador)
                        <div class="p-2 text-end d-print-none">
                            <button type="submit" class="btn btn-primary btn-sm">Guardar otros ingresos y descuentos</button>
                        </div>
                    @endif
                </form>
            </div>

            @if ($esAdministrador)
                <details class="card d-print-none">
                    <summary class="card-body py-2 fw-semibold small" style="cursor:pointer;">Márgenes de intermediación</summary>
                    <div class="card-body pt-0">
                        <p class="small text-muted mb-2">Cada mes se liquida con el margen vigente el primer día del mes.</p>
                        <table class="table table-sm mb-2">
                            <thead>
                                <tr>
                                    <th>Vigente desde</th>
                                    <th class="text-end">Valor por galón</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($margenes as $registro)
                                    <tr>
                                        <td>{{ $registro->vigente_desde->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ number_format((float) $registro->valor, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <form method="POST" action="{{ route('informes.anticipo-bimestral.margenes') }}"
                            class="d-flex flex-wrap gap-2 align-items-end">
                            @csrf
                            <input type="hidden" name="anio" value="{{ $anio }}">
                            <input type="hidden" name="bimestre" value="{{ $bimestre }}">
                            <div>
                                <label for="campo-vigente_desde" class="form-label small mb-1">Vigente desde</label>
                                <input id="campo-vigente_desde" type="date" name="vigente_desde" class="form-control form-control-sm" required>
                            </div>
                            <div>
                                <label for="campo-valor" class="form-label small mb-1">Valor (coma para decimales)</label>
                                <input id="campo-valor" type="text" name="valor" class="form-control form-control-sm text-end" inputmode="decimal"
                                    placeholder="1155,10" required>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm">Guardar margen</button>
                        </form>
                    </div>
                </details>
            @endif
        </div>
    </div>

    <div class="small text-muted mt-2">Galones comprados de gasolina y ACPM del módulo de compras por el margen de
        intermediación de cada mes. Ventas de lubricantes: Inventario Canastilla – Control (valor sin IVA).</div>
@endsection
