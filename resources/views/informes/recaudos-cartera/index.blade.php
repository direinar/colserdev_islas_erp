@extends('layouts.app')

@section('title', 'Informe Recaudos de Cartera')

@php
    use App\Services\RecaudosCarteraService;
    use Illuminate\Support\Carbon;

    $pesos = fn (float $valor): string => $valor ? number_format($valor, 0, ',', '.') : '';
    $pesosSiempre = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $rango = ['desde' => $desde, 'hasta' => $hasta];
    $esAdministrador = auth()->user()->isAdministrador();
@endphp

@section('content')
    <style>
        .rc-tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: .84rem;
            color: #000;
        }

        .rc-tabla th,
        .rc-tabla td {
            border: 1px solid #8c8c8c;
            padding: .25rem .5rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        .rc-tabla thead th {
            background: #ddebf7;
            text-align: center;
            font-weight: 700;
            font-size: .76rem;
        }

        .rc-tabla .rc-titulo-tabla {
            background: #1f4e78;
            color: #fff;
            font-size: .9rem;
        }

        .rc-tabla td.num { text-align: right; }
        .rc-tabla .rc-admin { background: #f7fbff; }

        .rc-tabla tfoot td {
            background: #fff2cc;
            font-weight: 800;
        }

        .rc-titulo {
            color: #ff0000;
            font-weight: 800;
            font-size: 1.6rem;
        }

        @page {
            size: landscape;
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

            .rc-tabla {
                font-size: 9px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <div class="card mb-3">
        <div class="card-body py-2 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-2">
            <div>
                <div class="rc-titulo">INFORME DE RECAUDOS DE CARTERA</div>
                <div class="small">
                    DESDE {{ Carbon::parse($desde)->format('d/m/Y') }} — HASTA {{ Carbon::parse($hasta)->format('d/m/Y') }}.
                    Recaudos por islas y por administración de las planillas de turno.
                </div>
            </div>

            <form method="GET" action="{{ route('informes.recaudos-cartera.index') }}"
                class="d-flex flex-wrap gap-2 align-items-end d-print-none">
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
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer"></i> IMPRIMIR
                </button>
                <a href="{{ route('informes.recaudos-cartera.exportar', $rango) }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> EXCEL
                </a>
            </form>
        </div>
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

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="table-responsive">
                    <table class="rc-tabla">
                        <thead>
                            <tr>
                                <th colspan="6" class="rc-titulo-tabla">RECAUDOS DE CARTERA</th>
                            </tr>
                            <tr>
                                <th>PLANILLA</th>
                                <th>FECHA</th>
                                <th>CLIENTE</th>
                                <th>BANCO</th>
                                <th>CONCEPTO</th>
                                <th>VALOR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recaudos as $recaudo)
                                <tr class="{{ $recaudo['concepto'] === RecaudosCarteraService::CONCEPTO_ADMIN ? 'rc-admin' : '' }}">
                                    <td class="text-center">
                                        <a href="{{ route('turnos.create', ['turno_busqueda' => $recaudo['planilla']]) }}"
                                            class="text-decoration-none" title="Abrir planilla del turno">{{ $recaudo['planilla'] }}</a>
                                    </td>
                                    <td class="text-center">{{ Carbon::parse($recaudo['fecha'])->format('d/m/Y') }}</td>
                                    <td>{{ $recaudo['cliente'] }}</td>
                                    <td>{{ $recaudo['banco'] }}</td>
                                    <td>{{ $recaudo['concepto'] }}</td>
                                    <td class="num">{{ $pesosSiempre($recaudo['valor']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No hay recaudos en el periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end">TOTAL DEL PERIODO</td>
                                <td class="num">{{ $pesosSiempre($total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5 d-flex flex-column gap-3">
            <div class="card">
                <div class="table-responsive">
                    <table class="rc-tabla">
                        <thead>
                            <tr>
                                <th colspan="4" class="rc-titulo-tabla">COMPROBANTE CONTABLE - RECAUDOS DE CARTERA</th>
                            </tr>
                            <tr>
                                <th>CUENTA</th>
                                <th>NOMBRE</th>
                                <th>DEBITO</th>
                                <th>CREDITO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comprobante['lineas'] as $linea)
                                <tr>
                                    <td>{{ $linea['cuenta'] }}</td>
                                    <td>{{ $linea['nombre'] }}</td>
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
                                <td colspan="2" class="num">{{ $pesosSiempre($comprobante['diferencia']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if ($bancos->contains(fn ($banco) => blank($banco->cuenta_contable)))
                <div class="alert alert-warning small mb-0 d-print-none">
                    Hay bancos sin cuenta contable: {{ $bancos->filter(fn ($banco) => blank($banco->cuenta_contable))->pluck('name')->implode(', ') }}.
                    @unless ($esAdministrador)
                        Solicítela al administrador.
                    @endunless
                </div>
            @endif

            @if ($esAdministrador)
                <details class="card d-print-none">
                    <summary class="card-body py-2 fw-semibold small" style="cursor:pointer;">Cuentas contables del comprobante</summary>
                    <form method="POST" action="{{ route('informes.recaudos-cartera.cuentas') }}" class="card-body pt-0">
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
                                @foreach ($bancos as $banco)
                                    <tr>
                                        <td class="small">Banco / caja: {{ $banco->name }} (débito)</td>
                                        <td>
                                            <input type="text" name="bancos[{{ $banco->id }}]" class="form-control form-control-sm"
                                                value="{{ $banco->cuenta_contable }}" maxlength="30">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-outline-primary btn-sm">Guardar cuentas</button>
                    </form>
                </details>
            @endif
        </div>
    </div>
@endsection
