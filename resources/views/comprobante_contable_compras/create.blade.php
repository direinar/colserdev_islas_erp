@extends('layouts.app')

@section('title', 'Comprobante Contable - Compras')

@section('content')
    <div class="pastel-section mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h4 class="mb-0 text-danger fw-bold">COMPROBANTE CONTABLE - COMPRAS</h4>
                <small class="text-muted">Informe contable de compras con control de debito y credito.</small>
            </div>
            <button type="submit" form="comprobante-form" class="btn btn-primary btn-sm">Guardar comprobante</button>
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

    <form id="comprobante-form" method="POST" action="{{ route('comprobante-contable-compras.store') }}">
        @csrf

        <x-erp-card title="COMPROBANTE CONTABLE - COMPRAS (INFORME)">
            <div class="p-3 pb-2">
                <div class="row g-2 align-items-end">
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
                    <div class="col-md-6 d-flex justify-content-md-end">
                        <button type="button" id="add-comprobante-row" class="btn btn-sm btn-outline-primary">+ Agregar
                            fila</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="comprobante-table">
                    <thead style="background-color:#fff000;">
                        <tr>
                            <th class="text-center align-middle">CUENTA</th>
                            <th class="text-center align-middle">CONCEPTO</th>
                            <th class="text-center align-middle">TERCERO</th>
                            <th class="text-center align-middle">NIT</th>
                            <th class="text-center align-middle">DEBITO</th>
                            <th class="text-center align-middle">CREDITO</th>
                            <th class="text-center align-middle">ACCION</th>
                        </tr>
                    </thead>

                    <tbody id="comprobante-body">
                        @php
                            $rows = old('detalles', [
                                [
                                    'cuenta' => '',
                                    'concepto' => '',
                                    'tercero' => '',
                                    'nit' => '',
                                    'debito' => '',
                                    'credito' => '',
                                ],
                            ]);
                        @endphp

                        @foreach ($rows as $index => $row)
                            <tr data-index="{{ $index }}">
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
                                    <input type="text" name="detalles[{{ $index }}][debito]"
                                        class="form-control form-control-sm text-end debito-input" inputmode="decimal"
                                        value="{{ $row['debito'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="detalles[{{ $index }}][credito]"
                                        class="form-control form-control-sm text-end credito-input" inputmode="decimal"
                                        value="{{ $row['credito'] ?? '' }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="4" class="text-end">TOTALES</td>
                            <td id="total-debito" class="text-end">0</td>
                            <td id="total-credito" class="text-end">0</td>
                            <td></td>
                        </tr>
                        <tr class="table-secondary fw-bold">
                            <td colspan="5" class="text-end">SALDO CREDITO - DEBITO</td>
                            <td id="saldo-credito-debito" class="text-end">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-erp-card>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('comprobante-body');
            const addBtn = document.getElementById('add-comprobante-row');
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

            function updateTotals() {
                let totalDebito = 0;
                let totalCredito = 0;

                tbody.querySelectorAll('tr').forEach(row => {
                    totalDebito += parseNumber(row.querySelector('.debito-input')?.value);
                    totalCredito += parseNumber(row.querySelector('.credito-input')?.value);
                });

                const saldoCreditoDebito = totalCredito - totalDebito;

                document.getElementById('total-debito').textContent = formatNumber(totalDebito);
                document.getElementById('total-credito').textContent = formatNumber(totalCredito);
                document.getElementById('saldo-credito-debito').textContent = formatNumber(saldoCreditoDebito);
            }

            function attachEvents(row) {
                row.querySelectorAll('.debito-input, .credito-input').forEach(input => {
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
                        <input type="text" name="detalles[${index}][debito]" class="form-control form-control-sm text-end debito-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="detalles[${index}][credito]" class="form-control form-control-sm text-end credito-input" inputmode="decimal">
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
                        input.value = '';
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
