<x-erp-card title="INFORMACION DE MEDIOS DE PAGO">

    <div class="d-flex justify-content-end mb-2">
        <button type="button" id="add-medio-pago-row" class="btn btn-sm btn-outline-primary">
            + Agregar fila
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm medios-pago-table">

            <thead style="background-color:#ccccff;">
                <tr>
                    <th colspan="2" class="text-center align-middle">
                        CONSIGNACIONES
                    </th>

                    <th rowspan="2" class="text-center align-middle">
                        DESCUENTOS
                    </th>

                    <th colspan="3" class="text-center align-middle">
                        CARTERA - CRÉDITO DIRECTO
                    </th>

                    <th rowspan="2" class="text-center align-middle">
                        ACCIÓN
                    </th>
                </tr>

                <tr>
                    <th class="text-center">No.</th>
                    <th class="text-center">VALOR</th>

                    <th class="text-center">No. Factura</th>
                    <th class="text-center">CLIENTE</th>
                    <th class="text-center">VALOR</th>
                </tr>
            </thead>

            <tbody id="medios-pago-body">
                @if (isset($turno) && optional($turno->mediosPago)->count())
                    @foreach ($turno->mediosPago as $i => $m)
                        <tr data-index="{{ $i }}">
                            <td>
                                <input type="text" name="medios_pago[{{ $i }}][consignacion_no]"
                                    class="form-control form-control-sm consignacion-no-input"
                                    value="{{ $m->consignacion_no }}">
                            </td>
                            <td>
                                <input type="text" name="medios_pago[{{ $i }}][consignacion_valor]"
                                    class="form-control form-control-sm text-end consignacion-valor-input"
                                    inputmode="decimal"
                                    value="{{ number_format($m->consignacion_valor, 0, '.', ',') }}">
                            </td>
                            <td>
                                <input type="text" name="medios_pago[{{ $i }}][descuento]"
                                    class="form-control form-control-sm text-end descuento-valor-input"
                                    inputmode="decimal" value="{{ number_format($m->descuento, 0, '.', ',') }}">
                            </td>
                            <td>
                                <input type="text" name="medios_pago[{{ $i }}][cartera_factura_no]"
                                    class="form-control form-control-sm cartera-no-input"
                                    value="{{ $m->cartera_factura_no }}">
                            </td>
                            <td>
                                <select name="medios_pago[{{ $i }}][cliente_id]"
                                    class="form-select form-select-sm cartera-cliente-select">
                                    <option value="">Seleccione cliente</option>
                                    @foreach ($customers ?? collect() as $customer)
                                        <option value="{{ $customer->id }}"
                                            @if ($m->cliente_id == $customer->id) selected @endif>{{ $customer->name }} -
                                            {{ $customer->document }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="medios_pago[{{ $i }}][cartera_valor]"
                                    class="form-control form-control-sm text-end cartera-valor-input"
                                    inputmode="decimal" value="{{ number_format($m->cartera_valor, 0, '.', ',') }}">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr data-index="0">

                        <td>
                            <input type="text" name="medios_pago[0][consignacion_no]"
                                class="form-control form-control-sm consignacion-no-input">
                        </td>

                        <td>
                            <input type="text" name="medios_pago[0][consignacion_valor]"
                                class="form-control form-control-sm text-end consignacion-valor-input"
                                inputmode="decimal">
                        </td>

                        <td>
                            <input type="text" name="medios_pago[0][descuento]"
                                class="form-control form-control-sm text-end descuento-valor-input" inputmode="decimal">
                        </td>

                        <td>
                            <input type="text" name="medios_pago[0][cartera_factura_no]"
                                class="form-control form-control-sm cartera-no-input">
                        </td>

                        <td>
                            <select name="medios_pago[0][cliente_id]"
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
                            <input type="text" name="medios_pago[0][cartera_valor]"
                                class="form-control form-control-sm text-end cartera-valor-input" inputmode="decimal">
                        </td>

                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger remove-row">
                                ×
                            </button>
                        </td>

                    </tr>
                @endif
            </tbody>

            <tfoot>
                <tr class="table-secondary fw-bold">
                    <td class="text-end">
                        TOTAL
                    </td>

                    <td id="total-consignaciones" class="text-end">
                        0
                    </td>

                    <td id="total-descuentos" class="text-end">
                        0
                    </td>

                    <td colspan="2" class="text-end">
                        TOTAL CARTERA
                    </td>

                    <td id="total-cartera" class="text-end">
                        0
                    </td>

                    <td></td>
                </tr>
            </tfoot>

        </table>
    </div>

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

        const tbody = document.getElementById('medios-pago-body');
        const addBtn = document.getElementById('add-medio-pago-row');

        const optionsHtml =
            document.getElementById('clientes-options-template').innerHTML;

        let nextIndex = tbody.querySelectorAll('tr').length;

        function parseNumber(value) {

            if (!value) {
                return 0;
            }

            value = value.toString()
                .replace(/\./g, '')
                .replace(/,/g, '.');

            return Number(value) || 0;
        }

        function formatNumber(number) {

            return Number(number).toLocaleString('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function updateTotals() {

            let totalConsignaciones = 0;
            let totalDescuentos = 0;
            let totalCartera = 0;

            document.querySelectorAll('#medios-pago-body tr').forEach(row => {

                totalConsignaciones += parseNumber(
                    row.querySelector('.consignacion-valor-input')?.value
                );

                totalDescuentos += parseNumber(
                    row.querySelector('.descuento-valor-input')?.value
                );

                totalCartera += parseNumber(
                    row.querySelector('.cartera-valor-input')?.value
                );
            });

            document.getElementById('total-consignaciones').textContent =
                formatNumber(totalConsignaciones);

            document.getElementById('total-descuentos').textContent =
                formatNumber(totalDescuentos);

            document.getElementById('total-cartera').textContent =
                formatNumber(totalCartera);
        }

        function attachEvents(row) {

            row.querySelectorAll(
                '.consignacion-valor-input, .descuento-valor-input, .cartera-valor-input'
            ).forEach(input => {

                input.addEventListener('input', updateTotals);
                input.addEventListener('change', updateTotals);
            });
        }

        function createRow(index) {

            const tr = document.createElement('tr');

            tr.dataset.index = index;

            tr.innerHTML = `
            <td>
                <input type="text"
                    name="medios_pago[${index}][consignacion_no]"
                    class="form-control form-control-sm consignacion-no-input">
            </td>

            <td>
                <input type="text"
                    name="medios_pago[${index}][consignacion_valor]"
                    class="form-control form-control-sm text-end consignacion-valor-input"
                    inputmode="decimal">
            </td>

            <td>
                <input type="text"
                    name="medios_pago[${index}][descuento]"
                    class="form-control form-control-sm text-end descuento-valor-input"
                    inputmode="decimal">
            </td>

            <td>
                <input type="text"
                    name="medios_pago[${index}][cartera_factura_no]"
                    class="form-control form-control-sm cartera-no-input">
            </td>

            <td>
                <select
                    name="medios_pago[${index}][cliente_id]"
                    class="form-select form-select-sm cartera-cliente-select">
                    ${optionsHtml}
                </select>
            </td>

            <td>
                <input type="text"
                    name="medios_pago[${index}][cartera_valor]"
                    class="form-control form-control-sm text-end cartera-valor-input"
                    inputmode="decimal">
            </td>

            <td class="text-center">
                <button type="button"
                        class="btn btn-sm btn-danger remove-row">
                    ×
                </button>
            </td>
        `;

            return tr;
        }

        attachEvents(document.querySelector('#medios-pago-body tr'));

        addBtn.addEventListener('click', function() {

            const row = createRow(nextIndex);

            tbody.appendChild(row);

            attachEvents(row);

            nextIndex++;

            updateTotals();
        });

        document.addEventListener('click', function(e) {

            if (e.target.classList.contains('remove-row')) {

                const rows = tbody.querySelectorAll('tr');

                if (rows.length > 1) {

                    e.target.closest('tr').remove();

                    updateTotals();
                }
            }
        });

        updateTotals();
    });
</script>
