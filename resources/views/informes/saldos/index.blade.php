@extends('layouts.app')

@section('title', 'Informes de Saldos')

@php
    use Illuminate\Support\Carbon;

    $pesos = fn (float $valor): string => ($valor < 0 ? '-' : '').'$ '.number_format(abs($valor), 0, ',', '.');
    $unidades = fn (float $valor): string => number_format($valor, fmod(round($valor, 2), 1.0) == 0.0 ? 0 : 2, ',', '.');
    $titulos = [
        'cartera' => ['INFORME DE SALDOS DE CARTERA', 'Saldo de cada cliente de cartera a la fecha de corte', 'bi-people'],
        'canastilla' => ['INFORME DE SALDOS DE CANASTILLA', 'Existencias de cada referencia, tomadas de su inventario', 'bi-box-seam'],
        'precios' => ['INFORME DE PRECIOS DE VENTA DE CANASTILLA', 'Precios vigentes de las referencias activas de la tabla de precios', 'bi-tags'],
    ];
    $pestanas = ['cartera' => 'Saldos de cartera', 'canastilla' => 'Saldos de canastilla', 'precios' => 'Precios de canastilla'];
    $filtros = ['seccion' => $seccion, 'corte' => $corte] + ($soloConSaldo ? [] : ['todos' => 1]);
@endphp

@section('content')
    <style>

        .is-pestanas .nav-link {
            color: #1f4e78;
            font-weight: 600;
        }

        .is-pestanas .nav-link.active {
            background: #1f4e78;
            color: #fff;
        }

        .is-kpi {
            border: 0;
            border-radius: .6rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
            height: 100%;
        }

        .is-kpi-titulo {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04rem;
            color: #6b7280;
            text-transform: uppercase;
        }

        .is-kpi-valor {
            font-size: 1.3rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .is-tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: .9rem;
        }

        .is-tabla th {
            background: #1f4e78;
            color: #fff;
            font-size: .76rem;
            letter-spacing: .04rem;
            padding: .5rem .75rem;
            text-align: left;
        }

        .is-tabla td {
            padding: .45rem .75rem;
            border-bottom: 1px solid #edf0f3;
        }

        .is-tabla tbody tr:hover {
            background: #f6f9fc;
        }

        .is-tabla .num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .is-tabla tfoot td {
            background: #fff2cc;
            font-weight: 800;
        }

        .is-negativo {
            color: #b42318 !important;
        }

        .is-cero {
            color: #9aa3ae;
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

            .is-kpi {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }

            .is-tabla,
            .is-tabla th,
            .is-tabla tfoot td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <x-page-header :title="$titulos[$seccion][0]" :icon="str_replace('bi-', '', $titulos[$seccion][2])"
        :subtitle="$titulos[$seccion][1].($seccion === 'precios' ? '' : ' — fecha de corte '.Carbon::parse($corte)->format('d/m/Y'))">
        <form method="GET" action="{{ route('informes.saldos.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
            <input type="hidden" name="seccion" value="{{ $seccion }}">
            @if ($seccion !== 'precios')
                <div>
                    <label class="form-label" for="corte">FECHA DE CORTE</label>
                    <input type="date" name="corte" id="corte" class="form-control form-control-sm" value="{{ $corte }}"
                        onchange="this.form.submit()">
                </div>
            @endif
            @if ($seccion === 'cartera')
                <div class="form-check form-switch mb-1 ms-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="todos" name="todos" value="1"
                        @checked(! $soloConSaldo) onchange="this.form.submit()">
                    <label class="form-check-label" for="todos">Incluir clientes en cero</label>
                </div>
            @endif
        </form>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i> IMPRIMIR
        </button>
        <a href="{{ route('informes.saldos.exportar', $filtros) }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> EXCEL
        </a>
    </x-page-header>

    <ul class="nav nav-pills is-pestanas mb-3 d-print-none">
        @foreach ($pestanas as $clave => $nombre)
            <li class="nav-item">
                <a class="nav-link {{ $clave === $seccion ? 'active' : '' }}"
                    href="{{ route('informes.saldos.index', ['seccion' => $clave, 'corte' => $corte]) }}">{{ $nombre }}</a>
            </li>
        @endforeach
    </ul>

    @if ($seccion === 'cartera')
        <div class="row g-3 mb-3">
            @foreach ([
                ['Clientes con saldo', (string) $datos['con_saldo'], false],
                ['Total por cobrar', $pesos($datos['por_cobrar']), false],
                ['Anticipos de clientes', $pesos($datos['anticipos']), $datos['anticipos'] < 0],
                ['Saldo neto de cartera', $pesos($datos['neto']), $datos['neto'] < 0],
            ] as [$titulo, $valor, $negativo])
                <div class="col-6 col-xl-3">
                    <div class="card is-kpi">
                        <div class="card-body py-3">
                            <div class="is-kpi-titulo">{{ $titulo }}</div>
                            <div class="is-kpi-valor {{ $negativo ? 'is-negativo' : '' }}">{{ $valor }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card is-kpi">
            <div class="table-responsive">
                <table class="is-tabla">
                    <thead>
                        <tr>
                            <th>CLIENTE</th>
                            <th>NIT / DOCUMENTO</th>
                            <th class="num">SALDO</th>
                            <th class="d-print-none"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($datos['clientes'] as $cliente)
                            <tr>
                                <td>{{ $cliente['cliente'] }}</td>
                                <td>{{ $cliente['documento'] }}</td>
                                <td class="num fw-semibold {{ $cliente['saldo'] < 0 ? 'is-negativo' : ($cliente['saldo'] == 0 ? 'is-cero' : '') }}">
                                    {{ $pesos($cliente['saldo']) }}
                                </td>
                                <td class="text-end d-print-none">
                                    <a href="{{ route('cartera.index', ['customer_id' => $cliente['id'], 'fecha_final' => $corte]) }}"
                                        class="btn btn-sm btn-outline-primary py-0" title="Ver estado de cuenta">Estado de cuenta</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hay clientes con saldo a esta fecha.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end">SALDO NETO DE CARTERA</td>
                            <td class="num {{ $datos['neto'] < 0 ? 'is-negativo' : '' }}">{{ $pesos($datos['neto']) }}</td>
                            <td class="d-print-none"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="small text-muted mt-2">Saldo = cargos − abonos hasta la fecha de corte, incluido el saldo inicial. Los
            saldos en rojo son anticipos del cliente.</div>
    @elseif ($seccion === 'canastilla')
        <div class="row g-3 mb-3">
            @foreach ([
                ['Referencias', (string) count($datos['referencias'])],
                ['Unidades en existencia', $unidades($datos['unidades'])],
                ['Valor del inventario (al costo)', $pesos($datos['valor'])],
            ] as [$titulo, $valor])
                <div class="col-12 col-md-4">
                    <div class="card is-kpi">
                        <div class="card-body py-3">
                            <div class="is-kpi-titulo">{{ $titulo }}</div>
                            <div class="is-kpi-valor">{{ $valor }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card is-kpi">
            <div class="table-responsive">
                <table class="is-tabla">
                    <thead>
                        <tr>
                            <th>REFERENCIA</th>
                            <th class="num">SALDO (UNIDADES)</th>
                            <th class="num">COSTO PROMEDIO</th>
                            <th class="num">VALOR AL COSTO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($datos['referencias'] as $fila)
                            <tr>
                                <td>
                                    {{ $fila['referencia'] }}
                                    @unless ($fila['activa'])
                                        <span class="badge text-bg-secondary ms-1">inactiva</span>
                                    @endunless
                                </td>
                                <td class="num fw-semibold {{ $fila['unidades'] < 0 ? 'is-negativo' : ($fila['unidades'] == 0 ? 'is-cero' : '') }}">
                                    {{ $unidades($fila['unidades']) }}
                                </td>
                                <td class="num">{{ $pesos($fila['promedio']) }}</td>
                                <td class="num">{{ $pesos($fila['valor']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hay referencias de canastilla.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="text-end">TOTALES</td>
                            <td class="num">{{ $unidades($datos['unidades']) }}</td>
                            <td></td>
                            <td class="num">{{ $pesos($datos['valor']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="small text-muted mt-2">Existencias al cierre de la fecha de corte según el inventario de cada referencia
            (compras de lubricantes menos ventas de canastilla de las planillas).</div>
    @else
        <div class="card is-kpi">
            <div class="table-responsive">
                <table class="is-tabla">
                    <thead>
                        <tr>
                            <th>REFERENCIA</th>
                            <th class="num">PRECIO ANTES DE IVA</th>
                            <th class="num">IVA</th>
                            <th class="num">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($datos as $fila)
                            <tr>
                                <td>{{ $fila['referencia'] }}</td>
                                <td class="num">{{ $pesos($fila['precio']) }}</td>
                                <td class="num">{{ $pesos($fila['iva']) }}</td>
                                <td class="num fw-bold">{{ $pesos($fila['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No hay referencias activas en la tabla de precios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="small text-muted mt-2">Precios vigentes de la tabla de precios de canastilla (Inventario → Lubricantes);
            solo las referencias activas. Generado el {{ now()->format('d/m/Y') }}.</div>
    @endif
@endsection
