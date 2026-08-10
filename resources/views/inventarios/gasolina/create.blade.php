@extends('layouts.app')

@section('title', 'Inventarios Gasolina')

@section('content')
    <div class="pastel-section mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h4 class="mb-0 text-danger fw-bold">INVENTARIOS GASOLINA</h4>
                <small class="text-muted">Formato de control por planilla con kardex de costo promedio y ventas.</small>
            </div>
            <button type="submit" form="inventario-gasolina-form" class="btn btn-primary btn-sm">Guardar inventario</button>
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

    <form id="inventario-gasolina-form" method="POST" action="{{ route('inventarios-gasolina.store') }}">
        @csrf

        @php
            $rows = old('rows', [
                [
                    'fecha' => date('Y-m-d'),
                    'planilla_no' => '',
                    'fc_compra_no' => '',
                    'entradas_galones' => '',
                    'salidas_galones' => '',
                    'valor_entradas' => '',
                    'precio_venta' => old('precio_venta_default', '0'),
                ],
            ]);
        @endphp

        <x-erp-card title="INVENTARIOS GASOLINA">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 align-middle" id="inventario-gasolina-table">
                    <thead>
                        <tr style="background-color:#000; color:#ff1c1c; letter-spacing:.25rem; font-weight:800;">
                            <th colspan="12" class="text-center fs-5">INVENTARIOS&nbsp;&nbsp;&nbsp;&nbsp;GASOLINA</th>
                        </tr>
                        <tr style="background-color:#f6e7d4;">
                            <th colspan="2" class="text-center align-middle">PLANILLA</th>
                            <th colspan="3" class="text-center align-middle">GALONES</th>
                            <th colspan="4" class="text-center align-middle">VALORES AL COSTO PROMEDIO</th>
                            <th colspan="2" class="text-center align-middle">VENTAS</th>
                            <th rowspan="2" class="text-center align-middle">ACCIÓN</th>
                        </tr>
                        <tr style="background-color:#f6e7d4;">
                            <th class="text-center">FECHA</th>
                            <th class="text-center">No.</th>
                            <th class="text-center">No. FC DE COMPRA</th>
                            <th class="text-center">ENTRADAS</th>
                            <th class="text-center">SALIDAS</th>
                            <th class="text-center">SALDO</th>
                            <th class="text-center">ENTRADAS</th>
                            <th class="text-center">SALIDAS</th>
                            <th class="text-center">SALDO</th>
                            <th class="text-center">PROMEDIO</th>
                            <th class="text-center">VR VENTA</th>
                            <th class="text-center">PRECIO</th>
                        </tr>
                    </thead>

                    <tbody id="inventario-gasolina-body">
                        <tr style="background-color:#eef1f5; font-weight:700;">
                            <td colspan="5" class="text-uppercase">Saldo anterior</td>
                            <td>
                                <input type="text" name="saldo_anterior_galones" id="saldo-anterior-galones"
                                    class="form-control form-control-sm text-end" inputmode="decimal"
                                    value="{{ old('saldo_anterior_galones', '0') }}">
                            </td>
                            <td></td>
                            <td></td>
                            <td>
                                <input type="text" name="saldo_anterior_valor" id="saldo-anterior-valor"
                                    class="form-control form-control-sm text-end" inputmode="decimal"
                                    value="{{ old('saldo_anterior_valor', '0') }}">
                            </td>
                            <td>
                                <input type="text" name="saldo_anterior_promedio" id="saldo-anterior-promedio"
                                    class="form-control form-control-sm text-end" inputmode="decimal"
                                    value="{{ old('saldo_anterior_promedio', '0') }}">
                            </td>
                            <td></td>
                            <td>
                                <input type="text" id="precio-venta-default"
                                    class="form-control form-control-sm text-end" inputmode="decimal"
                                    value="{{ old('precio_venta_default', '0') }}">
                            </td>
                        </tr>

                        @foreach ($rows as $index => $row)
                            <tr data-index="{{ $index }}">
                                <td>
                                    <input type="date" name="rows[{{ $index }}][fecha]"
                                        class="form-control form-control-sm" value="{{ $row['fecha'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="rows[{{ $index }}][planilla_no]"
                                        class="form-control form-control-sm text-end" inputmode="numeric"
                                        value="{{ $row['planilla_no'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="rows[{{ $index }}][fc_compra_no]"
                                        class="form-control form-control-sm" value="{{ $row['fc_compra_no'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="rows[{{ $index }}][entradas_galones]"
                                        class="form-control form-control-sm text-end entradas-galones-input"
                                        inputmode="decimal" value="{{ $row['entradas_galones'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" name="rows[{{ $index }}][salidas_galones]"
                                        class="form-control form-control-sm text-end salidas-galones-input"
                                        inputmode="decimal" value="{{ $row['salidas_galones'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-end saldo-galones-output"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" name="rows[{{ $index }}][valor_entradas]"
                                        class="form-control form-control-sm text-end valor-entradas-input"
                                        inputmode="decimal" value="{{ $row['valor_entradas'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text"
                                        class="form-control form-control-sm text-end valor-salidas-output" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-end valor-saldo-output"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-end promedio-output"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-end vr-venta-output"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" name="rows[{{ $index }}][precio_venta]"
                                        class="form-control form-control-sm text-end precio-venta-input"
                                        inputmode="decimal"
                                        value="{{ $row['precio_venta'] ?? old('precio_venta_default', '0') }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td colspan="4" class="text-end">Totales</td>
                            <td id="total-salidas" class="text-end">0</td>
                            <td id="total-saldo" class="text-end">0</td>
                            <td id="total-valor-entradas" class="text-end">0</td>
                            <td id="total-valor-salidas" class="text-end">0</td>
                            <td id="total-valor-saldo" class="text-end">0</td>
                            <td></td>
                            <td id="total-vr-venta" class="text-end">0</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="p-3 d-flex justify-content-end">
                <button type="button" id="add-inventario-gasolina-row" class="btn btn-sm btn-outline-primary">+ Agregar
                    fila</button>
            </div>
        </x-erp-card>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('inventario-gasolina-body');
            const addBtn = document.getElementById('add-inventario-gasolina-row');
            const saldoAnteriorGalonesInput = document.getElementById('saldo-anterior-galones');
            const saldoAnteriorValorInput = document.getElementById('saldo-anterior-valor');
            const saldoAnteriorPromedioInput = document.getElementById('saldo-anterior-promedio');
            const precioVentaDefaultInput = document.getElementById('precio-venta-default');
            let nextIndex = tbody.querySelectorAll('tr[data-index]').length;

            function parseNumber(value) {
                if (!value) {
                    return 0;
                }

                const clean = String(value).replace(/\./g, '').replace(/,/g, '.');
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

            function getRows() {
                return Array.from(tbody.querySelectorAll('tr[data-index]'));
            }

            function updateTotals(acc) {
                document.getElementById('total-salidas').textContent = formatNumber(acc.totalSalidas, 3);
                document.getElementById('total-saldo').textContent = formatNumber(acc.ultimoSaldoGalones, 3);
                document.getElementById('total-valor-entradas').textContent = formatNumber(acc.totalValorEntradas,
                    0);
                document.getElementById('total-valor-salidas').textContent = formatNumber(acc.totalValorSalidas, 0);
                document.getElementById('total-valor-saldo').textContent = formatNumber(acc.ultimoValorSaldo, 0);
                document.getElementById('total-vr-venta').textContent = formatNumber(acc.totalVrVenta, 0);
            }

            function updateRows() {
                let saldoAnteriorGalones = parseNumber(saldoAnteriorGalonesInput.value);
                let saldoAnteriorValor = parseNumber(saldoAnteriorValorInput.value);
                let saldoAnteriorPromedio = parseNumber(saldoAnteriorPromedioInput.value);

                if (saldoAnteriorPromedio <= 0 && saldoAnteriorGalones > 0) {
                    saldoAnteriorPromedio = saldoAnteriorValor / saldoAnteriorGalones;
                    setFormattedValue(saldoAnteriorPromedioInput, saldoAnteriorPromedio, 0);
                }

                const totals = {
                    totalSalidas: 0,
                    ultimoSaldoGalones: saldoAnteriorGalones,
                    totalValorEntradas: 0,
                    totalValorSalidas: 0,
                    ultimoValorSaldo: saldoAnteriorValor,
                    totalVrVenta: 0,
                };

                getRows().forEach(row => {
                    const entradasInput = row.querySelector('.entradas-galones-input');
                    const salidasInput = row.querySelector('.salidas-galones-input');
                    const saldoOutput = row.querySelector('.saldo-galones-output');
                    const valorEntradasInput = row.querySelector('.valor-entradas-input');
                    const valorSalidasOutput = row.querySelector('.valor-salidas-output');
                    const valorSaldoOutput = row.querySelector('.valor-saldo-output');
                    const promedioOutput = row.querySelector('.promedio-output');
                    const vrVentaOutput = row.querySelector('.vr-venta-output');
                    const precioVentaInput = row.querySelector('.precio-venta-input');

                    const entradas = parseNumber(entradasInput.value);
                    const salidas = parseNumber(salidasInput.value);
                    const valorEntradas = parseNumber(valorEntradasInput.value);
                    const precioVenta = parseNumber(precioVentaInput.value);

                    const valorSalidas = salidas * saldoAnteriorPromedio;
                    const saldoGalones = saldoAnteriorGalones + entradas - salidas;
                    const valorSaldo = saldoAnteriorValor + valorEntradas - valorSalidas;
                    const promedio = saldoGalones > 0 ? valorSaldo / saldoGalones : 0;
                    const vrVenta = salidas * precioVenta;

                    setFormattedValue(saldoOutput, saldoGalones, 3);
                    setFormattedValue(valorSalidasOutput, valorSalidas, 0);
                    setFormattedValue(valorSaldoOutput, valorSaldo, 0);
                    setFormattedValue(promedioOutput, promedio, 0);
                    setFormattedValue(vrVentaOutput, vrVenta, 0);

                    totals.totalSalidas += salidas;
                    totals.ultimoSaldoGalones = saldoGalones;
                    totals.totalValorEntradas += valorEntradas;
                    totals.totalValorSalidas += valorSalidas;
                    totals.ultimoValorSaldo = valorSaldo;
                    totals.totalVrVenta += vrVenta;

                    saldoAnteriorGalones = saldoGalones;
                    saldoAnteriorValor = valorSaldo;
                    saldoAnteriorPromedio = promedio;
                });

                updateTotals(totals);
            }

            function attachRowEvents(row) {
                row.querySelectorAll(
                        '.entradas-galones-input, .salidas-galones-input, .valor-entradas-input, .precio-venta-input'
                        )
                    .forEach(input => {
                        input.addEventListener('input', updateRows);
                        input.addEventListener('change', updateRows);
                        input.addEventListener('blur', function() {
                            const decimals = input.classList.contains('entradas-galones-input') || input
                                .classList.contains('salidas-galones-input') ? 3 : 0;
                            setFormattedValue(input, parseNumber(input.value), decimals);
                        });
                    });
            }

            function createRow(index) {
                const precioDefault = parseNumber(precioVentaDefaultInput.value);
                const tr = document.createElement('tr');
                tr.dataset.index = index;
                tr.innerHTML = `
                    <td>
                        <input type="date" name="rows[${index}][fecha]" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                    </td>
                    <td>
                        <input type="text" name="rows[${index}][planilla_no]" class="form-control form-control-sm text-end" inputmode="numeric">
                    </td>
                    <td>
                        <input type="text" name="rows[${index}][fc_compra_no]" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" name="rows[${index}][entradas_galones]" class="form-control form-control-sm text-end entradas-galones-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" name="rows[${index}][salidas_galones]" class="form-control form-control-sm text-end salidas-galones-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm text-end saldo-galones-output" readonly>
                    </td>
                    <td>
                        <input type="text" name="rows[${index}][valor_entradas]" class="form-control form-control-sm text-end valor-entradas-input" inputmode="decimal">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm text-end valor-salidas-output" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm text-end valor-saldo-output" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm text-end promedio-output" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm text-end vr-venta-output" readonly>
                    </td>
                    <td>
                        <input type="text" name="rows[${index}][precio_venta]" class="form-control form-control-sm text-end precio-venta-input" inputmode="decimal" value="${formatNumber(precioDefault, 0)}">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                    </td>
                `;

                return tr;
            }

            getRows().forEach(attachRowEvents);

            [saldoAnteriorGalonesInput, saldoAnteriorValorInput, saldoAnteriorPromedioInput,
                precioVentaDefaultInput]
            .forEach(input => {
                input.addEventListener('input', updateRows);
                input.addEventListener('change', updateRows);
                input.addEventListener('blur', function() {
                    const decimals = input.id === 'saldo-anterior-galones' ? 3 : 0;
                    setFormattedValue(input, parseNumber(input.value), decimals);
                });
            });

            addBtn.addEventListener('click', function() {
                const row = createRow(nextIndex);
                tbody.appendChild(row);
                attachRowEvents(row);
                nextIndex += 1;
                updateRows();
            });

            tbody.addEventListener('click', function(event) {
                if (!event.target.classList.contains('remove-row')) {
                    return;
                }

                const rows = getRows();
                if (rows.length === 1) {
                    rows[0].querySelectorAll('input').forEach(input => {
                        if (input.type === 'date') {
                            return;
                        }
                        if (!input.hasAttribute('readonly')) {
                            input.value = '';
                        }
                    });
                } else {
                    event.target.closest('tr').remove();
                }

                updateRows();
            });

            updateRows();
        });
    </script>
@endsection
