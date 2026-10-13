@extends('layouts.app')

@section('title', 'Comprobante contable de recaudos de cartera')

@php
    use Illuminate\Support\Carbon;

    $pesos = fn (float $valor): string => $valor ? number_format($valor, 0, ',', '.') : '';
    $pesosSiempre = fn (float $valor): string => number_format($valor, 0, ',', '.');
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

    <x-page-header title="Comprobante contable de recaudos de cartera" icon="journal-text"
        :subtitle="'Desde '.Carbon::parse($desde)->format('d/m/Y').' — hasta '.Carbon::parse($hasta)->format('d/m/Y').'. Débito a caja por los recaudos por islas y a cada banco por los de administración; crédito a la cartera de cada cliente.'">
        @include('informes.partials.filtro-rango', [
            'ruta' => 'informes.comprobante-recaudos.index',
            'rutaExportar' => 'informes.comprobante-recaudos.exportar',
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
        <div class="col-12 col-xl-7 d-flex flex-column gap-3">
            <div class="card">
                <div class="table-responsive">
                    <table class="rc-tabla">
                        <thead>
                            <tr>
                                <th colspan="4" class="rc-titulo-tabla">COMPROBANTE CONTABLE DE RECAUDOS DE CARTERA</th>
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
                    <form method="POST" action="{{ route('informes.comprobante-recaudos.cuentas') }}" class="card-body pt-0">
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
