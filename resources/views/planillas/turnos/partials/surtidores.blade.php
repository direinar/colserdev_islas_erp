<x-erp-card title="LECTURA ELECTRÓNICA">

    <table class="table table-bordered table-erp">

        <thead class="bg-blue">
            <tr>
                <th>MANGUERA</th>
                <th>INICIAL</th>
                <th>FINAL</th>
                <th>GLS</th>
            </tr>
        </thead>
        <tbody>
            <tr data-combustible="corriente">
                <td>PLUS 01</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="corriente">
                <td>PLUS 02</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="acpm">
                <td>ACPM 03</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="acpm">
                <td>ACPM 04</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="corriente">
                <td>PLUS 05</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="corriente">
                <td>PLUS 06</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="acpm">
                <td>ACPM 07</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="acpm">
                <td>ACPM 08</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="corriente">
                <td>PLUS 09</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="corriente">
                <td>PLUS 10</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="acpm">
                <td>ACPM 11</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>
            <tr data-combustible="acpm">
                <td>ACPM 12</td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-inicial"></td>
                <td><input type="text" inputmode="decimal" placeholder="0,000"
                        class="form-control erp-input lectura-final"></td>
                <td><input type="text" readonly class="form-control erp-input lectura-gls"></td>
            </tr>

            <!-- Total general + precios -->
            {{-- <tfoot>
            <tr class="table-secondary fw-bold">
                <td colspan="3" class="text-end">TOTAL</td>
                <td class="text-end ventas-total">0</td>
            </tr>
            <tr class="table-warning">
                <th colspan="3">PRECIO CORRIENTE</th>
                <th class="text-end">
                    {{ number_format(config('combustibles.corriente'), 0, ',', '.') }}
                </th>
            </tr>
            <tr class="table-info">
                <th colspan="3">PRECIO ACPM</th>
                <th class="text-end">
                    {{ number_format(config('combustibles.acpm'), 0, ',', '.') }}
                </th>
            </tr>
        </tfoot> --}}


            <table class="table table-bordered w-100">

                <thead class="table-light">
                    <tr>
                        <th class="text-center col-4">
                            VENTA SEGÚN LECTURAS
                        </th>

                        <th class="text-center col-4">
                            CORRIENTE
                        </th>

                        <th class="text-center col-4">
                            ACPM
                        </th>
                    </tr>
                </thead>

                <tbody>

                    <tr>
                        <th scope="row">GALONES</th>

                        <td>
                            <input type="text" readonly
                                class="form-control form-control-sm ventas-lectura-galones-corriente">
                        </td>

                        <td>
                            <input type="text" readonly
                                class="form-control form-control-sm ventas-lectura-galones-acpm">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">VALOR</th>

                        <td>
                            <input type="text" readonly
                                class="form-control form-control-sm ventas-lectura-valor-corriente"
                                data-precio="{{ config('combustibles.corriente') }}">
                        </td>

                        <td>
                            <input type="text" readonly
                                class="form-control form-control-sm ventas-lectura-valor-acpm"
                                data-precio="{{ config('combustibles.acpm') }}">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">PRECIOS</th>

                        <th class="text-end">
                            {{ number_format(config('combustibles.corriente'), 0, ',', '.') }}
                        </th>
                        <th class="text-end">
                            {{ number_format(config('combustibles.acpm'), 0, ',', '.') }}
                        </th>
                    </tr>
                    <tr class="table-secondary fw-bold">
                        <td colspan="2" class="text-end">TOTAL</td>
                        <td class="text-end ventas-total-lectura">0</td>
                    </tr>

                </tbody>

            </table>

        </tbody>

    </table>

</x-erp-card>
