<x-erp-card title="RESUMEN DE LO RECIBIDO EN ESTE TURNO">

    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 tabla-resumen-recibido" style="font-size: 0.95rem;">

            <tbody>
                <tr>
                    <td class="fw-bold" style="background-color: #ffffcc;">CONSIGNACIONES</td>
                    <td style="text-align: right; background-color: #ffffcc;">
                        <span id="resumen-consignaciones">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">TC, QR, NEQUI, DAVIPLATA</td>
                    <td style="text-align: right;">
                        <span id="resumen-qr">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">PUNTOS REDIMIDOS</td>
                    <td style="text-align: right;">
                        <span id="resumen-puntos">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">GASOLINA EDS</td>
                    <td style="text-align: right;">
                        <span id="resumen-gasolina">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">TRANSFERENCIAS BANCOLOMBIA</td>
                    <td style="text-align: right;">
                        <span id="resumen-transferencias">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">DESCUENTOS</td>
                    <td style="text-align: right;">
                        <span id="resumen-descuentos">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">CARTERA - crédito directo</td>
                    <td style="text-align: right;">
                        <span id="resumen-cartera">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">VARIOS</td>
                    <td style="text-align: right;">
                        <span id="resumen-varios">0</span>
                    </td>
                </tr>

                <tr style="height: 10px;">
                    <td colspan="2"></td>
                </tr>

                <tr>
                    <td class="fw-bold">SOBRANTE</td>
                    <td style="text-align: right;">
                        <span id="resumen-sobrante">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">FALTANTE</td>
                    <td style="text-align: right;">
                        <span id="resumen-faltante">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">SUBTOTAL INGRESOS</td>
                    <td style="text-align: right; font-weight: bold;">
                        <span id="resumen-subtotal">0</span>
                    </td>
                </tr>

                <tr>
                    <td class="fw-bold">RECAUDOS Y ANTICIPOS</td>
                    <td style="text-align: right;">
                        <span id="resumen-recaudos">0</span>
                    </td>
                </tr>

                <tr style="border-top: 3px solid #333;">
                    <td class="fw-bold" style="font-size: 1.1rem;">TOTAL RECIBIDO EN ESTE TURNO</td>
                    <td style="text-align: right; font-weight: bold; font-size: 1.1rem; color: #2563eb;">
                        <span id="resumen-total">0</span>
                    </td>
                </tr>
            </tbody>

        </table>
    </div>

</x-erp-card>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function formatNumber(number) {
            return Number(number).toLocaleString('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function parseNumber(value) {
            if (!value) return 0;
            return Number(String(value).replace(/\./g, '').replace(/,/g, '.')) || 0;
        }

        function updateResumen() {
            // Sumar desde medios_pago (consignaciones)
            let consignaciones = 0;
            document.querySelectorAll('.consignacion-valor-input').forEach(input => {
                consignaciones += parseNumber(input.value);
            });

            // Sumar desde QR
            let qrTotal = 0;
            document.querySelectorAll('.tabla-qr .qr-valor').forEach(input => {
                qrTotal += parseNumber(input.value);
            });

            // Sumar puntos redimidos desde transferencias
            let puntos = 0;
            document.querySelectorAll('.tabla-transferencias .puntos-valor').forEach(input => {
                puntos += parseNumber(input.value);
            });

            // Sumar gasolina eds
            let gasolina = 0;
            document.querySelectorAll('.tabla-gasolina-eds .puntos-valor').forEach(input => {
                gasolina += parseNumber(input.value);
            });

            // Sumar transferencias
            let transferencias = 0;
            document.querySelectorAll('.tabla-transferencias .transferencia-valor').forEach(input => {
                transferencias += parseNumber(input.value);
            });

            // Sumar descuentos
            let descuentos = 0;
            document.querySelectorAll('.descuento-valor-input').forEach(input => {
                descuentos += parseNumber(input.value);
            });

            // Sumar cartera
            let cartera = 0;
            document.querySelectorAll('.cartera-valor-input').forEach(input => {
                cartera += parseNumber(input.value);
            });

            // Sumar varios
            let varios = 0;
            document.querySelectorAll('.tabla-varios .varios-valor').forEach(input => {
                varios += parseNumber(input.value);
            });

            // Sumar sobrante
            let sobrante = 0;
            document.querySelectorAll('.sobrante-valor-input').forEach(input => {
                sobrante += parseNumber(input.value);
            });

            // Sumar faltante
            let faltante = 0;
            document.querySelectorAll('.faltante-valor-input').forEach(input => {
                faltante += parseNumber(input.value);
            });

            // Sumar recaudos
            let recaudos = 0;
            document.querySelectorAll('.tabla-recaudos .recaudo-valor').forEach(input => {
                recaudos += parseNumber(input.value);
            });

            // Actualizar valores en el resumen
            document.getElementById('resumen-consignaciones').textContent = formatNumber(consignaciones);
            document.getElementById('resumen-qr').textContent = formatNumber(qrTotal);
            document.getElementById('resumen-puntos').textContent = formatNumber(puntos);
            document.getElementById('resumen-gasolina').textContent = formatNumber(gasolina);
            document.getElementById('resumen-transferencias').textContent = formatNumber(transferencias);
            document.getElementById('resumen-descuentos').textContent = formatNumber(descuentos);
            document.getElementById('resumen-cartera').textContent = formatNumber(cartera);
            document.getElementById('resumen-varios').textContent = formatNumber(varios);
            document.getElementById('resumen-sobrante').textContent = formatNumber(sobrante);
            document.getElementById('resumen-faltante').textContent = formatNumber(faltante);
            document.getElementById('resumen-recaudos').textContent = formatNumber(recaudos);

            // Calcular subtotal
            let subtotal = consignaciones + qrTotal + puntos + gasolina + transferencias + descuentos +
                cartera + varios + sobrante + faltante;
            document.getElementById('resumen-subtotal').textContent = formatNumber(subtotal);

            // Total = subtotal + recaudos
            let total = subtotal - recaudos;
            document.getElementById('resumen-total').textContent = formatNumber(total);
        }

        // Observar cambios en todas las tablas
        const inputSelector =
            '.consignacion-valor-input, .descuento-valor-input, .cartera-valor-input, .tabla-qr .qr-valor, .tabla-transferencias .puntos-valor, .tabla-transferencias .transferencia-valor, .tabla-gasolina-eds .puntos-valor, .tabla-varios .varios-valor, .tabla-recaudos .recaudo-valor';

        document.querySelectorAll(inputSelector).forEach(input => {
            input.addEventListener('input', updateResumen);
            input.addEventListener('change', updateResumen);
        });

        // Usar MutationObserver para detectar cuando se agregan nuevas filas dinámicamente
        const tables = document.querySelectorAll(
            '.medios-pago-table tbody, .tabla-qr tbody, .tabla-transferencias tbody, .tabla-gasolina-eds tbody, .tabla-varios tbody, .tabla-recaudos tbody'
            );

        tables.forEach(table => {
            const observer = new MutationObserver(() => {
                // Re-attach listeners a nuevas filas
                document.querySelectorAll(inputSelector).forEach(input => {
                    input.removeEventListener('input', updateResumen);
                    input.removeEventListener('change', updateResumen);
                    input.addEventListener('input', updateResumen);
                    input.addEventListener('change', updateResumen);
                });
                updateResumen();
            });

            observer.observe(table, {
                childList: true,
                subtree: true
            });
        });

        // Cálculo inicial
        updateResumen();
    });
</script>
