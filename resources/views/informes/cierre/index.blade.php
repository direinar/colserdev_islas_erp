@extends('layouts.app')

@section('title', 'Informe de Cierre')

@php
    use Illuminate\Support\Carbon;

    $pesos = fn (float $valor): string => ($valor < 0 ? '-' : '').'$ '.number_format(abs($valor), 0, ',', '.');
    $galones = fn (?float $valor): string => $valor === null ? '—' : number_format($valor, 0, ',', '.');
    $rango = ['desde' => $desde, 'hasta' => $hasta];

    // Renglones del estado de resultados: [referencia, concepto, clave, estilo, origen].
    $secciones = [
        'COMBUSTIBLES' => [
            ['1', 'Ventas de ACPM', 'ventas_acpm', 'linea', 'Inventario ACPM: VR. VENTAS del periodo'],
            ['2', 'Ventas de gasolina', 'ventas_gasolina', 'linea', 'Inventario gasolina: VR. VENTAS del periodo'],
            ['3', 'Total ventas del periodo', 'total_ventas', 'subtotal', '1 + 2'],
            ['4', 'Costo del combustible vendido', 'costo_combustible', 'resta', 'Inventarios ACPM + gasolina: salidas al costo promedio'],
            ['5', 'Subtotal', 'subtotal', 'subtotal', '3 − 4'],
            ['6', 'Menos: descuentos', 'descuentos', 'resta', 'Descuentos en ventas: puntos redimidos + descuentos de las planillas'],
            ['7', 'Utilidad bruta en combustibles', 'utilidad_combustibles', 'resultado', '5 − 6'],
            ['8', 'Margen mayorista', 'margen_mayorista', 'linea', 'Informe Margen Mayorista del mismo periodo'],
            ['9', 'Ingresos totales por combustibles', 'ingresos_combustibles', 'resultado', '7 + 8'],
        ],
        'CANASTILLA' => [
            ['10', 'Venta de canastilla', 'venta_canastilla', 'linea', 'Inventario Canastilla – Control: VR. VENTA (sin IVA)'],
            ['', 'Costo de ventas de canastilla', 'costo_canastilla', 'resta', 'Inventario Canastilla – Control: salidas al costo promedio'],
            ['11', 'Utilidad en canastilla', 'utilidad_canastilla', 'resultado', '10 − costo'],
        ],
    ];

    $saldoContrato = $estadisticas['saldo_contrato'];
    $acumulado = $estadisticas['galonaje_acumulado'];
    $avanceContrato = $acumulado !== null && $saldoContrato !== null && ($acumulado + $saldoContrato) > 0
        ? max(0, min(100, $acumulado / ($acumulado + $saldoContrato) * 100))
        : null;
@endphp

@section('content')
    <style>
        .ic-encabezado {
            background: linear-gradient(135deg, #1f4e78 0%, #2e75b6 100%);
            color: #fff;
            border-radius: .6rem;
        }

        .ic-encabezado .form-label {
            color: #dbe9f7;
        }

        .ic-kpi {
            border: 0;
            border-radius: .6rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
            height: 100%;
        }

        .ic-kpi .ic-kpi-titulo {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04rem;
            color: #6b7280;
            text-transform: uppercase;
        }

        .ic-kpi .ic-kpi-valor {
            font-size: 1.35rem;
            font-weight: 800;
        }

        .ic-kpi.ic-destacado {
            background: #e2efda;
        }

        .ic-estado {
            width: 100%;
            border-collapse: collapse;
            font-size: .9rem;
        }

        .ic-estado td {
            padding: .45rem .75rem;
            border-bottom: 1px solid #edf0f3;
            vertical-align: middle;
        }

        .ic-estado .ic-seccion td {
            background: #1f4e78;
            color: #fff;
            font-weight: 700;
            letter-spacing: .08rem;
            font-size: .8rem;
        }

        .ic-estado .ic-ref {
            width: 2.6rem;
            text-align: center;
        }

        .ic-estado .ic-ref span {
            display: inline-block;
            min-width: 1.7rem;
            padding: .05rem .35rem;
            border-radius: 1rem;
            background: #ddebf7;
            color: #1f4e78;
            font-size: .72rem;
            font-weight: 700;
        }

        .ic-estado .ic-valor {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            width: 11rem;
        }

        .ic-estado .ic-origen {
            color: #8a94a3;
            font-size: .74rem;
        }

        .ic-estado .ic-resta .ic-concepto {
            padding-left: 1.6rem;
        }

        .ic-estado .ic-resta .ic-valor {
            color: #b42318;
        }

        .ic-estado .ic-subtotal td {
            background: #f6f8fa;
            font-weight: 700;
        }

        .ic-estado .ic-resultado td {
            background: #fff2cc;
            font-weight: 800;
        }

        .ic-estado .ic-total td {
            background: #2e7d32;
            color: #fff;
            font-weight: 800;
            font-size: 1.05rem;
        }

        .ic-negativo {
            color: #b42318 !important;
        }

        @page {
            size: portrait;
            margin: 10mm;
        }

        @media print {
            nav,
            .navbar {
                display: none !important;
            }

            body > .container-fluid {
                padding: 0 !important;
            }

            .ic-encabezado {
                background: none !important;
                color: #000 !important;
            }

            .card {
                box-shadow: none !important;
            }

            .ic-estado,
            .ic-kpi,
            .ic-encabezado {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <div class="ic-encabezado p-3 mb-3 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-end gap-2">
        <div>
            <div class="fs-3 fw-bold" style="letter-spacing:.15rem;">INFORME DE CIERRE</div>
            <div class="small opacity-75">
                Periodo del {{ Carbon::parse($desde)->format('d/m/Y') }} al {{ Carbon::parse($hasta)->format('d/m/Y') }}
            </div>
        </div>

        <form method="GET" action="{{ route('informes.cierre.index') }}" class="d-flex flex-wrap gap-2 align-items-end d-print-none">
            <div>
                <label class="form-label small fw-semibold mb-1" for="desde">DESDE</label>
                <input type="date" name="desde" id="desde" class="form-control form-control-sm" value="{{ $desde }}"
                    onchange="this.form.submit()">
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1" for="hasta">HASTA</label>
                <input type="date" name="hasta" id="hasta" class="form-control form-control-sm" value="{{ $hasta }}"
                    onchange="this.form.submit()">
            </div>
            <button type="button" class="btn btn-light btn-sm" onclick="window.print()">
                <i class="bi bi-printer"></i> IMPRIMIR
            </button>
            <a href="{{ route('informes.cierre.exportar', $rango) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> EXCEL
            </a>
        </form>
    </div>

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

    {{-- Resumen --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['Total ventas combustibles', $valores['total_ventas'], false],
            ['Utilidad bruta combustibles', $valores['utilidad_combustibles'], false],
            ['Utilidad en canastilla', $valores['utilidad_canastilla'], false],
            ['Utilidad bruta total EDS', $valores['utilidad_total'], true],
        ] as [$titulo, $valor, $destacado])
            <div class="col-6 col-xl-3">
                <div class="card ic-kpi {{ $destacado ? 'ic-destacado' : '' }}">
                    <div class="card-body py-3">
                        <div class="ic-kpi-titulo">{{ $titulo }}</div>
                        <div class="ic-kpi-valor {{ $valor < 0 ? 'ic-negativo' : '' }}">{{ $pesos($valor) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- Estado de resultados --}}
        <div class="col-12 col-xl-8">
            <div class="card ic-kpi">
                <div class="table-responsive">
                    <table class="ic-estado">
                        <tbody>
                            @foreach ($secciones as $seccion => $renglones)
                                <tr class="ic-seccion">
                                    <td colspan="4">{{ $seccion }}</td>
                                </tr>
                                @foreach ($renglones as [$referencia, $concepto, $clave, $estilo, $origen])
                                    <tr class="ic-{{ $estilo }}">
                                        <td class="ic-ref">@if ($referencia)<span>{{ $referencia }}</span>@endif</td>
                                        <td class="ic-concepto">{{ $estilo === 'resta' ? '(−) ' : '' }}{{ $concepto }}</td>
                                        <td class="ic-origen">{{ $origen }}</td>
                                        <td class="ic-valor {{ $valores[$clave] < 0 && $estilo !== 'resta' ? 'ic-negativo' : '' }}">{{ $pesos($valores[$clave]) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                            <tr class="ic-total">
                                <td class="ic-ref"></td>
                                <td colspan="2">UTILIDAD BRUTA TOTAL EDS <span class="small fw-normal opacity-75">(9 + 11)</span></td>
                                <td class="ic-valor">{{ $pesos($valores['utilidad_total']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Estadísticas del periodo --}}
        <div class="col-12 col-xl-4 d-flex flex-column gap-3">
            <div class="card ic-kpi">
                <div class="card-body">
                    <div class="ic-kpi-titulo mb-3">Estadísticas del periodo</div>

                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <span><span class="badge text-bg-light me-1">12</span> Margen de intermediación</span>
                        <span class="fw-bold fs-5">
                            {{ $estadisticas['margen_intermediacion'] === null ? '—' : number_format($estadisticas['margen_intermediacion'] * 100, 2, ',', '.').' %' }}
                        </span>
                    </div>
                    <div class="small text-muted mb-3">Utilidad bruta en combustibles / total ventas (7 / 3)</div>

                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span><span class="badge text-bg-light me-1">13</span> Galonaje del periodo</span>
                        <span class="fw-bold">{{ $galones($estadisticas['galonaje_periodo']) }} gal</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span><span class="badge text-bg-light me-1">14</span> Galonaje acumulado</span>
                        <span class="fw-bold">{{ $galones($acumulado) }} gal</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <span><span class="badge text-bg-light me-1">15</span> Saldo del contrato</span>
                        <span class="fw-bold {{ ($saldoContrato ?? 0) < 0 ? 'ic-negativo' : '' }}">{{ $galones($saldoContrato) }} gal</span>
                    </div>

                    @if ($avanceContrato !== null)
                        <div class="progress" role="progressbar" aria-label="Avance del contrato" aria-valuenow="{{ round($avanceContrato) }}"
                            aria-valuemin="0" aria-valuemax="100" style="height:.6rem;">
                            <div class="progress-bar bg-success" style="width: {{ $avanceContrato }}%"></div>
                        </div>
                        <div class="small text-muted mt-1">Contrato ejecutado: {{ number_format($avanceContrato, 1, ',', '.') }} %
                            (desde el corte del {{ $contrato->fecha_corte->format('d/m/Y') }})</div>
                    @else
                        <div class="alert alert-warning small mb-0 mt-2 d-print-none">
                            Falta registrar los saldos iniciales del contrato de galonaje para calcular los renglones 14 y 15.
                        </div>
                    @endif
                </div>
            </div>

            <details class="card ic-kpi d-print-none">
                <summary class="card-body py-2 fw-semibold small" style="cursor:pointer;">Saldos iniciales del contrato de galonaje</summary>
                <form method="POST" action="{{ route('informes.cierre.contrato') }}" class="card-body pt-0">
                    @csrf
                    <input type="hidden" name="desde" value="{{ $desde }}">
                    <input type="hidden" name="hasta" value="{{ $hasta }}">
                    <p class="small text-muted mb-2">Desde la fecha de corte, los galones vendidos (gasolina + ACPM) se suman al
                        acumulado y se restan del saldo del contrato. Escriba los galones sin puntos de miles.</p>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Fecha de corte</label>
                        <input type="date" name="fecha_corte" class="form-control form-control-sm" required
                            value="{{ $contrato?->fecha_corte->format('Y-m-d') }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Galonaje acumulado a esa fecha</label>
                        <input type="text" name="galonaje_acumulado_inicial" class="form-control form-control-sm text-end" required
                            inputmode="decimal" value="{{ $contrato ? rtrim(rtrim(number_format((float) $contrato->galonaje_acumulado_inicial, 3, ',', ''), '0'), ',') : '' }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Saldo del contrato a esa fecha</label>
                        <input type="text" name="saldo_contrato_inicial" class="form-control form-control-sm text-end" required
                            inputmode="decimal" value="{{ $contrato ? rtrim(rtrim(number_format((float) $contrato->saldo_contrato_inicial, 3, ',', ''), '0'), ',') : '' }}">
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Guardar</button>
                </form>
            </details>
        </div>
    </div>
@endsection
