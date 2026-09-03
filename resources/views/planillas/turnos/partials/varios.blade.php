<x-erp-card>

    <div class="medio-pago-toolbar">
        <div class="medio-pago-title">VARIOS</div>
        <button type="button" id="add-varios-row" class="btn btn-sm btn-outline-primary medio-pago-add-btn">
            + AGREGAR FILA
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 tabla-varios">

            <thead>
                <tr style="background-color:#ccccff;">
                    <th class="text-center">CONCEPTO</th>
                    <th class="text-center" width="120">VALOR</th>
                    <th class="text-center">ACCIÓN</th>
                </tr>
            </thead>

            <tbody id="varios-body">
                @if (isset($turno) && optional($turno->varios)->count())
                    @foreach ($turno->varios as $i => $v)
                        <tr data-index="{{ $i }}">
                            <td>
                                <input type="text" name="varios[{{ $i }}][concepto]"
                                    class="form-control form-control-sm border-0 bg-transparent"
                                    value="{{ $v->concepto }}">
                            </td>
                            <td>
                                <input type="text" name="varios[{{ $i }}][valor]"
                                    class="form-control form-control-sm text-end border-0 bg-transparent varios-valor"
                                    inputmode="decimal" value="{{ number_format($v->valor, 0, ',', '.') }}">
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
                                <input type="text" name="varios[{{ $i }}][concepto]"
                                    class="form-control form-control-sm border-0 bg-transparent">
                            </td>
                            <td>
                                <input type="text" name="varios[{{ $i }}][valor]"
                                    class="form-control form-control-sm text-end border-0 bg-transparent varios-valor"
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
                    <td id="total-varios" class="text-end">0</td>
                    <td></td>
                </tr>
            </tfoot>

        </table>
    </div>

</x-erp-card>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('varios-body');
        const addBtn = document.getElementById('add-varios-row');
        const totalCell = document.getElementById('total-varios');

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
            tbody.querySelectorAll('.varios-valor').forEach(input => {
                total += parseNumber(input.value);
            });
            totalCell.textContent = formatNumber(total);
        }

        function attachRowListeners(row) {
            const input = row.querySelector('.varios-valor');
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
                    <input type="text" name="varios[${index}][concepto]" class="form-control form-control-sm border-0 bg-transparent" placeholder="Concepto">
                </td>
                <td>
                    <input type="text" name="varios[${index}][valor]" class="form-control form-control-sm text-end border-0 bg-transparent varios-valor" inputmode="decimal" placeholder="0">
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
