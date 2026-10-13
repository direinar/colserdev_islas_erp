@extends('layouts.app')

@section('title', 'Informe Recaudos de Cartera')

@php
    use App\Services\RecaudosCarteraService;
    use Illuminate\Support\Carbon;

    $pesos = fn (float $valor): string => $valor ? number_format($valor, 0, ',', '.') : '';
    $pesosSiempre = fn (float $valor): string => number_format($valor, 0, ',', '.');
    $rango = ['desde' => $desde, 'hasta' => $hasta];

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

    <x-page-header title="Informe de recaudos de cartera" icon="wallet2"
        :subtitle="'Desde '.Carbon::parse($desde)->format('d/m/Y').' — hasta '.Carbon::parse($hasta)->format('d/m/Y').'. Recaudos por islas y por administración de las planillas de turno.'">
        @include('informes.partials.filtro-rango', [
            'ruta' => 'informes.recaudos-cartera.index',
            'rutaExportar' => 'informes.recaudos-cartera.exportar',
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
        <div class="col-12 col-xl-9">
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

    </div>
@endsection
