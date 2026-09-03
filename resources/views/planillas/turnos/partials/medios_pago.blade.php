<x-erp-card title="INFORMACION DE MEDIOS DE PAGO">

    <div class="medio-pago-stack">
        <div class="medio-pago-section">
            <div class="medio-pago-toolbar">
                <div class="medio-pago-title">CONSIGNACIONES</div>
                <button type="button" id="add-consignacion-row" class="btn btn-sm btn-outline-primary medio-pago-add-btn">
                    + AGREGAR FILA
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 medio-pago-table">
                    <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th class="text-center">VALOR</th>
                            <th class="text-center">ACCIÓN</th>
                        </tr>
                    </thead>
                    <tbody id="consignaciones-body">
                        @if (isset($turno) && optional($turno->consignaciones)->count())
                            @foreach ($turno->consignaciones as $i => $m)
                                <tr data-index="{{ $i }}">
                                    <td>
                                        <input type="text"
                                            name="consignaciones[{{ $i }}][consignacion_no]"
                                            class="form-control form-control-sm consignacion-no-input"
                                            value="{{ $m->consignacion_no }}">
                                    </td>
                                    <td>
                                        <input type="text"
                                            name="consignaciones[{{ $i }}][consignacion_valor]"
                                            class="form-control form-control-sm text-end consignacion-valor-input"
                                            inputmode="decimal" value="{{ number_format($m->valor, 0, ',', '.') }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr data-index="0">
                                <td>
                                    <input type="text" name="consignaciones[0][consignacion_no]"
                                        class="form-control form-control-sm consignacion-no-input">
                                </td>
                                <td>
                                    <input type="text" name="consignaciones[0][consignacion_valor]"
                                        class="form-control form-control-sm text-end consignacion-valor-input"
                                        inputmode="decimal">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="medio-pago-total-row">
                            <td class="text-end">TOTAL</td>
                            <td id="total-consignaciones" class="text-end">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="medio-pago-section">
            <div class="medio-pago-toolbar">
                <div class="medio-pago-title">DESCUENTOS</div>
                <button type="button" id="add-descuento-row" class="btn btn-sm btn-outline-primary medio-pago-add-btn">
                    + AGREGAR FILA
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 medio-pago-table">
                    <thead>
                        <tr>
                            <th class="text-center">VALOR</th>
                            <th class="text-center">ACCIÓN</th>
                        </tr>
                    </thead>
                    <tbody id="descuentos-body">
                        @if (isset($turno) && optional($turno->descuentos)->count())
                            @foreach ($turno->descuentos as $i => $m)
                                <tr data-index="{{ $i }}">
                                    <td>
                                        <input type="text" name="descuentos[{{ $i }}][descuento]"
                                            class="form-control form-control-sm text-end descuento-valor-input"
                                            inputmode="decimal" value="{{ number_format($m->valor, 0, ',', '.') }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr data-index="0">
                                <td>
                                    <input type="text" name="descuentos[0][descuento]"
                                        class="form-control form-control-sm text-end descuento-valor-input"
                                        inputmode="decimal">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="medio-pago-total-row">
                            <td class="text-end">TOTAL</td>
                            <td id="total-descuentos" class="text-end">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="medio-pago-section">
            <div class="medio-pago-toolbar">
                <div class="medio-pago-title">CARTERA - CRÉDITO DIRECTO</div>
                <button type="button" id="add-cartera-row" class="btn btn-sm btn-outline-primary medio-pago-add-btn">
                    + AGREGAR FILA
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 medio-pago-table">
                    <thead>
                        <tr>
                            <th class="text-center">No. Factura</th>
                            <th class="text-center">CLIENTE</th>
                            <th class="text-center">VALOR</th>
                            <th class="text-center">ACCIÓN</th>
                        </tr>
                    </thead>
                    <tbody id="cartera-body">
                        @if (isset($turno) && optional($turno->cartera)->count())
                            @foreach ($turno->cartera as $i => $m)
                                <tr data-index="{{ $i }}">
                                    <td>
                                        <input type="text" name="cartera[{{ $i }}][cartera_factura_no]"
                                            class="form-control form-control-sm cartera-no-input"
                                            value="{{ $m->factura_no }}">
                                    </td>
                                    <td>
                                        <select name="cartera[{{ $i }}][cliente_id]"
                                            class="form-select form-select-sm cartera-cliente-select">
                                            <option value="">Seleccione cliente</option>
                                            @foreach ($customers ?? collect() as $customer)
                                                <option value="{{ $customer->id }}"
                                                    @if ($m->cliente_id == $customer->id) selected @endif>
                                                    {{ $customer->name }} - {{ $customer->document }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="cartera[{{ $i }}][cartera_valor]"
                                            class="form-control form-control-sm text-end cartera-valor-input"
                                            inputmode="decimal" value="{{ number_format($m->valor, 0, ',', '.') }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr data-index="0">
                                <td>
                                    <input type="text" name="cartera[0][cartera_factura_no]"
                                        class="form-control form-control-sm cartera-no-input">
                                </td>
                                <td>
                                    <select name="cartera[0][cliente_id]"
                                        class="form-select form-select-sm cartera-cliente-select">
                                        <option value="">Seleccione cliente</option>
                                        @foreach ($customers ?? collect() as $customer)
                                            <option value="{{ $customer->id }}">
                                                {{ $customer->name }} - {{ $customer->document }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="cartera[0][cartera_valor]"
                                        class="form-control form-control-sm text-end cartera-valor-input"
                                        inputmode="decimal">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="medio-pago-total-row">
                            <td colspan="2" class="text-end">TOTAL CARTERA</td>
                            <td id="total-cartera" class="text-end">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="medio-pago-section qr-section">
            @include('planillas.turnos.partials.qr')
        </div>

    </div>

    <style>
        .medio-pago-stack {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .medio-pago-section {
            border: 1px solid #dfe1ea;
            background: #f5f5f6;
            overflow: hidden;
        }

        .medio-pago-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            background: #d9d6f9;
            border-bottom: 1px solid #d0d0d0;
            min-height: 48px;
        }

        .medio-pago-title {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #1d1d1d;
        }

        .medio-pago-add-btn {
            background: #f3f3f3 !important;
            color: #d93a2f !important;
            border-color: #d9d9d9 !important;
            font-weight: 700 !important;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 7px 14px !important;
        }

        .medio-pago-add-btn:hover {
            background: #fff !important;
            border-color: #cfcfcf !important;
        }

        .medio-pago-table thead th {
            background: #d9d6f9 !important;
            color: #1d1d1d !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            padding: 10px 8px !important;
            border-color: #d0d0d0 !important;
        }

        .medio-pago-table tbody td,
        .medio-pago-table tfoot td {
            padding: 10px 8px !important;
            background: #fff;
            border-color: #d9d9d9 !important;
        }

        .medio-pago-total-row td {
            background: #e8e8e8 !important;
            color: #1d1d1d !important;
            font-weight: 700;
        }
    </style>

    <select id="clientes-options-template" class="d-none">
        <option value="">Seleccione cliente</option>
        @foreach ($customers ?? collect() as $customer)
            <option value="{{ $customer->id }}">
                {{ $customer->name }} - {{ $customer->document }}
            </option>
        @endforeach
    </select>

</x-erp-card>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ensureNumber = value => {
            const n = window.MoneyFormat.parseMoney(value);
            return isNaN(n) ? 0 : n;
        };

        const formatNumber = number => window.MoneyFormat.formatMoney(number);

        const sharedIndex = {
            value: Math.max(
                Array.from(document.querySelectorAll('#consignaciones-body tr')).reduce((max, row) =>
                    Math.max(max, Number(row.dataset.index || 0)), -1),
                Array.from(document.querySelectorAll('#descuentos-body tr')).reduce((max, row) => Math
                    .max(max, Number(row.dataset.index || 0)), -1),
                Array.from(document.querySelectorAll('#cartera-body tr')).reduce((max, row) => Math.max(
                    max, Number(row.dataset.index || 0)), -1)
            ) + 1
        };

        function setupTable({
            bodyId,
            totalId,
            valueSelector,
            addButtonId,
            rowFactory
        }) {
            const body = document.getElementById(bodyId);
            const addButton = document.getElementById(addButtonId);
            const totalCell = document.getElementById(totalId);

            if (!body || !totalCell) return;

            const recalc = () => {
                let total = 0;
                body.querySelectorAll('tr').forEach(row => {
                    const inputs = row.querySelectorAll(valueSelector);
                    if (!inputs.length) return;
                    total += Array.from(inputs).reduce((sum, input) => sum + ensureNumber(input
                        .value), 0);
                });
                totalCell.textContent = formatNumber(total);
            };

            const bindRow = row => {
                row.querySelectorAll(valueSelector).forEach(input => {
                    input.addEventListener('input', recalc);
                    input.addEventListener('change', recalc);
                });
            };

            if (addButton) {
                addButton.addEventListener('click', () => {
                    const index = sharedIndex.value++;
                    const row = rowFactory(index);
                    body.appendChild(row);
                    bindRow(row);
                    recalc();
                });
            }

            body.addEventListener('click', event => {
                const button = event.target.closest('.remove-row');
                if (!button) return;
                const row = button.closest('tr');
                if (row) {
                    row.remove();
                    recalc();
                }
            });

            body.querySelectorAll('tr').forEach(bindRow);
            recalc();
        }

        const clientesOptions = document.getElementById('clientes-options-template')?.innerHTML || '';

        setupTable({
            bodyId: 'consignaciones-body',
            totalId: 'total-consignaciones',
            valueSelector: '.consignacion-valor-input',
            addButtonId: 'add-consignacion-row',
            rowFactory: index => {
                const tr = document.createElement('tr');
                tr.dataset.index = index;
                tr.innerHTML = `
                    <td><input type="text" name="consignaciones[${index}][consignacion_no]" class="form-control form-control-sm consignacion-no-input"></td>
                    <td><input type="text" name="consignaciones[${index}][consignacion_valor]" class="form-control form-control-sm text-end consignacion-valor-input" inputmode="decimal"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">×</button></td>
                `;
                return tr;
            }
        });

        setupTable({
            bodyId: 'descuentos-body',
            totalId: 'total-descuentos',
            valueSelector: '.descuento-valor-input',
            addButtonId: 'add-descuento-row',
            rowFactory: index => {
                const tr = document.createElement('tr');
                tr.dataset.index = index;
                tr.innerHTML = `
                    <td><input type="text" name="descuentos[${index}][descuento]" class="form-control form-control-sm text-end descuento-valor-input" inputmode="decimal"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">×</button></td>
                `;
                return tr;
            }
        });

        setupTable({
            bodyId: 'cartera-body',
            totalId: 'total-cartera',
            valueSelector: '.cartera-valor-input',
            addButtonId: 'add-cartera-row',
            rowFactory: index => {
                const tr = document.createElement('tr');
                tr.dataset.index = index;
                tr.innerHTML = `
                    <td><input type="text" name="cartera[${index}][cartera_factura_no]" class="form-control form-control-sm cartera-no-input"></td>
                    <td>
                        <select name="cartera[${index}][cliente_id]" class="form-select form-select-sm cartera-cliente-select">
                            <option value="">Seleccione cliente</option>
                            ${clientesOptions}
                        </select>
                    </td>
                    <td><input type="text" name="cartera[${index}][cartera_valor]" class="form-control form-control-sm text-end cartera-valor-input" inputmode="decimal"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">×</button></td>
                `;
                return tr;
            }
        });
    });
</script>
