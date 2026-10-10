@extends('layouts.app')

@section('title', 'Informe Control Caja')

@php
    use App\Services\ControlCajaService;
    use Illuminate\Support\Carbon;

    $pesos = fn (float $valor): string => $valor ? number_format($valor, 0, ',', '.') : '';
    $pesosSiempre = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $rojo = fn (float $valor): string => $valor < 0 ? 'cc-negativo' : '';
    $casillas = range(0, ControlCajaService::CASILLAS_VARIOS - 1);
    $nombresCasilla = ['No. 1', 'No. 2', 'No. 3', 'No. 4'];
    $referenciasVarios = [['(14)', '(15)'], ['(14A)', '(15A)'], ['(14B)', '(15B)'], ['(14C)', '(15C)']];
    $rango = ['desde' => $desde, 'hasta' => $hasta];
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

        .cc-tabla .cc-ref th {
            font-weight: 600;
            color: #555;
            background: #fff;
        }

        .cc-tabla .cc-planilla { background: #f2f2f2; }
        .cc-tabla .cc-ventas { background: #fce4d6; }
        .cc-tabla .cc-pagos { background: #ddebf7; }
        .cc-tabla .cc-resultados { background: #e2efda; }
        .cc-tabla .cc-inicio { border-left: 3px solid #262626; }

        .cc-tabla td.num { text-align: right; }
        .cc-tabla .cc-negativo { color: #ff0000; }
        .cc-tabla .cc-total { font-weight: 700; }

        .cc-tabla tfoot td {
            font-weight: 800;
            background: #fff2cc;
        }

        @page {
            size: landscape;
            margin: 6mm;
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

            .cc-tabla {
                font-size: 7px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <x-page-header title="Informe control caja" icon="cash-stack"
        :subtitle="'Desde '.Carbon::parse($desde)->format('d/m/Y').' — hasta '.Carbon::parse($hasta)->format('d/m/Y').'. Toda la información viene de la planilla de turnos.'">
        @include('informes.partials.filtro-rango', [
            'ruta' => 'informes.control-caja.index',
            'rutaExportar' => 'informes.control-caja.exportar',
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

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="cc-tabla">
                <thead>
                    <tr>
                        <th colspan="2" class="cc-planilla cc-grupo">PLANILLA</th>
                        <th colspan="5" class="cc-ventas cc-grupo cc-inicio">VENTAS</th>
                        <th colspan="{{ 6 + 2 * count($casillas) + 4 }}" class="cc-pagos cc-grupo cc-inicio">FORMAS DE PAGO</th>
                        <th colspan="2" class="cc-resultados cc-grupo cc-inicio">RESULTADOS</th>
                    </tr>
                    <tr>
                        <th class="cc-planilla">FECHA</th>
                        <th class="cc-planilla">No.</th>
                        <th class="cc-ventas cc-inicio">GASOLINA</th>
                        <th class="cc-ventas">ACPM</th>
                        <th class="cc-ventas">CANASTILLA</th>
                        <th class="cc-ventas">IVA<br>GENERADO</th>
                        <th class="cc-ventas">TOTAL<br>VENTAS</th>
                        <th class="cc-pagos cc-inicio">CONSIGNACIONES</th>
                        <th class="cc-pagos">TC, QR Y<br>TRANSFERENCIAS</th>
                        <th class="cc-pagos">PUNTOS<br>REDIMIDOS</th>
                        <th class="cc-pagos">GASOLINA<br>EDS</th>
                        <th class="cc-pagos">DESCUENTOS</th>
                        <th class="cc-pagos">CARTERA<br>CRED. DTO</th>
                        @foreach ($casillas as $casilla)
                            <th class="cc-pagos">VARIOS<br>CONCEPTO {{ $nombresCasilla[$casilla] }}</th>
                            <th class="cc-pagos">VARIOS<br>VALOR {{ $nombresCasilla[$casilla] }}</th>
                        @endforeach
                        <th class="cc-pagos">FALTANTES</th>
                        <th class="cc-pagos">SOBRANTES</th>
                        <th class="cc-pagos">RECAUDOS Y<br>ANTICIPOS</th>
                        <th class="cc-pagos">TOTAL</th>
                        <th class="cc-resultados cc-inicio">RECIBIDO<br>S/PLANILLA</th>
                        <th class="cc-resultados">RECIBIDO<br>S/LECTURA</th>
                    </tr>
                    <tr class="cc-ref">
                        @foreach (['(1)', '(2)', '(3)', '(4)', '(5)', '(6)', '(7)', '(8)', '(9)', '(10)', '(11)', '(12)', '(13)'] as $referencia)
                            <th>{{ $referencia }}</th>
                        @endforeach
                        @foreach ($referenciasVarios as [$concepto, $valor])
                            <th>{{ $concepto }}</th>
                            <th>{{ $valor }}</th>
                        @endforeach
                        @foreach (['(16)', '(17)', '(18)', '(19)', '(20)', '(21)'] as $referencia)
                            <th>{{ $referencia }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $fila)
                        <tr>
                            <td class="text-center">{{ Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <a href="{{ route('turnos.create', ['turno_busqueda' => $fila['numero_turno']]) }}"
                                    class="text-decoration-none d-print-none" title="Abrir planilla del turno">{{ $fila['numero_turno'] }}</a>
                                <span class="d-none d-print-inline">{{ $fila['numero_turno'] }}</span>
                            </td>
                            <td class="num cc-inicio">{{ $pesos($fila['gasolina']) }}</td>
                            <td class="num">{{ $pesos($fila['acpm']) }}</td>
                            <td class="num">{{ $pesos($fila['canastilla']) }}</td>
                            <td class="num">{{ $pesos($fila['iva']) }}</td>
                            <td class="num cc-total">{{ $pesosSiempre($fila['total_ventas']) }}</td>
                            @foreach (['consignaciones', 'pagos_electronicos', 'puntos', 'gasolina_eds', 'descuentos', 'cartera'] as $indice => $columna)
                                <td class="num {{ $indice === 0 ? 'cc-inicio' : '' }}">{{ $pesos($fila[$columna]) }}</td>
                            @endforeach
                            @foreach ($fila['varios'] as $vario)
                                <td>{{ $vario['concepto'] }}</td>
                                <td class="num">{{ $pesos($vario['valor']) }}</td>
                            @endforeach
                            <td class="num">{{ $pesos($fila['faltantes']) }}</td>
                            <td class="num {{ $rojo($fila['sobrantes']) }}">{{ $pesos($fila['sobrantes']) }}</td>
                            <td class="num {{ $rojo($fila['recaudos']) }}">{{ $pesos($fila['recaudos']) }}</td>
                            <td class="num cc-total {{ $rojo($fila['total']) }}">{{ $pesosSiempre($fila['total']) }}</td>
                            <td class="num cc-inicio">{{ $pesosSiempre($fila['recibido_planilla']) }}</td>
                            <td class="num">{{ $pesosSiempre($fila['recibido_lectura']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 19 + 2 * count($casillas) }}" class="text-center text-muted py-3">No hay planillas de turno en el periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">TOTALES</td>
                        @foreach (['gasolina', 'acpm', 'canastilla', 'iva', 'total_ventas', 'consignaciones', 'pagos_electronicos', 'puntos', 'gasolina_eds', 'descuentos', 'cartera'] as $indice => $columna)
                            <td class="num {{ in_array($indice, [0, 5], true) ? 'cc-inicio' : '' }}">{{ $pesosSiempre($totales[$columna]) }}</td>
                        @endforeach
                        @foreach ($totales['varios'] as $valor)
                            <td></td>
                            <td class="num">{{ $pesosSiempre($valor) }}</td>
                        @endforeach
                        @foreach (['faltantes', 'sobrantes', 'recaudos', 'total'] as $columna)
                            <td class="num {{ $rojo($totales[$columna]) }}">{{ $pesosSiempre($totales[$columna]) }}</td>
                        @endforeach
                        <td class="num cc-inicio">{{ $pesosSiempre($totales['recibido_planilla']) }}</td>
                        <td class="num">{{ $pesosSiempre($totales['recibido_lectura']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="table-responsive">
                    <table class="cc-tabla">
                        <thead>
                            <tr>
                                <th colspan="4" class="cc-grupo" style="background:#ccccff;">COMPROBANTE CONTABLE DE VENTAS</th>
                            </tr>
                            <tr style="background:#ccccff;">
                                <th>CUENTA</th>
                                <th>CONCEPTO</th>
                                <th>DEBITO</th>
                                <th>CREDITO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comprobante['lineas'] as $linea)
                                <tr>
                                    <td>{{ $linea['cuenta'] }}</td>
                                    <td>{{ $linea['concepto'] }}</td>
                                    <td class="num">{{ $pesos($linea['debito']) }}</td>
                                    <td class="num">{{ $pesos($linea['credito']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-end">TOTALES</td>
                                <td class="num">{{ $pesosSiempre($comprobante['debito']) }}</td>
                                <td class="num">{{ $pesosSiempre($comprobante['credito']) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end">DIFERENCIA</td>
                                <td colspan="2" class="num {{ round($comprobante['diferencia']) != 0 ? 'cc-negativo' : '' }}">
                                    {{ $pesosSiempre($comprobante['diferencia']) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @if (round($comprobante['diferencia']) != 0)
                <div class="alert alert-warning mt-2 small d-print-none">
                    El comprobante no cuadra: normalmente es un sobrante o faltante de alguna planilla que no se ha trasladado.
                </div>
            @endif
        </div>

        <div class="col-12 col-xl-5 d-print-none">
            <details class="card">
                <summary class="card-body py-2 fw-semibold small" style="cursor:pointer;">Cuentas contables del comprobante</summary>
                <form method="POST" action="{{ route('informes.control-caja.cuentas') }}" class="card-body pt-0">
                    @csrf
                    <input type="hidden" name="desde" value="{{ $desde }}">
                    <input type="hidden" name="hasta" value="{{ $hasta }}">
                    <table class="table table-sm align-middle mb-2">
                        <tbody>
                            @foreach ($cuentas as $clave => $cuenta)
                                <tr>
                                    <td class="small">{{ $cuenta->descripcion }}</td>
                                    <td style="width:9rem;">
                                        <input type="text" name="cuentas[{{ $clave }}][cuenta]" class="form-control form-control-sm"
                                            value="{{ $cuenta->cuenta }}" required maxlength="30">
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
                inventarios de gasolina, ACPM y canastilla. Recibido s/planilla: total recibido en el turno; recibido
                s/lectura: venta según lecturas de surtidores más canastilla.
            </div>
        </div>
    </div>
@endsection
