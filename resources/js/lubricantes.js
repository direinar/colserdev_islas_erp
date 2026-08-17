document.addEventListener('DOMContentLoaded', function () {
    function formatNumber(value, showZero = false) {
        if (value === 0) {
            return showZero ? '0' : '';
        }
        return value.toLocaleString('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    function parseNumber(value) {
        if (!value) return 0;
        return Number(String(value).replace(/\./g, '').replace(/,/g, '.')) || 0;
    }

    function updateRowValues(row) {
        const cantidad = Number(row.querySelector('.cantidad-input')?.value || 0);
        const select = row.querySelector('.lubricantes-producto-select');
        const option = select?.selectedOptions?.[0];
        const unitPrice = Number(option?.dataset.salePrice || 0);
        const unitIva = Number(option?.dataset.iva || 0);

        const valorTotalSinIva = cantidad * unitPrice;
        const ivaTotal = cantidad * unitIva;
        const total = valorTotalSinIva + ivaTotal;

        const valorInput = row.querySelector('.valor-sin-iva-input');
        const ivaInput = row.querySelector('.iva-input');
        const totalInput = row.querySelector('.total-input');

        if (valorInput) valorInput.value = formatNumber(valorTotalSinIva, true);
        if (ivaInput) ivaInput.value = formatNumber(ivaTotal, true);
        if (totalInput) totalInput.value = formatNumber(total, true);
    }

    function updateTotalsFooter() {
        const rows = Array.from(document.querySelectorAll('.lubricantes-table tbody tr'));
        let totalValor = 0;
        let totalIva = 0;
        let totalGeneral = 0;

        rows.forEach(function (row) {
            const valor = parseNumber(row.querySelector('.valor-sin-iva-input')?.value || 0);
            const iva = parseNumber(row.querySelector('.iva-input')?.value || 0);
            const total = parseNumber(row.querySelector('.total-input')?.value || 0);

            totalValor += valor;
            totalIva += iva;
            totalGeneral += total;
        });

        const totalValorCell = document.getElementById('total-valor-sin-iva');
        const totalIvaCell = document.getElementById('total-iva');
        const totalTotalCell = document.getElementById('total-total');

        if (totalValorCell) totalValorCell.textContent = formatNumber(totalValor, true);
        if (totalIvaCell) totalIvaCell.textContent = formatNumber(totalIva, true);
        if (totalTotalCell) totalTotalCell.textContent = formatNumber(totalGeneral, true);
    }

    function setSelectEnabled(row, enabled) {
        const select = row.querySelector('.lubricantes-producto-select');
        if (!select) return;
        if (enabled) {
            select.removeAttribute('disabled');
        } else {
            select.setAttribute('disabled', 'disabled');
            select.value = '';
            const saleIn = row.querySelector('.valor-sin-iva-input');
            const ivaIn = row.querySelector('.iva-input');
            const totalIn = row.querySelector('.total-input');
            if (saleIn) saleIn.value = '0';
            if (ivaIn) ivaIn.value = '0';
            if (totalIn) totalIn.value = '0';
        }
    }

    function attachRowListeners(row) {
        const cantidad = row.querySelector('.cantidad-input');
        const select = row.querySelector('.lubricantes-producto-select');

        if (cantidad) {
            const update = function () {
                const enabled = Number(this.value) > 0;
                setSelectEnabled(row, enabled);
                updateRowValues(row);
                updateTotalsFooter();
            };

            cantidad.addEventListener('input', update);
            cantidad.addEventListener('change', update);
            cantidad.addEventListener('keyup', update);
        }

        if (select) {
            select.addEventListener('change', function () {
                updateRowValues(row);
                updateTotalsFooter();
            });
        }
    }

    // Inicializar listeners en filas existentes
    const tbody = document.querySelector('.lubricantes-table tbody');
    if (tbody) {
        tbody.querySelectorAll('tr').forEach(function (row) {
            attachRowListeners(row);
            const qty = Number(row.querySelector('.cantidad-input')?.value || 0);
            setSelectEnabled(row, qty > 0);
        });
        updateTotalsFooter();
    }

    // preparar plantilla de opciones para nuevas filas
    const optionsTemplate = document.getElementById('lubricantes-options-template');
    const optionsHtml = optionsTemplate ? optionsTemplate.innerHTML : '';

    // calcular siguiente índice
    let nextIndex = 0;
    if (tbody) {
        const existing = Array.from(tbody.querySelectorAll('tr')).map(r => Number(r.dataset.index || -1));
        if (existing.length) nextIndex = Math.max(...existing) + 1;
    }

    function createRow(index) {
        const tr = document.createElement('tr');
        tr.dataset.index = index;
        tr.innerHTML = `
            <td>
                <input type="number" name="urea_lubricantes[${index}][cantidad]" min="0" step="1" class="form-control form-control-sm cantidad-input" />
            </td>
            <td>
                <select name="urea_lubricantes[${index}][producto]" class="form-select form-select-sm lubricantes-producto-select" disabled>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="text" name="urea_lubricantes[${index}][valor_sin_iva]" class="form-control form-control-sm valor-sin-iva-input" readonly />
            </td>
            <td>
                <input type="text" name="urea_lubricantes[${index}][iva]" class="form-control form-control-sm iva-input" readonly />
            </td>
            <td>
                <input type="text" name="urea_lubricantes[${index}][total]" class="form-control form-control-sm total-input" readonly />
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
            </td>
        `;
        return tr;
    }

    // botón para agregar fila
    const addBtn = document.getElementById('add-lubricante-row');
    if (addBtn && tbody) {
        addBtn.addEventListener('click', function () {
            const newRow = createRow(nextIndex);
            tbody.appendChild(newRow);
            attachRowListeners(newRow);
            nextIndex++;
            updateTotalsFooter();
            newRow.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        });
    }

    if (tbody) {
        tbody.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-row')) {
                const row = event.target.closest('tr');
                if (row) {
                    row.remove();
                    updateTotalsFooter();
                }
            }
        });
    }
});
