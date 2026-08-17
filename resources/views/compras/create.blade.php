@extends('layouts.app')

@section('title', 'Compras')

@section('content')
    <th class="text-center align-middle">GASOLINA</th>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
        <div>
            <tr style="background-color:#ccccff;">
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
                    <thead style="background-color:#fff000;">
                        <tr>
                            <th class="text-center align-middle">FECHA</th>
                            <th class="text-center align-middle">No. FACTURA</th>
                            <th class="text-center align-middle">VR TOTAL FRA</th>
                            <th class="text-center align-middle">GASOLINA</th>
                            <th class="text-center align-middle">ACPM</th>
                            <th class="text-center align-middle">TOTAL</th>
                            <th colspan="2" class="text-center align-middle">DISTRIBUCION DEL COSTO</th>
                            <th class="text-center align-middle">ACCION</th>
                        </tr>
                        <tr>
                            <th colspan="6"></th>
                            <th class="text-center">GASOLINA</th>
                            <th class="text-center">ACPM</th>
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
                                    <input type="text" name="compras[{{ $index }}][vr_total_fra]"
                                        class="form-control form-control-sm text-end vr-total-fra-input" inputmode="decimal"
                                        value="{{ $row['vr_total_fra'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][gasolina]"
                                        class="form-control form-control-sm text-end gasolina-input" inputmode="decimal"
                                        value="{{ $row['gasolina'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][acpm]"
                                        class="form-control form-control-sm text-end acpm-input" inputmode="decimal"
                                        value="{{ $row['acpm'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][total]"
                                        class="form-control form-control-sm text-end total-input" readonly>
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][distribucion_gasolina]"
                                        class="form-control form-control-sm text-end distribucion-gasolina-input" readonly>
                                </td>
                                <td>
                                    <input type="text" name="compras[{{ $index }}][distribucion_acpm]"
                                        class="form-control form-control-sm text-end distribucion-acpm-input" readonly>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="3" class="text-end">TOTALES</td>
                            <td id="total-gasolina" class="text-end">0</td>
                            <td id="total-acpm" class="text-end">0</td>
                            <td id="total-general" class="text-end">0</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-erp-card>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('compras-body');
            const addBtn = document.getElementById('add-compra-row');
            let nextIndex = tbody.querySelectorAll('tr').length;

            function parseNumber(value) {
                if (!value) {
                    return 0;
                }

                const clean = value.toString()
                    .replace(/\./g, '')
                    .replace(/,/g, '.');

                return Number(clean) || 0;
            }

            function formatNumber(number, decimals = 0) {
                return Number(number).toLocaleString('es-CO', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals,
                });
            }

            function setFormattedValue(input, value, decimals = 0) {
                input.value = formatNumber(value, decimals);
            }

            function updateRow(row) {
                const vrTotalFraInput = row.querySelector('.vr-total-fra-input');
                const gasolinaInput = row.querySelector('.gasolina-input');
                const acpmInput = row.querySelector('.acpm-input');
                const totalInput = row.querySelector('.total-input');
                const distribucionGasolinaInput = row.querySelector('.distribucion-gasolina-input');
                const distribucionAcpmInput = row.querySelector('.distribucion-acpm-input');

                const vrTotalFra = parseNumber(vrTotalFraInput.value);
                const gasolina = parseNumber(gasolinaInput.value);
                const acpm = parseNumber(acpmInput.value);
                const total = gasolina + acpm;

                const distribucionGasolina = total > 0 ? (vrTotalFra * gasolina) / total : 0;
                const distribucionAcpm = total > 0 ? (vrTotalFra * acpm) / total : 0;

                setFormattedValue(totalInput, total, 0);
                setFormattedValue(distribucionGasolinaInput, distribucionGasolina, 0);
                setFormattedValue(distribucionAcpmInput, distribucionAcpm, 0);
            }

            function updateTotals() {
                let totalGasolina = 0;
                let totalAcpm = 0;
                let totalGeneral = 0;

                tbody.querySelectorAll('tr').forEach(row => {
                    updateRow(row);

                    totalGasolina += parseNumber(row.querySelector('.gasolina-input')?.value);
                    totalAcpm += parseNumber(row.querySelector('.acpm-input')?.value);
                    totalGeneral += parseNumber(row.querySelector('.total-input')?.value);
                });

                document.getElementById('total-gasolina').textContent = formatNumber(totalGasolina);
                document.getElementById('total-acpm').textContent = formatNumber(totalAcpm);
                document.getElementById('total-general').textContent = formatNumber(totalGeneral);
            }

            function attachEvents(row) {
                row.querySelectorAll('.vr-total-fra-input, .gasolina-input, .acpm-input').forEach(input => {
                    input.addEventListener('input', updateTotals);
                    input.addEventListener('change', updateTotals);
                    input.addEventListener('blur', function() {
                        const value = parseNumber(input.value);
                        setFormattedValue(input, value, 0);
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
                        <input type="text" name="compras[${index}][vr_total_fra]" class="form-control form-control-sm text-end vr-total-fra-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][gasolina]" class="form-control form-control-sm text-end gasolina-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][acpm]" class="form-control form-control-sm text-end acpm-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][total]" class="form-control form-control-sm text-end total-input" readonly>
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][distribucion_gasolina]" class="form-control form-control-sm text-end distribucion-gasolina-input" readonly>
                    </td>
                    <td>
                        <input type="text" name="compras[${index}][distribucion_acpm]" class="form-control form-control-sm text-end distribucion-acpm-input" readonly>
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
