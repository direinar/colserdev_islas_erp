<x-erp-card title="RESUMEN DE LO VENDIDO EN ESTE TURNO">

    <div class="table-responsive">
        <table class="table table-bordered table-sm" style="background-color: #e8f8e7; border-color: #9cbf8f;">
            <thead>
                <tr style="background-color: #d6f7c5;">
                    <th></th>
                    <th class="text-center">VENTAS/IAPROPIADA</th>
                    <th class="text-center">VENTAS/SURTIDORES</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>COMBUSTIBLE</strong></td>
                    <td class="text-end" id="resumen-combustible-iapropiada">
                        0
                    </td>
                    <td class="text-end" id="resumen-combustible-surtidores">
                        0
                    </td>
                </tr>
                <tr>
                    <td><strong>CANASTILLA</strong></td>
                    <td class="text-end" id="resumen-lubricantes-iapropiada">
                        0
                    </td>
                    <td class="text-end" id="resumen-lubricantes-surtidores">
                        0
                    </td>
                </tr>
                <tr style="background-color: #c9f0ad; font-size: 1.05rem;">
                    <td><strong>TOTALES</strong></td>
                    <td class="text-end">
                        <strong id="resumen-total-iapropiada" class="text-primary">0</strong>
                    </td>
                    <td class="text-end"><strong id="resumen-total-surtidores">0</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function parseNumber(value) {
                if (!value) return 0;
                return Number(String(value).replace(/\./g, '').replace(/,/g, '.')) || 0;
            }

            function formatNumber(value) {
                return value.toLocaleString('es-CO', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function updateResumenValues() {
                // Obtener total de combustible IAPROPIADA (ventas.blade.php)
                const ventasTotalElement = document.querySelector('.ventas-total-turno');
                const combustibleIapropiada = ventasTotalElement ?
                    parseNumber(ventasTotalElement.textContent) : 0;

                // Obtener total de combustible SURTIDORES (surtidores.blade.php)
                const surtidoresTotalElement = document.querySelector('.ventas-total-lectura');
                const combustibleSurtidores = surtidoresTotalElement ?
                    parseNumber(surtidoresTotalElement.textContent) : 0;

                // Obtener total de lubricantes (lubricantes.blade.php)
                const lubricantesTotalElement = document.getElementById('total-total');
                const lubricantesTotal = lubricantesTotalElement ?
                    parseNumber(lubricantesTotalElement.textContent) : 0;

                // Actualizar campos en la tabla de resumen
                const combustibleIapropiataCell = document.getElementById('resumen-combustible-iapropiada');
                const combustibleSurtidoresCell = document.getElementById('resumen-combustible-surtidores');
                const lubricantesIapropiataCell = document.getElementById('resumen-lubricantes-iapropiada');
                const lubricanteSurtidoresCell = document.getElementById('resumen-lubricantes-surtidores');
                const totalIapropiataCell = document.getElementById('resumen-total-iapropiada');
                const totalSurtidoresCell = document.getElementById('resumen-total-surtidores');

                if (combustibleIapropiataCell)
                    combustibleIapropiataCell.textContent = formatNumber(combustibleIapropiada);
                if (combustibleSurtidoresCell)
                    combustibleSurtidoresCell.textContent = formatNumber(combustibleSurtidores);
                if (lubricantesIapropiataCell)
                    lubricantesIapropiataCell.textContent = formatNumber(lubricantesTotal);
                if (lubricanteSurtidoresCell)
                    lubricanteSurtidoresCell.textContent = formatNumber(lubricantesTotal);

                // Calcular y actualizar totales
                const totalIapropiada = combustibleIapropiada + lubricantesTotal;
                const totalSurtidores = combustibleSurtidores + lubricantesTotal;

                if (totalIapropiataCell)
                    totalIapropiataCell.textContent = formatNumber(totalIapropiada);
                if (totalSurtidoresCell)
                    totalSurtidoresCell.textContent = formatNumber(totalSurtidores);
            }

            // Ejecutar al cargar
            updateResumenValues();

            // Actualizar cuando cambian los valores en otros archivos
            const observer = new MutationObserver(updateResumenValues);

            // Observar cambios en ventas
            const ventasTotal = document.querySelector('.ventas-total-turno');
            if (ventasTotal) observer.observe(ventasTotal, {
                characterData: true,
                subtree: true,
                childList: true
            });

            // Observar cambios en surtidores
            const surtidoresTotal = document.querySelector('.ventas-total-lectura');
            if (surtidoresTotal) observer.observe(surtidoresTotal, {
                characterData: true,
                subtree: true,
                childList: true
            });

            // Observar cambios en lubricantes
            const lubricantesTotal = document.getElementById('total-total');
            if (lubricantesTotal) observer.observe(lubricantesTotal, {
                characterData: true,
                subtree: true,
                childList: true
            });

            // Polling adicional cada 500ms para garantizar actualización
            setInterval(updateResumenValues, 500);
        });
    </script>

</x-erp-card>
