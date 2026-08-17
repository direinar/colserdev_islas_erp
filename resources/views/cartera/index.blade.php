@extends('layouts.app')

@section('title', 'Cartera')

@section('content')
    <div class="pastel-section mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h4 class="mb-0 text-danger fw-bold">CARTERA</h4>
                <small class="text-muted">Informe de cartera con cliente seleccionado y control de debito/credito.</small>
            </div>
            <button type="submit" form="cartera-form" class="btn btn-primary btn-sm">Guardar cartera</button>
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

    <form id="cartera-form" method="POST" action="{{ route('cartera.store') }}">
        @csrf

        <x-erp-card title="CARTERA (INFORME)">
            <div class="p-3 pb-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Cliente</label>
                        <select name="customer_id" id="customer-id" class="form-select form-select-sm" required>
                            <option value="">Seleccione cliente</option>
                            @foreach ($customers ?? collect() as $customer)
                                <option value="{{ $customer->id }}" data-name="{{ $customer->name }}"
                                    @selected(old('customer_id') == $customer->id)>
                                    {{ $customer->name }} - {{ $customer->document }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Fecha inicial</label>
                        <input type="date" name="fecha_inicial" class="form-control form-control-sm"
                            value="{{ old('fecha_inicial', date('Y-m-01')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Fecha final</label>
                        <input type="date" name="fecha_final" class="form-control form-control-sm"
                            value="{{ old('fecha_final', date('Y-m-t')) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Saldo inicial</label>
                        <input type="text" name="saldo_inicial" id="saldo-inicial"
                            class="form-control form-control-sm text-end" inputmode="decimal"
                            value="{{ old('saldo_inicial', '0') }}">
                    </div>
                    <div class="col-md-12 d-flex justify-content-md-end">
                        <button type="button" id="add-cartera-row" class="btn btn-sm btn-outline-primary">+ Agregar
                            fila</button>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="small text-muted">Cliente encabezado</div>
                    <h5 id="customer-heading" class="mb-0 fw-bold">
                        {{ old('customer_id') ? optional(($customers ?? collect())->firstWhere('id', old('customer_id')))->name : 'SIN CLIENTE SELECCIONADO' }}
                    </h5>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="cartera-table">
                    <thead style="background-color:#ccccff;">
                        <tr>
                            <th class="text-center align-middle">PLANILLAS</th>
                            <th class="text-center align-middle">FECHA</th>
                            <th class="text-center align-middle">FACTURA</th>
                            <th class="text-center align-middle">PLACAS</th>
                            <th class="text-center align-middle">PRODUCTO</th>
                            <th class="text-center align-middle">GALONES</th>
                            <th class="text-center align-middle">VR. UNITARIO</th>
                            <th class="text-center align-middle">BRUTO</th>
                            <th class="text-center align-middle">DESCUENTO</th>
                            <th class="text-center align-middle">CUENTA</th>
                            <th class="text-center align-middle">CONCEPTO</th>
                            <th class="text-center align-middle">TERCERO</th>
                            <th class="text-center align-middle">NIT</th>
                            <th class="text-center align-middle">VR. NETO CARGO</th>
                            <th class="text-center align-middle">ABONOS</th>
                            <th class="text-center align-middle">SALDO</th>
                            <th class="text-center align-middle">ACCION</th>
                        </tr>
                    </thead>

                    <tbody id="cartera-body">
                        @php
                            $rows = old('detalles', [
                                [
                                    'planillas' => '',
                                    'fecha' => '',
                                    'factura' => '',
                                    'placas' => '',
                                    'producto' => '',
                                    'galones' => '',
                                    'vr_unitario' => '',
                                    'bruto' => '',
                                    'descuento' => '',
                                    'cuenta' => '',
                                    'concepto' => '',
                                    'tercero' => '',
                                    'nit' => '',
                                    'vr_neto_cargo' => '',
                                    'abonos' => '',
                                    'saldo' => '',
                                ],
                            ]);
                        @endphp

                        @foreach ($rows as $index => $row)
                            <tr data-index="{{ $index }}">
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][planillas]"
                                        class="form-control form-control-sm" value="{{ $row['planillas'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="date" name="detalles[{{ $index }}][fecha]"
                                        class="form-control form-control-sm" value="{{ $row['fecha'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][factura]"
                                        class="form-control form-control-sm" value="{{ $row['factura'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][placas]"
                                        class="form-control form-control-sm" value="{{ $row['placas'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][producto]"
                                        class="form-control form-control-sm" value="{{ $row['producto'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][galones]"
                                        class="form-control form-control-sm text-end galones-input" inputmode="decimal"
                                        value="{{ $row['galones'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][vr_unitario]"
                                        class="form-control form-control-sm text-end vr-unitario-input"
                                        inputmode="decimal" value="{{ $row['vr_unitario'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][bruto]"
                                        class="form-control form-control-sm text-end bruto-input" readonly>
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][descuento]"
                                        class="form-control form-control-sm text-end descuento-input" inputmode="decimal"
                                        value="{{ $row['descuento'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][cuenta]"
                                        class="form-control form-control-sm" value="{{ $row['cuenta'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][concepto]"
                                        class="form-control form-control-sm" value="{{ $row['concepto'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][tercero]"
                                        class="form-control form-control-sm" value="{{ $row['tercero'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][nit]"
                                        class="form-control form-control-sm" value="{{ $row['nit'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][vr_neto_cargo]"
                                        class="form-control form-control-sm text-end vr-neto-cargo-input" readonly>
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][abonos]"
                                        class="form-control form-control-sm text-end abonos-input" inputmode="decimal"
                                        value="{{ $row['abonos'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][saldo]"
                                        class="form-control form-control-sm text-end saldo-input" readonly>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-erp-card>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('cartera-body');
            const addBtn = document.getElementById('add-cartera-row');
            const customerSelect = document.getElementById('customer-id');
            const customerHeading = document.getElementById('customer-heading');
            const saldoInicialInput = document.getElementById('saldo-inicial');
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

            function updateRow(row, saldoAnterior) {
                const galonesInput = row.querySelector('.galones-input');
                const vrUnitarioInput = row.querySelector('.vr-unitario-input');
                const brutoInput = row.querySelector('.bruto-input');
                const descuentoInput = row.querySelector('.descuento-input');
                const vrNetoCargoInput = row.querySelector('.vr-neto-cargo-input');
                const abonosInput = row.querySelector('.abonos-input');
                const saldoInput = row.querySelector('.saldo-input');

                const galones = parseNumber(galonesInput?.value);
                const vrUnitario = parseNumber(vrUnitarioInput?.value);
                const descuento = parseNumber(descuentoInput?.value);
                const abonos = parseNumber(abonosInput?.value);

                const bruto = Math.round(galones * vrUnitario);
                const vrNetoCargo = Math.max(0, bruto - Math.round(descuento));
                const saldo = saldoAnterior + vrNetoCargo - Math.round(abonos);

                setFormattedValue(brutoInput, bruto, 0);
                setFormattedValue(vrNetoCargoInput, vrNetoCargo, 0);
                setFormattedValue(saldoInput, saldo, 0);

                return saldo;
            }

            function updateCustomerHeading() {
                const selected = customerSelect.options[customerSelect.selectedIndex];
                const name = selected?.dataset?.name;
                customerHeading.textContent = name && name.trim() !== '' ? name : 'SIN CLIENTE SELECCIONADO';
            }

            function updateTotals() {
                let saldo = Math.round(parseNumber(saldoInicialInput?.value));

                tbody.querySelectorAll('tr').forEach(row => {
                    saldo = updateRow(row, saldo);
                });
            }

            function attachEvents(row) {
                row.querySelectorAll('.galones-input, .vr-unitario-input, .descuento-input, .abonos-input').forEach(
                    input => {
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
                        <input type="text" name="detalles[${index}][planillas]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="date" name="detalles[${index}][fecha]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][factura]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][placas]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][producto]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][galones]" class="form-control form-control-sm text-end galones-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][vr_unitario]" class="form-control form-control-sm text-end vr-unitario-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][bruto]" class="form-control form-control-sm text-end bruto-input" readonly>
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][descuento]" class="form-control form-control-sm text-end descuento-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][cuenta]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][concepto]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][tercero]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][nit]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][vr_neto_cargo]" class="form-control form-control-sm text-end vr-neto-cargo-input" readonly>
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][abonos]" class="form-control form-control-sm text-end abonos-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][saldo]" class="form-control form-control-sm text-end saldo-input" readonly>
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
                        if (input.type !== 'date' && !input.classList.contains('bruto-input') && !
                            input.classList
                            .contains('vr-neto-cargo-input') && !input.classList.contains(
                                'saldo-input')) {
                            input.value = '';
                        }
                    });
                } else {
                    e.target.closest('tr').remove();
                }

                updateTotals();
            });

            customerSelect.addEventListener('change', updateCustomerHeading);
            saldoInicialInput.addEventListener('input', updateTotals);
            saldoInicialInput.addEventListener('change', updateTotals);
            saldoInicialInput.addEventListener('blur', function() {
                setFormattedValue(saldoInicialInput, parseNumber(saldoInicialInput.value), 0);
            });

            updateCustomerHeading();
            updateTotals();
        });
    </script>
@endsection
