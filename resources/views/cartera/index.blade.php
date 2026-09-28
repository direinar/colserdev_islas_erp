@extends('layouts.app')

@section('title', 'Estado de cuenta')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.');
    // Campos editables: en blanco cuando el valor es cero para no ensuciar la tabla.
    $moneyInput = fn ($value) => (float) $value != 0 ? number_format((float) $value, 0, ',', '.') : '';
    $galonesInput = fn ($value) => (float) $value != 0 ? rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',') : '';
    $saldoClass = fn ($value) => (float) $value < 0 ? 'text-danger fw-bold' : '';

    $hayDatosVehiculo = $movimientos->contains(fn ($m) => $m->llevaDatosVehiculo());
@endphp

@section('content')
    <style>
        .estado-cuenta-fechas .input-group-text {
            min-width: 4.5rem;
        }

        #cartera-table input.form-control-sm {
            min-width: 5.5rem;
        }

        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }

            nav,
            .navbar {
                display: none !important;
            }

            #cartera-table {
                font-size: 10px;
            }

            #cartera-table input {
                border: 0;
                padding: 0;
                background: transparent;
                min-width: 0 !important;
            }

            #cartera-table thead {
                background-color: #ccccff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .card {
                border: 0;
            }
        }
    </style>

    <div class="pastel-section mb-3 d-print-none">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <h4 class="mb-0 text-danger fw-bold">ESTADO DE CUENTA</h4>
            @if ($customer)
                <div class="d-flex flex-wrap gap-2">
                    @if ($hayDatosVehiculo)
                        <button type="submit" form="cartera-form" class="btn btn-primary btn-sm">
                            <i class="bi bi-truck"></i> Guardar datos del vehículo
                        </button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                    <a href="{{ route('cartera.exportar', ['customer_id' => $customer->id, 'fecha_inicial' => $fechaInicial, 'fecha_final' => $fechaFinal]) }}"
                        class="btn btn-outline-success btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-print-none">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger d-print-none">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-erp-card>
        <form method="GET" action="{{ route('cartera.index') }}" id="cartera-filtro" class="p-3 pb-2 d-print-none">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1" for="customer-id">Cliente</label>
                    <select name="customer_id" id="customer-id" class="form-select form-select-sm"
                        onchange="this.form.submit()">
                        <option value="">Seleccione cliente</option>
                        @foreach ($customers as $item)
                            <option value="{{ $item->id }}" @selected($customer?->id === $item->id)>
                                {{ $item->name }} - {{ $item->document }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 estado-cuenta-fechas">
                    <div class="input-group input-group-sm">
                        <label class="input-group-text" for="fecha-inicial">Desde</label>
                        <input type="date" name="fecha_inicial" id="fecha-inicial" class="form-control"
                            value="{{ $fechaInicial }}" onchange="this.form.submit()">
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 estado-cuenta-fechas">
                    <div class="input-group input-group-sm">
                        <label class="input-group-text" for="fecha-final">Hasta</label>
                        <input type="date" name="fecha_final" id="fecha-final" class="form-control"
                            value="{{ $fechaFinal }}" onchange="this.form.submit()">
                    </div>
                </div>
            </div>
        </form>

        @if (! $customer)
            <div class="p-3 pt-2 text-muted">
                Seleccione un cliente para ver su estado de cuenta.
            </div>
        @else
            <div class="px-3 pb-2 pt-2">
                <div class="d-none d-print-block">
                    <h4 class="fw-bold mb-1">ESTADO DE CUENTA</h4>
                </div>
                <div class="small text-muted">Cliente</div>
                <h5 class="mb-0 fw-bold">{{ $customer->name }}</h5>
                <div class="small text-muted">NIT / Documento: {{ $customer->document }}</div>
                <div class="small text-muted d-none d-print-block">
                    Periodo: {{ \Illuminate\Support\Carbon::parse($fechaInicial)->format('d/m/Y') }}
                    al {{ \Illuminate\Support\Carbon::parse($fechaFinal)->format('d/m/Y') }}
                    · Impreso: {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>

            <form id="cartera-form" method="POST" action="{{ route('cartera.store') }}">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                <input type="hidden" name="fecha_inicial" value="{{ $fechaInicial }}">
                <input type="hidden" name="fecha_final" value="{{ $fechaFinal }}">

                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0" id="cartera-table">
                        <thead style="background-color:#ccccff;">
                            <tr>
                                <th class="text-center align-middle">PLANILLA</th>
                                <th class="text-center align-middle">FECHA</th>
                                <th class="text-center align-middle">FACTURA</th>
                                <th class="text-center align-middle">PLACAS</th>
                                <th class="text-center align-middle">PRODUCTO</th>
                                <th class="text-center align-middle">GALONES</th>
                                <th class="text-center align-middle">VR. UNITARIO</th>
                                <th class="text-center align-middle">BRUTO</th>
                                <th class="text-center align-middle">DESCUENTO</th>
                                <th class="text-center align-middle">CONCEPTO</th>
                                <th class="text-center align-middle">VR. NETO CARGO</th>
                                <th class="text-center align-middle">ABONOS</th>
                                <th class="text-center align-middle">SALDO</th>
                                <th class="text-center align-middle d-print-none">ESTADO</th>
                            </tr>
                        </thead>

                        <tbody id="cartera-body">
                            <tr class="table-light fw-bold" id="cartera-saldo-anterior">
                                <td colspan="10">
                                    SALDO ANTERIOR al {{ \Illuminate\Support\Carbon::parse($fechaInicial)->subDay()->format('d/m/Y') }}
                                </td>
                                <td></td>
                                <td></td>
                                <td class="text-end {{ $saldoClass($saldoAnterior) }}">{{ $money($saldoAnterior) }}</td>
                                <td class="d-print-none"></td>
                            </tr>

                            @foreach ($movimientos as $index => $m)
                                @php $vehiculo = $m->llevaDatosVehiculo(); @endphp
                                <tr data-id="{{ $m->id }}" @class(['cartera-fila-vehiculo' => $vehiculo])>
                                    <td class="text-nowrap">
                                        @if ($m->esDeTurno())
                                            <a href="{{ route('turnos.create', ['turno_busqueda' => $m->planillas]) }}"
                                                class="text-decoration-none" title="Abrir planilla del turno">{{ $m->planillas }}</a>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $m->fecha?->format('d/m/Y') }}</td>
                                    <td>{{ $m->factura }}</td>

                                    @if ($vehiculo)
                                        <td>
                                            <input type="hidden" name="detalles[{{ $index }}][id]" value="{{ $m->id }}">
                                            <input type="text" name="detalles[{{ $index }}][placas]"
                                                class="form-control form-control-sm" value="{{ $m->placas }}">
                                        </td>
                                        <td>
                                            <input type="text" name="detalles[{{ $index }}][producto]"
                                                class="form-control form-control-sm" value="{{ $m->producto }}">
                                        </td>
                                        <td>
                                            <input type="text" name="detalles[{{ $index }}][galones]"
                                                class="form-control form-control-sm text-end galones-input" inputmode="decimal"
                                                value="{{ $galonesInput($m->galones) }}">
                                        </td>
                                        <td>
                                            <input type="text" name="detalles[{{ $index }}][vr_unitario]"
                                                class="form-control form-control-sm text-end vr-unitario-input money-input"
                                                inputmode="decimal" value="{{ $moneyInput($m->vr_unitario) }}">
                                        </td>
                                        <td class="text-end bruto-cell">{{ $moneyInput($m->bruto) }}</td>
                                        <td>
                                            <input type="text" name="detalles[{{ $index }}][descuento]"
                                                class="form-control form-control-sm text-end money-input"
                                                inputmode="decimal" value="{{ $moneyInput($m->descuento) }}"
                                                title="Informativo: no cambia el cargo ni el saldo">
                                        </td>
                                    @else
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    @endif

                                    <td>{{ $m->concepto }}</td>
                                    <td class="text-end">{{ $moneyInput($m->vr_neto_cargo) }}</td>
                                    <td class="text-end">{{ $moneyInput($m->abonos) }}</td>
                                    <td class="text-end {{ $saldoClass($m->saldo_corrido) }}">{{ $money($m->saldo_corrido) }}</td>
                                    <td class="text-center d-print-none">
                                        @if ($m->esDeTurno() && $m->turno?->revisado)
                                            <i class="bi bi-lock-fill text-danger"
                                                title="Planilla REVISADA por {{ $m->turno->revisado_por }}{{ $m->turno->revisado_at ? ' el '.$m->turno->revisado_at->format('d/m/Y') : '' }}: los valores no tendrán modificaciones"></i>
                                        @elseif ($m->esDeTurno())
                                            <i class="bi bi-unlock text-muted" title="Planilla pendiente de revisión"></i>
                                        @elseif ($m->esSaldoInicial())
                                            <span class="badge text-bg-secondary">Saldo inicial</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            @if ($movimientos->isEmpty())
                                <tr>
                                    <td colspan="14" class="text-center text-muted">
                                        El cliente no tiene movimientos entre las fechas seleccionadas.
                                    </td>
                                </tr>
                            @endif
                        </tbody>

                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="10" class="text-end">TOTALES</td>
                                <td class="text-end" id="total-cargos">{{ $money($totalCargos) }}</td>
                                <td class="text-end" id="total-abonos">{{ $money($totalAbonos) }}</td>
                                <td class="text-end {{ $saldoClass($saldoFinal) }}" id="saldo-final">{{ $money($saldoFinal) }}</td>
                                <td class="d-print-none"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </form>
        @endif
    </x-erp-card>

    @if ($customer && $hayDatosVehiculo)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Formato es-CO: punto = miles, coma = decimales.
                function parseNumber(value) {
                    if (!value) {
                        return 0;
                    }

                    const clean = value.toString().replace(/\./g, '').replace(/,/g, '.');
                    return Number(clean) || 0;
                }

                function formatNumber(number, decimals = 0) {
                    return Number(number).toLocaleString('es-CO', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: decimals,
                    });
                }

                // BRUTO = galones x vr. unitario (dato del vehículo; el cargo lo fija la planilla).
                function updateBruto(row) {
                    const galones = parseNumber(row.querySelector('.galones-input').value);
                    const vrUnitario = parseNumber(row.querySelector('.vr-unitario-input').value);
                    const bruto = Math.round(galones * vrUnitario);

                    row.querySelector('.bruto-cell').textContent = bruto ? formatNumber(bruto) : '';
                }

                document.querySelectorAll('#cartera-body .cartera-fila-vehiculo').forEach(row => {
                    row.querySelectorAll('.galones-input, .money-input').forEach(input => {
                        input.addEventListener('input', () => updateBruto(row));
                        input.addEventListener('blur', function() {
                            if (input.value.trim() === '') {
                                return;
                            }

                            const decimals = input.classList.contains('galones-input') ? 3 : 0;
                            input.value = formatNumber(parseNumber(input.value), decimals);
                            updateBruto(row);
                        });
                    });
                });
            });
        </script>
    @endif
@endsection
