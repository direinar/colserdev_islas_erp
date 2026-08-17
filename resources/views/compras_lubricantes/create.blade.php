@extends('layouts.app')

@section('title', 'Compras de Lubricantes')

@section('content')
    <div class="pastel-section mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h4 class="mb-0 text-danger fw-bold">COMPRAS DE LUBRICANTES</h4>
                <small class="text-muted">Registre cada compra y el sistema calculará VR sin IVA y total por fila.</small>
            </div>
            <button type="submit" form="compras-lubricantes-form" class="btn btn-primary btn-sm">Guardar compras</button>
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

    <form id="compras-lubricantes-form" method="POST" action="{{ route('compras-lubricantes.store') }}">
        @csrf

        <x-erp-card title="INFORMACION DE COMPRAS DE LUBRICANTES">
            <div class="d-flex justify-content-end p-3 pb-2">
                <button type="button" id="add-lub-row" class="btn btn-sm btn-outline-primary">+ Agregar fila</button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="compras-lub-table">
                    <thead style="background-color:#ccccff;">
                        <tr>
                            <th class="text-center align-middle">FECHA</th>
                            <th class="text-center align-middle">PROVEEDOR</th>
                            <th class="text-center align-middle">No. FC</th>
                            <th class="text-center align-middle">UNIDADES</th>
                            <th class="text-center align-middle">VALOR UNITARIO</th>
                            <th class="text-center align-middle">VR SIN IVA</th>
                            <th class="text-center align-middle">IVA</th>
                            <th class="text-center align-middle">TOTAL</th>
                            <th class="text-center align-middle">ACCION</th>
                        </tr>
                    </thead>

                    <tbody id="compras-lub-body">
                        @php
                            $rows = old('detalles', [
                                [
                                    'fecha' => date('Y-m-d'),
                                    'proveedor_id' => '',
                                    'no_fc' => '',
                                    'unidades' => '',
                                    'valor_unitario' => '',
                                    'iva' => '',
                                ],
                            ]);
                        @endphp

                        @foreach ($rows as $index => $row)
                            <tr data-index="{{ $index }}">
                                <td>
                                    <input type="date" name="detalles[{{ $index }}][fecha]"
                                        class="form-control form-control-sm" value="{{ $row['fecha'] ?? '' }}">
                                </td>
                                <td>
                                    <select name="detalles[{{ $index }}][proveedor_id]"
                                        class="form-select form-select-sm proveedor-select">
                                        <option value="">Seleccione proveedor</option>
                                        @foreach ($proveedores ?? collect() as $proveedor)
                                            <option value="{{ $proveedor->id }}" @selected(($row['proveedor_id'] ?? '') == $proveedor->id)>
                                                {{ $proveedor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][no_fc]"
                                        class="form-control form-control-sm" value="{{ $row['no_fc'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][unidades]"
                                        class="form-control form-control-sm text-end unidades-input" inputmode="decimal"
                                        value="{{ $row['unidades'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][valor_unitario]"
                                        class="form-control form-control-sm text-end valor-unitario-input"
                                        inputmode="decimal" value="{{ $row['valor_unitario'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][vr_sin_iva]"
                                        class="form-control form-control-sm text-end vr-sin-iva-input" readonly>
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][iva]"
                                        class="form-control form-control-sm text-end iva-input" inputmode="decimal"
                                        value="{{ $row['iva'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][total]"
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
                            <td colspan="3" class="text-end">TOTALES</td>
                            <td id="total-unidades" class="text-end">0</td>
                            <td></td>
                            <td id="total-vr-sin-iva" class="text-end">0</td>
                            <td id="total-iva" class="text-end">0</td>
                            <td id="total-general" class="text-end">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-erp-card>
    </form>

    <select id="proveedores-options-template" class="d-none">
        <option value="">Seleccione proveedor</option>
        @foreach ($proveedores ?? collect() as $proveedor)
            <option value="{{ $proveedor->id }}">{{ $proveedor->name }}</option>
        @endforeach
    </select>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('compras-lub-body');
            const addBtn = document.getElementById('add-lub-row');
            const proveedorOptionsHtml = document.getElementById('proveedores-options-template').innerHTML;
            let nextIndex = tbody.querySelectorAll('tr').length;

            function parseNumber(value) {
                if (!value) {
                    return 0;
                }

                const clean = value.toString().replace(/\./g, '').replace(/,/g, '.');
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
                const unidadesInput = row.querySelector('.unidades-input');
                const valorUnitarioInput = row.querySelector('.valor-unitario-input');
                const vrSinIvaInput = row.querySelector('.vr-sin-iva-input');
                const ivaInput = row.querySelector('.iva-input');
                const totalInput = row.querySelector('.total-input');

                const unidades = parseNumber(unidadesInput.value);
                const valorUnitario = parseNumber(valorUnitarioInput.value);
                const iva = parseNumber(ivaInput.value);

                const vrSinIva = Math.round(unidades * valorUnitario);
                const total = vrSinIva + Math.round(iva);

                setFormattedValue(vrSinIvaInput, vrSinIva, 0);
                setFormattedValue(totalInput, total, 0);
            }

            function updateTotals() {
                let totalUnidades = 0;
                let totalVrSinIva = 0;
                let totalIva = 0;
                let totalGeneral = 0;

                tbody.querySelectorAll('tr').forEach(row => {
                    updateRow(row);

                    totalUnidades += parseNumber(row.querySelector('.unidades-input')?.value);
                    totalVrSinIva += parseNumber(row.querySelector('.vr-sin-iva-input')?.value);
                    totalIva += parseNumber(row.querySelector('.iva-input')?.value);
                    totalGeneral += parseNumber(row.querySelector('.total-input')?.value);
                });

                document.getElementById('total-unidades').textContent = formatNumber(totalUnidades);
                document.getElementById('total-vr-sin-iva').textContent = formatNumber(totalVrSinIva);
                document.getElementById('total-iva').textContent = formatNumber(totalIva);
                document.getElementById('total-general').textContent = formatNumber(totalGeneral);
            }

            function attachEvents(row) {
                row.querySelectorAll('.unidades-input, .valor-unitario-input, .iva-input').forEach(input => {
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
                        <input type="date" name="detalles[${index}][fecha]" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                    </td>
                    <td>
                        <select name="detalles[${index}][proveedor_id]" class="form-select form-select-sm proveedor-select">
                            ${proveedorOptionsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][no_fc]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][unidades]" class="form-control form-control-sm text-end unidades-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][valor_unitario]" class="form-control form-control-sm text-end valor-unitario-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][vr_sin_iva]" class="form-control form-control-sm text-end vr-sin-iva-input" readonly>
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][iva]" class="form-control form-control-sm text-end iva-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][total]" class="form-control form-control-sm text-end total-input" readonly>
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
                        if (input.type !== 'date' && !input.classList.contains(
                                'vr-sin-iva-input') && !input.classList.contains('total-input')) {
                            input.value = '';
                        }
                    });

                    const firstSelect = rows[0].querySelector('.proveedor-select');
                    if (firstSelect) {
                        firstSelect.value = '';
                    }
                } else {
                    e.target.closest('tr').remove();
                }

                updateTotals();
            });

            updateTotals();
        });
    </script>
@endsection
