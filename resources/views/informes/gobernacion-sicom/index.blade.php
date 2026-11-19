@extends('layouts.app')

@section('title', 'Informe Gobernación y SICOM')

@php
    use Illuminate\Support\Carbon;

    $lectura = fn (?float $valor): string => $valor === null ? '' : number_format($valor, 3, ',', '.');
    $galones = fn (?float $valor): string => $valor === null ? '' : number_format($valor, 2, ',', '.');
    $galonesOVacio = fn (float $valor): string => $valor ? $galones($valor) : '';
    $nombreMes = mb_strtoupper(Carbon::parse($desde)->translatedFormat('F Y'));

    $conceptos = [
        'saldo_inicio' => ['SALDO AL INICIO DEL MES', 'Galones al iniciar el mes, tomados del inventario de cada producto.'],
        'compras' => ['Más COMPRAS del mes', 'Galones del módulo de compras (igual al punto 2 de este informe).'],
        'ventas' => ['Menos VENTAS del mes', 'Galones vendidos en el mes según el inventario de cada producto.'],
        'ajustes' => ['Ajustes del mes', 'Galones ajustados (positivos o negativos) según los ajustes de inventario del mes.'],
        'saldo_cierre' => ['SALDO AL CIERRE DEL MES', 'Saldo al inicio + compras − ventas ± ajustes.'],
        'saldo_inventario' => ['SALDO GALONES AL CIERRE DEL MES', 'Saldo final del inventario de cada producto.'],
        'diferencia' => ['DIFERENCIA', 'Saldo al cierre del mes menos saldo galones al cierre del mes: debe ser cero.'],
    ];
@endphp

@section('content')
    <style>
        .gs-tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: .84rem;
            color: #000;
        }

        .gs-tabla th,
        .gs-tabla td {
            border: 1px solid #8c8c8c;
            padding: .25rem .5rem;
            vertical-align: middle;
        }

        .gs-tabla thead th {
            background: #ddebf7;
            text-align: center;
            font-weight: 700;
            font-size: .76rem;
        }

        .gs-tabla .gs-titulo-tabla {
            background: #1f4e78;
            color: #fff;
            font-size: .9rem;
            text-align: left;
        }

        .gs-tabla td.num {
            text-align: right;
            white-space: nowrap;
        }

        .gs-tabla tfoot td,
        .gs-tabla tr.gs-total td {
            background: #fff2cc;
            font-weight: 800;
        }

        .gs-tabla .gs-nota {
            font-size: .74rem;
            color: #555;
        }

        .gs-tabla .gs-negativo {
            color: #c00000;
        }

        .gs-notas {
            font-size: .76rem;
            color: #555;
            margin: .35rem 0 0;
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

            .card,
            .card-body {
                border: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .gs-tabla {
                font-size: 9px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .gs-tabla a {
                color: inherit;
                text-decoration: none;
            }
        }
    </style>

    <x-page-header title="Informe Gobernación y SICOM" icon="bank"
        :subtitle="'MES: '.$nombreMes.'. Lecturas electrónicas, compras y resumen de galones para la declaración del SICOM.'">
        <form method="GET" action="{{ route('informes.gobernacion-sicom.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label" for="mes">MES</label>
                <input type="month" name="mes" id="mes" class="form-control form-control-sm" value="{{ $mes }}"
                    onchange="this.form.submit()">
            </div>
        </form>
        <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i> IMPRIMIR
        </button>
        <a href="{{ route('informes.gobernacion-sicom.exportar', ['mes' => $mes]) }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> EXCEL
        </a>
    </x-page-header>

    <div class="row g-3">
        {{-- 1 - LECTURAS ELECTRÓNICAS --}}
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-body">
                    @if ($lecturas['incompletas'])
                        <div class="alert alert-warning small d-print-none" id="lecturas-incompletas">
                            Sin lectura del último día del mes ({{ Carbon::parse($hasta)->format('d/m/Y') }}) en:
                            {{ implode(', ', $lecturas['incompletas']) }}. El informe toma la última lectura registrada del mes.
                            <a href="{{ route('lectura-electronica.index', ['fecha' => $hasta]) }}">Registrar la lectura del {{ Carbon::parse($hasta)->format('d/m/Y') }}</a>.
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="gs-tabla">
                            <thead>
                                <tr>
                                    <th colspan="6" class="gs-titulo-tabla">1 - LECTURAS ELECTRÓNICAS</th>
                                </tr>
                                <tr>
                                    <th rowspan="2">MANGUERAS</th>
                                    <th colspan="2">LECTURAS ELECTRÓNICAS</th>
                                    <th colspan="3">VENTAS DEL MES</th>
                                </tr>
                                <tr>
                                    <th>INICIAL DEL MES (1)</th>
                                    <th>FINAL DEL MES (2)</th>
                                    <th>PLUS (3)</th>
                                    <th>ACPM (4)</th>
                                    <th>TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lecturas['filas'] as $fila)
                                    <tr>
                                        <td>{{ $fila['manguera'] }}</td>
                                        <td class="num">{{ $lectura($fila['inicial']) }}</td>
                                        <td class="num">
                                            {{ $lectura($fila['final']) }}
                                            @if ($fila['fecha_final'] && $fila['fecha_final'] !== $hasta)
                                                <div class="gs-nota">al {{ Carbon::parse($fila['fecha_final'])->format('d/m') }}</div>
                                            @endif
                                        </td>
                                        <td class="num">{{ $galonesOVacio($fila['plus']) }}</td>
                                        <td class="num">{{ $galonesOVacio($fila['acpm']) }}</td>
                                        <td class="num">{{ $galones($fila['total']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end">TOTALES</td>
                                    <td class="num">{{ $galones($lecturas['totales']['plus']) }}</td>
                                    <td class="num">{{ $galones($lecturas['totales']['acpm']) }}</td>
                                    <td class="num">{{ $galones($lecturas['totales']['total']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="gs-notas">(1) Lectura del último día del mes anterior (la final del informe anterior).
                        (2) Lectura del último día del mes del informe (será la inicial del mes siguiente).
                        (3) y (4) Diferencia (2) − (1) de PLUS y de ACPM.</p>
                </div>
            </div>
        </div>

        {{-- 2 - COMPRAS DEL MES --}}
        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="gs-tabla">
                            <thead>
                                <tr>
                                    <th colspan="5" class="gs-titulo-tabla">2 - COMPRAS DEL MES</th>
                                </tr>
                                <tr>
                                    <th>FECHA</th>
                                    <th>No. FACTURA</th>
                                    <th>GALONES PLUS</th>
                                    <th>GALONES ACPM</th>
                                    <th>TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($compras['filas'] as $compra)
                                    <tr>
                                        <td>{{ Carbon::parse($compra['fecha'])->format('d/m/Y') }}</td>
                                        <td>{{ $compra['factura'] }}</td>
                                        <td class="num">{{ $galonesOVacio($compra['plus']) }}</td>
                                        <td class="num">{{ $galonesOVacio($compra['acpm']) }}</td>
                                        <td class="num">{{ $galones($compra['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Sin compras de combustible en el mes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-end">TOTALES</td>
                                    <td class="num">{{ $galones($compras['totales']['plus']) }}</td>
                                    <td class="num">{{ $galones($compras['totales']['acpm']) }}</td>
                                    <td class="num">{{ $galones($compras['totales']['total']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3 - RESUMEN PARA SICOM --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="gs-tabla" id="resumen-sicom">
                            <thead>
                                <tr>
                                    <th colspan="5" class="gs-titulo-tabla">3 - RESUMEN DEL MES PARA DECLARACIÓN DE SICOM — {{ $nombreMes }}</th>
                                </tr>
                                <tr>
                                    <th>CONCEPTO</th>
                                    <th>CORRIENTE</th>
                                    <th>ACPM</th>
                                    <th>TOTAL</th>
                                    <th>ORIGEN DEL DATO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($conceptos as $clave => [$concepto, $origen])
                                    <tr @class(['gs-total' => in_array($clave, ['saldo_cierre', 'diferencia'], true)])>
                                        <td>{{ $concepto }}</td>
                                        @foreach (['corriente', 'acpm', 'total'] as $producto)
                                            <td @class(['num', 'gs-negativo' => $clave === 'diferencia' && round($sicom[$producto][$clave], 2) != 0.0])>
                                                {{ $galones($sicom[$producto][$clave]) }}</td>
                                        @endforeach
                                        <td class="gs-nota">{{ $origen }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="gs-notas d-print-none">Detalle en los inventarios de
                        <a href="{{ route('inventarios-gasolina.create') }}">gasolina</a> y
                        <a href="{{ route('inventarios-acpm.create') }}">ACPM</a>, y en
                        <a href="{{ route('inventarios-ajustes.index') }}">ajustes de inventario</a>.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
