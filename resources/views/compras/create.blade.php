@extends('layouts.app')

@section('title', 'Compras')

@section('content')
    <div class="pastel-section mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h4 class="mb-0 text-danger fw-bold">COMPRAS DE COMBUSTIBLE</h4>
                <small class="text-muted">Registre compras por factura con distribución de costo entre gasolina y
                    ACPM.</small>
            </div>
            <button type="submit" form="compras-form" class="btn btn-primary btn-sm">Guardar factura</button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="compras-form" method="POST" action="{{ route('compras.store') }}">
        @csrf

        <x-erp-card title="INFORMACION DE COMPRAS">
            <div class="d-flex justify-content-end p-3 pb-2">
                <button type="button" id="add-compra-row" class="btn btn-sm btn-outline-primary">+ Agregar fila</button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="compras-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center align-middle border-end">FECHA</th>
                            <th class="text-center align-middle border-end">No. FACTURA</th>
                            <th colspan="2" class="text-center align-middle border-end">GASOLINA</th>
                            <th colspan="2" class="text-center align-middle border-end">ACPM</th>
                            <th colspan="2" class="text-center align-middle border-end">TOTAL</th>
                            <th class="text-center align-middle">ACCION</th>
                        </tr>
                        <tr>
                            <th colspan="2" class="border-end"></th>
                            <th class="text-center border-end">UNIDADES</th>
                            <th class="text-center border-end">DISTRIB. COSTO</th>
                            <th class="text-center border-end">UNIDADES</th>
                            <th class="text-center border-end">DISTRIB. COSTO</th>
                            <th class="text-center border-end">VALOR</th>
                            <th class="text-center border-end">UNIDADES</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody id="compras-body">
                        @php
                            $rows = old('compras', [
                                [
                                    'fecha' => date('Y-m-d'),
                                    'factura' => '',
                                    'vr_total_fra' => '',
                                    'gasolina' => '',
                                    'acpm' => '',
                                    'total' => '',
                                    'distribucion_gasolina' => '',
                                    'distribucion_acpm' => '',
                                ],
                            ]);
                        @endphp

                        @foreach ($rows as $index => $row)
                            <tr data-index="{{ $index }}">
                                <td>
                                    <input type="date" name="compras[{{ $index }}][fecha]"
                                        class="form-control form-control-sm" value="{{ $row['fecha'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][factura]"
                                        class="form-control form-control-sm" value="{{ $row['factura'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][gasolina]"
                                        class="form-control form-control-sm text-end gasolina-input" inputmode="decimal"
                                        value="{{ $row['gasolina'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][distribucion_gasolina]"
                                        class="form-control form-control-sm text-end distribucion-gasolina-input"
                                        inputmode="decimal" value="{{ $row['distribucion_gasolina'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][acpm]"
                                        class="form-control form-control-sm text-end acpm-input" inputmode="decimal"
                                        value="{{ $row['acpm'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][distribucion_acpm]"
                                        class="form-control form-control-sm text-end distribucion-acpm-input"
                                        inputmode="decimal" value="{{ $row['distribucion_acpm'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][vr_total_fra]"
                                        class="form-control form-control-sm text-end vr-total-fra-input" readonly>
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][total]"
                                        class="form-control form-control-sm text-end total-input" readonly>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="2" class="text-end">TOTALES</td>
                            <td id="total-gasolina" class="text-end">0</td>
                            <td id="total-distrib-gasolina" class="text-end">0</td>
                            <td id="total-acpm" class="text-end">0</td>
                            <td id="total-distrib-acpm" class="text-end">0</td>
                            <td id="total-valor" class="text-end">0</td>
                            <td id="total-unidades" class="text-end">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-erp-card>
    </form>

    <style>
        #compras-registradas tr:target td {
            background-color: #fff3cd;
        }
    </style>

    <x-erp-card title="COMPRAS REGISTRADAS">
        <form method="GET" action="{{ route('compras.create') }}"
            class="d-flex flex-wrap align-items-end gap-2 p-3 pb-2">
            <div>
                <label for="compras-desde" class="form-label small mb-1">Desde</label>
                <input type="date" id="compras-desde" name="desde" class="form-control form-control-sm"
                    value="{{ $desde }}">
            </div>
            <div>
                <label for="compras-hasta" class="form-label small mb-1">Hasta</label>
                <input type="date" id="compras-hasta" name="hasta" class="form-control form-control-sm"
                    value="{{ $hasta }}">
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Consultar</button>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle" id="compras-registradas">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="text-center align-middle">FECHA</th>
                        <th rowspan="2" class="text-center align-middle">No. FACTURA</th>
                        <th colspan="2" class="text-center">GASOLINA</th>
                        <th colspan="2" class="text-center">ACPM</th>
                        <th colspan="2" class="text-center">TOTAL</th>
                        <th rowspan="2" class="text-center align-middle">ACCION</th>
                    </tr>
                    <tr>
                        <th class="text-center">GALONES</th>
                        <th class="text-center">DISTRIB. COSTO</th>
                        <th class="text-center">GALONES</th>
                        <th class="text-center">DISTRIB. COSTO</th>
                        <th class="text-center">VALOR</th>
                        <th class="text-center">GALONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comprasRegistradas as $compra)
                        <tr id="compra-{{ $compra->id }}">
                            <td class="text-center">{{ $compra->fecha->format('d/m/Y') }}</td>
                            <td>{{ $compra->factura }}</td>
                            <td class="text-end">{{ number_format((float) $compra->gasolina, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $compra->distribucion_gasolina, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $compra->acpm, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $compra->distribucion_acpm, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $compra->vr_total_fra, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $compra->total, 0, ',', '.') }}</td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('compras.edit', $compra) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form method="POST" action="{{ route('compras.destroy', $compra) }}" class="d-inline"
                                    onsubmit="return confirm('¿Eliminar la compra {{ $compra->factura }}? El inventario se recalcula sin ella.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">No hay compras en el rango seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-secondary fw-bold">
                        <td colspan="2" class="text-end">TOTALES</td>
                        <td class="text-end">{{ number_format((float) $comprasRegistradas->sum('gasolina'), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $comprasRegistradas->sum('distribucion_gasolina'), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $comprasRegistradas->sum('acpm'), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $comprasRegistradas->sum('distribucion_acpm'), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $comprasRegistradas->sum('vr_total_fra'), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $comprasRegistradas->sum('total'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-erp-card>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('compras-body');
            const addBtn = document.getElementById('add-compra-row');
            let nextIndex = tbody.querySelectorAll('tr').length;

            function parseNumber(value) {
                if (value === null || value === undefined || value === '') {
                    return 0;
                }

                const clean = String(value).trim()
                    .replace(/\s+/g, '')
                    .replace(/\./g, '')
                    .replace(/,/g, '.');

                return Number(clean) || 0;
            }

            function formatNumber(number) {
                const value = Number(number);

                if (!Number.isFinite(value)) {
                    return '0';
                }

                return value.toString();
            }

            function setFormattedValue(input, value) {
                input.value = formatNumber(value);
            }

            function updateRow(row) {
                const vrTotalFraInput = row.querySelector('.vr-total-fra-input');
                const gasolinaInput = row.querySelector('.gasolina-input');
                const acpmInput = row.querySelector('.acpm-input');
                const totalInput = row.querySelector('.total-input');
                const distribucionGasolinaInput = row.querySelector('.distribucion-gasolina-input');
                const distribucionAcpmInput = row.querySelector('.distribucion-acpm-input');

                const gasolina = parseNumber(gasolinaInput.value);
                const acpm = parseNumber(acpmInput.value);
                const distribucionGasolina = parseNumber(distribucionGasolinaInput.value);
                const distribucionAcpm = parseNumber(distribucionAcpmInput.value);
                const totalUnidades = gasolina + acpm;
                const totalValor = distribucionGasolina + distribucionAcpm;

                setFormattedValue(totalInput, totalUnidades);
                setFormattedValue(vrTotalFraInput, totalValor);
            }

            function updateTotals() {
                let totalGasolina = 0;
                let totalDistribGasolina = 0;
                let totalAcpm = 0;
                let totalDistribAcpm = 0;
                let totalValor = 0;
                let totalUnidades = 0;

                tbody.querySelectorAll('tr').forEach(row => {
                    updateRow(row);

                    totalGasolina += parseNumber(row.querySelector('.gasolina-input')?.value);
                    totalDistribGasolina += parseNumber(row.querySelector('.distribucion-gasolina-input')
                        ?.value);
                    totalAcpm += parseNumber(row.querySelector('.acpm-input')?.value);
                    totalDistribAcpm += parseNumber(row.querySelector('.distribucion-acpm-input')?.value);
                    totalValor += parseNumber(row.querySelector('.vr-total-fra-input')?.value);
                    totalUnidades += parseNumber(row.querySelector('.total-input')?.value);
                });

                document.getElementById('total-gasolina').textContent = formatNumber(totalGasolina);
                document.getElementById('total-distrib-gasolina').textContent = formatNumber(totalDistribGasolina);
                document.getElementById('total-acpm').textContent = formatNumber(totalAcpm);
                document.getElementById('total-distrib-acpm').textContent = formatNumber(totalDistribAcpm);
                document.getElementById('total-valor').textContent = formatNumber(totalValor);
                document.getElementById('total-unidades').textContent = formatNumber(totalUnidades);
            }

            function attachEvents(row) {
                row.querySelectorAll(
                        '.gasolina-input, .distribucion-gasolina-input, .acpm-input, .distribucion-acpm-input')
                    .forEach(input => {
                        input.addEventListener('input', updateTotals);
                        input.addEventListener('change', updateTotals);
                        input.addEventListener('blur', function() {
                            const value = parseNumber(input.value);
                            setFormattedValue(input, value);
                        });
                    });
            }

            function createRow(index) {
                const tr = document.createElement('tr');
                tr.dataset.index = index;
                tr.innerHTML = `
                    <td>
                        <input type="date" name="compras[${index}][fecha]" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][factura]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][gasolina]" class="form-control form-control-sm text-end gasolina-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][distribucion_gasolina]" class="form-control form-control-sm text-end distribucion-gasolina-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][acpm]" class="form-control form-control-sm text-end acpm-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][distribucion_acpm]" class="form-control form-control-sm text-end distribucion-acpm-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][vr_total_fra]" class="form-control form-control-sm text-end vr-total-fra-input" readonly>
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][total]" class="form-control form-control-sm text-end total-input" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                    </td>
                `;

                return tr;
            }

            tbody.querySelectorAll('tr').forEach(row => attachEvents(row));

            addBtn.addEventListener('click', function() {
                const row = createRow(nextIndex);
                tbody.appendChild(row);
                attachEvents(row);
                nextIndex += 1;
                updateTotals();
            });

            tbody.addEventListener('click', function(e) {
                if (!e.target.classList.contains('remove-row')) {
                    return;
                }

                const rows = tbody.querySelectorAll('tr');
                if (rows.length === 1) {
                    rows[0].querySelectorAll('input').forEach(input => {
                        if (input.type !== 'date') {
                            input.value = '';
                        }
                    });
                } else {
                    e.target.closest('tr').remove();
                }

                updateTotals();
            });

            updateTotals();
        });
    </script>
@endsection
