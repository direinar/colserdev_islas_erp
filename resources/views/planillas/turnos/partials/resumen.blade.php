<x-erp-card title="RESUMEN DE LO VENDIDO EN ESTE TURNO" title-class="lectura-electronica-title">

    <div class="table-responsive">
        <table class="table table-bordered table-sm" style="background-color: #e8f8e7; border-color: #9cbf8f;">
            <thead>
                <tr style="background-color: #b5fdd2;">
                    <th></th>
                    <th class="text-end">VENTAS/IAPROPIADA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>COMBUSTIBLE</strong></td>
                    <td class="text-end" id="resumen-combustible-iapropiada">
                        0
                    </td>
                </tr>
                <tr>
                    <td><strong>CANASTILLA</strong></td>
                    <td class="text-end" id="resumen-lubricantes-iapropiada">
                        0
                    </td>
                </tr>
                <tr style="background-color: #c9f0ad; font-size: 1.05rem;">
                    <td><strong>TOTALES</strong></td>
                    <td class="text-end">
                        <strong id="resumen-total-iapropiada" class="text-primary">0</strong>
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

                // Obtener total de lubricantes (lubricantes.blade.php)
                const lubricantesTotalElement = document.getElementById('total-total');
                const lubricantesTotal = lubricantesTotalElement ?
                    parseNumber(lubricantesTotalElement.textContent) : 0;

                // Actualizar campos en la tabla de resumen
                const combustibleIapropiataCell = document.getElementById('resumen-combustible-iapropiada');
                const lubricantesIapropiataCell = document.getElementById('resumen-lubricantes-iapropiada');
                const totalIapropiataCell = document.getElementById('resumen-total-iapropiada');

                if (combustibleIapropiataCell)
                    combustibleIapropiataCell.textContent = formatNumber(combustibleIapropiada);
                if (lubricantesIapropiataCell)
                    lubricantesIapropiataCell.textContent = formatNumber(lubricantesTotal);

                // Calcular y actualizar totales
                const totalIapropiada = combustibleIapropiada + lubricantesTotal;

                if (totalIapropiataCell)
                    totalIapropiataCell.textContent = formatNumber(totalIapropiada);
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
