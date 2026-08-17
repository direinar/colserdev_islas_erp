<x-erp-card title="RECAUDOS, ANTICIPOS Y PREPAGOS POR ISLAS">

    <div class="d-flex justify-content-end mb-2">
        <button type="button" id="add-recaudo-row" class="btn btn-sm btn-outline-primary">+ Agregar fila</button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 tabla-recaudos">

            <thead>
                <tr style="background-color:#ccccff;">
                    <th colspan="3" class="text-center fw-bold">
                        RECAUDOS, ANTICIPOS Y PREPAGOS POR ISLAS
                    </th>
                </tr>

                <tr style="background-color:#ccccff;">
                    <th class="text-center">CLIENTE</th>
                    <th class="text-center" width="120">VALOR</th>
                    <th class="text-center">ACCIÓN</th>
                </tr>
            </thead>

            <tbody id="recaudos-body">
                @if (isset($turno) && optional($turno->recaudos)->count())
                    @foreach ($turno->recaudos as $i => $r)
                        <tr data-index="{{ $i }}">
                            <td>
                                <select name="recaudos[{{ $i }}][cliente_id]"
                                    class="form-select form-select-sm border-0 bg-transparent recaudos-cliente">
                                    <option value="">Seleccione cliente</option>
                                    @foreach ($customers ?? collect() as $customer)
                                        <option value="{{ $customer->id }}"
                                            @if ($r->cliente_id == $customer->id) selected @endif>{{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <input type="text" name="recaudos[{{ $i }}][valor]"
                                    class="form-control form-control-sm text-end border-0 bg-transparent recaudo-valor"
                                    inputmode="decimal" value="{{ number_format($r->valor, 0, ',', '.') }}">
                            </td>

                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                            </td>
                        </tr>
                    @endforeach
                @else
                    @for ($i = 0; $i < 1; $i++)
                        <tr data-index="{{ $i }}">
                            <td>
                                <select name="recaudos[{{ $i }}][cliente_id]"
                                    class="form-select form-select-sm border-0 bg-transparent recaudos-cliente">
                                    <option value="">Seleccione cliente</option>
                                    @foreach ($customers ?? collect() as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <input type="text" name="recaudos[{{ $i }}][valor]"
                                    class="form-control form-control-sm text-end border-0 bg-transparent recaudo-valor"
                                    inputmode="decimal">
                            </td>

                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                            </td>
                        </tr>
                    @endfor
                @endif
            </tbody>

            <tfoot>
                <tr style="background-color:#d9d9d9; font-weight:bold;">
                    <td class="text-end">TOTAL</td>
                    <td id="total-recaudos" class="text-end">0</td>
                    <td></td>
                </tr>
            </tfoot>

        </table>
    </div>

</x-erp-card>

<select id="recaudos-customers-template" class="d-none">
    <option value="">Seleccione cliente</option>
    @foreach ($customers ?? collect() as $customer)
        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
    @endforeach
</select>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('recaudos-body');
        const addBtn = document.getElementById('add-recaudo-row');
        const totalCell = document.getElementById('total-recaudos');
        const customersTemplate = document.getElementById('recaudos-customers-template');
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
            tbody.querySelectorAll('.recaudo-valor').forEach(input => {
                total += parseNumber(input.value);
            });
            totalCell.textContent = formatNumber(total);
        }

        function attachRowListeners(row) {
            const input = row.querySelector('.recaudo-valor');
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
                    <select name="recaudos[${index}][cliente_id]" class="form-select form-select-sm border-0 bg-transparent recaudos-cliente">
                        ${customersHtml}
                    </select>
                </td>
                <td>
                    <input type="text" name="recaudos[${index}][valor]" class="form-control form-control-sm text-end border-0 bg-transparent recaudo-valor" inputmode="decimal">
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
