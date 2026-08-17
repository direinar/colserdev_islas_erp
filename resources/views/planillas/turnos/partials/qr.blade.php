<x-erp-card title="TC, QR, NEQUI Y DAVIPLATA">

    <div class="d-flex justify-content-end mb-2">
        <button type="button" id="add-qr-row" class="btn btn-sm btn-outline-primary">+ Agregar fila</button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 tabla-qr">

            <thead>
                <tr style="background-color:#ccccff;">
                    <th colspan="3" class="text-center fw-bold">
                        TC, QR, NEQUI Y DAVIPLATA
                    </th>
                </tr>
                <tr style="background-color:#ccccff;">
                    <th class="text-center">CONCEPTO</th>
                    <th class="text-center" width="120">VALOR</th>
                    <th class="text-center">ACCIÓN</th>
                </tr>
            </thead>

            <tbody id="qr-body">
                @if (isset($turno) && optional($turno->qrPagos)->count())
                    @foreach ($turno->qrPagos as $i => $q)
                        <tr data-index="{{ $i }}">
                            <td>
                                <input type="text" name="qr_pagos[{{ $i }}][concepto]"
                                    class="form-control form-control-sm border-0 bg-transparent"
                                    value="{{ $q->concepto }}">
                            </td>
                            <td>
                                <input type="text" name="qr_pagos[{{ $i }}][valor]"
                                    class="form-control form-control-sm text-end border-0 bg-transparent qr-valor"
                                    inputmode="decimal" value="{{ number_format($q->valor, 0, ',', '.') }}">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                            </td>
                        </tr>
                    @endforeach
                @else
                    @php
                        $initialQrLabels = ['Datáfono 1'];
                    @endphp

                    @foreach ($initialQrLabels as $index => $label)
                        <tr data-index="{{ $index }}">
                            <td>
                                <input type="text" name="qr_pagos[{{ $index }}][Datáfono]"
                                    class="form-control form-control-sm border-0 bg-transparent"
                                    value="{{ $label }}">
                            </td>
                            <td>
                                <input type="text" name="qr_pagos[{{ $index }}][valor]"
                                    class="form-control form-control-sm text-end border-0 bg-transparent qr-valor"
                                    inputmode="decimal">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>

            <tfoot>
                <tr style="background-color:#d9d9d9; font-weight:bold;">
                    <td class="text-end">TOTAL</td>
                    <td id="total-qr" class="text-end">0</td>
                    <td></td>
                </tr>
            </tfoot>

        </table>
    </div>

</x-erp-card>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('qr-body');
        const addBtn = document.getElementById('add-qr-row');
        const totalCell = document.getElementById('total-qr');

        function parseNumber(value) {
            if (!value) return 0;
            return Number(String(value).replace(/\./g, '').replace(/,/g, '.')) || 0;
        }

        function formatNumber(number) {
            return Number(number).toLocaleString('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function updateTotals() {
            let total = 0;
            tbody.querySelectorAll('tr').forEach(row => {
                total += parseNumber(row.querySelector('.qr-valor')?.value);
            });
            totalCell.textContent = formatNumber(total);
        }

        function attachRowListeners(row) {
            const input = row.querySelector('.qr-valor');
            if (input) {
                input.addEventListener('input', updateTotals);
                input.addEventListener('change', updateTotals);
            }
        }

        function createRow(index) {
            // Contar el número de filas actual para generar el nombre automático
            const rowCount = tbody.querySelectorAll('tr').length + 1;
            const conceptName = `Datáfono ${rowCount}`;

            const tr = document.createElement('tr');
            tr.dataset.index = index;
            tr.innerHTML = `
                <td>
                    <input type="text" name="qr_pagos[${index}][Datáfono]" class="form-control form-control-sm border-0 bg-transparent" value="${conceptName}">
                </td>
                <td>
                    <input type="text" name="qr_pagos[${index}][valor]" class="form-control form-control-sm text-end border-0 bg-transparent qr-valor" inputmode="decimal">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                </td>
            `;
            return tr;
        }

        let nextIndex = 0;
        if (tbody) {
            const existing = Array.from(tbody.querySelectorAll('tr')).map(row => Number(row.dataset.index || -
                1));
            if (existing.length) nextIndex = Math.max(...existing) + 1;
        }

        if (addBtn) {
            addBtn.addEventListener('click', function() {
                const row = createRow(nextIndex++);
                tbody.appendChild(row);
                attachRowListeners(row);
                updateTotals();
            });
        }

        tbody.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-row')) {
                const row = event.target.closest('tr');
                if (row) {
                    row.remove();
                    updateTotals();
                }
            }
        });

        tbody.querySelectorAll('tr').forEach(attachRowListeners);
        updateTotals();
    });
</script>
