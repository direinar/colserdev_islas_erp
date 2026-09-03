<x-erp-card>

    <div class="medio-pago-toolbar">
        <div class="medio-pago-title">PUNTOS REDIMIDOS</div>
        <button type="button" id="add-transferencia-row" class="btn btn-sm btn-outline-primary medio-pago-add-btn">
            + AGREGAR FILA
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 tabla-transferencias">

            <thead>
                <tr style="background-color:#ccccff;">
                    <th class="text-end">VALOR</th>
                    <th class="text-center">ACCIÓN</th>
                </tr>
            </thead>

            <tbody id="transferencias-body">
                @if (isset($turno) && optional($turno->transferencias)->count())
                    @foreach ($turno->transferencias as $i => $t)
                        <tr data-index="{{ $i }}">
                            <td>
                                <input type="text" name="transferencias[{{ $i }}][puntos]"
                                    class="form-control form-control-sm text-end border-0 bg-transparent puntos-valor"
                                    inputmode="decimal"
                                    value="{{ number_format($t->puntos_redimidos ?? 0, 0, ',', '.') }}">
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
                                <input type="text" name="transferencias[{{ $i }}][puntos]"
                                    class="form-control form-control-sm text-end border-0 bg-transparent puntos-valor"
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
                <tr style="background-color:#bfbfbf; font-weight:bold;">
                    <td class="text-end">TOTAL</td>
                    <td id="total-puntos" class="text-end">0</td>
                </tr>
            </tfoot>

        </table>
    </div>

</x-erp-card>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('transferencias-body');
        const addBtn = document.getElementById('add-transferencia-row');
        const totalPuntosCell = document.getElementById('total-puntos');

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
            let totalPuntos = 0;

            tbody.querySelectorAll('tr').forEach(row => {
                totalPuntos += parseNumber(row.querySelector('.puntos-valor')?.value);
            });

            totalPuntosCell.textContent = formatNumber(totalPuntos);
        }

        function attachRowListeners(row) {
            row.querySelectorAll('.puntos-valor').forEach(input => {
                input.addEventListener('input', updateTotals);
                input.addEventListener('change', updateTotals);
            });
        }

        function createRow(index) {
            const tr = document.createElement('tr');
            tr.dataset.index = index;
            tr.innerHTML = `
                <td>
                    <input type="text" name="transferencias[${index}][puntos]" class="form-control form-control-sm text-end border-0 bg-transparent puntos-valor" inputmode="decimal">
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
