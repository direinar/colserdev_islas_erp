<x-erp-card title="INFORMACION DE VENTAS DEL TURNO">

    <div class="table-responsive">
        <table class="table table-bordered table-sm">

            <!-- Encabezado principal -->
            <thead class="bg-yellow">
                <tr>
                    <th colspan="3" class="text-center">VENTAS SEGÚN CIERRES DE IAPPROPIADA</th>
                </tr>
                <tr>
                    <th>SURTIDOR</th>
                    <th class="text-end">GALONES</th>
                    <th class="text-end">VALOR</th>
                </tr>
            </thead>

            <!-- Sección: Ventas por surtidor -->
            <tbody>
                <tr>
                    <td>SURTIDOR 1 CTE</td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input galones-input galones-cte"
                            data-precio="{{ config('combustibles.corriente') }}">
                    </td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input valor-total" readonly>
                    </td>
                </tr>
                <tr>
                    <td>SURTIDOR 1 ACPM</td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input galones-input galones-acpm"
                            data-precio="{{ config('combustibles.acpm') }}">
                    </td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input valor-total" readonly>
                    </td>
                </tr>
                <tr>
                    <td>SURTIDOR 2 CTE</td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input galones-input galones-cte"
                            data-precio="{{ config('combustibles.corriente') }}">
                    </td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input valor-total" readonly>
                    </td>
                </tr>
                <tr>
                    <td>SURTIDOR 2 ACPM</td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input galones-input galones-acpm"
                            data-precio="{{ config('combustibles.acpm') }}">
                    </td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input valor-total" readonly>
                    </td>
                </tr>
                <tr>
                    <td>SURTIDOR 3 ACPM</td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input galones-input galones-acpm"
                            data-precio="{{ config('combustibles.acpm') }}">
                    </td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input valor-total" readonly>
                    </td>
                </tr>
                <tr>
                    <td>SURTIDOR 3 CTE</td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input galones-input galones-cte"
                            data-precio="{{ config('combustibles.corriente') }}">
                    </td>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm erp-input valor-total" readonly>
                    </td>
                </tr>
            </tbody>

            <!-- Sección: Venta en tirillas -->
            <thead class="bg-light">

                <tr>

                    <th class="text-center">
                        VENTA EN TIRILLAS DE CORTES
                    </th>

                    <th class="text-center">
                        CORRIENTE
                    </th>

                    <th class="text-center">
                        ACPM
                    </th>

                </tr>

            </thead>
            <tbody>
                <tr>
                    <th scope="row">GALONES</th>
                    <td class="text-end">
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-galones-corriente">
                    </td>
                    <td class="text-end">
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-galones-acpm">
                    </td>
                </tr>
                <tr>
                    <th scope="row">VALOR</th>
                    <td class="text-end">
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-valor-corriente"
                            data-precio="{{ config('combustibles.corriente') }}">
                    </td>
                    <td class="text-end">
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-valor-acpm"
                            data-precio="{{ config('combustibles.acpm') }}">
                    </td>
                </tr>
            </tbody>

            <!-- Total general + precios -->
            <tfoot>
                <tr class="table-secondary fw-bold">
                    <td colspan="2" class="text-end">TOTAL</td>
                    <td class="text-end ventas-total-turno">0</td>
                </tr>
                {{-- <tr class="table-warning">
                    <th colspan="2">PRECIO CORRIENTE</th>
                    <th class="text-end">
                        {{ number_format(config('combustibles.corriente'), 0, ',', '.') }}
                    </th>
                </tr> --}}
                {{-- <tr class="table-info">
                    <th colspan="2">PRECIO ACPM</th>
                    <th class="text-end">
                        {{ number_format(config('combustibles.acpm'), 0, ',', '.') }}
                    </th>
                </tr> --}}
            </tfoot>

        </table>
    </div>

</x-erp-card>
