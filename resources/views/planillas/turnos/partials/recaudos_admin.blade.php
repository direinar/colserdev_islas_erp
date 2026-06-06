<x-erp-card title="RECAUDOS POR ADMINISTRACIÓN">

    <div class="d-flex justify-content-end mb-2">
        <button type="button" id="add-recaudo-admin-row" class="btn btn-sm btn-outline-primary">+ Agregar fila</button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 tabla-recaudos-admin">

            <thead>
                <tr style="background-color:#ccccff;">
                    <th class="text-center fw-bold">BANCO/CAJA</th>
                    <th class="text-center fw-bold">RESPONSABLE</th>
                    <th class="text-center fw-bold" width="140">VALOR</th>
                    <th class="text-center">ACCIÓN</th>
                </tr>
            </thead>

            <tbody id="recaudos-admin-body">
                <tr data-index="0">
                    <td>
                        <input type="text" name="recaudos_admin[0][banco]"
                            class="form-control form-control-sm border-0 bg-transparent" placeholder="Ej: Bancolombia">
                    </td>
                    <td>
                        <select name="recaudos_admin[0][responsable_id]"
                            class="form-select form-select-sm border-0 bg-transparent recaudos-admin-responsable">
                            <option value="">Seleccione responsable</option>
                            @foreach ($customers ?? collect() as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="recaudos_admin[0][valor]"
                            class="form-control form-control-sm text-end border-0 bg-transparent recaudo-admin-valor"
                            inputmode="decimal">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                    </td>
                </tr>
            </tbody>

            <tfoot>
                <tr style="background-color:#d9d9d9; font-weight:bold;">
                    <td colspan="2" class="text-end">TOTAL</td>
                    <td id="total-recaudos-admin" class="text-end">0</td>
                    <td></td>
                </tr>
            </tfoot>

        </table>
    </div>

</x-erp-card>

<select id="recaudos-admin-customers-template" class="d-none">
    <option value="">Seleccione responsable</option>
    @foreach ($customers ?? collect() as $customer)
        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
    @endforeach
</select>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('recaudos-admin-body');
        const addBtn = document.getElementById('add-recaudo-admin-row');
        const totalCell = document.getElementById('total-recaudos-admin');
        const customersTemplate = document.getElementById('recaudos-admin-customers-template');
        const customersHtml = customersTemplate ? customersTemplate.innerHTML : '';

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
            tbody.querySelectorAll('.recaudo-admin-valor').forEach(input => {
                total += parseNumber(input.value);
            });
            totalCell.textContent = formatNumber(total);
        }

        function attachRowListeners(row) {
            const input = row.querySelector('.recaudo-admin-valor');
            if (input) {
                input.addEventListener('input', updateTotals);
                input.addEventListener('change', updateTotals);
            }
        }

        function createRow(index) {
            const tr = document.createElement('tr');
            tr.dataset.index = index;
            tr.innerHTML = `
                <td>
                    <input type="text" name="recaudos_admin[${index}][banco]" class="form-control form-control-sm border-0 bg-transparent" placeholder="Ej: Bancolombia">
                </td>
                <td>
                    <select name="recaudos_admin[${index}][responsable_id]" class="form-select form-select-sm border-0 bg-transparent recaudos-admin-responsable">
                        ${customersHtml}
                    </select>
                </td>
                <td>
                    <input type="text" name="recaudos_admin[${index}][valor]" class="form-control form-control-sm text-end border-0 bg-transparent recaudo-admin-valor" inputmode="decimal">
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
