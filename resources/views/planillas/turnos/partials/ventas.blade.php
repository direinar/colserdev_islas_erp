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
                @if (isset($turno) && optional($turno->ventas)->count())
                    @foreach ($turno->ventas as $i => $v)
                        <tr>
                            <td>
                                <input type="hidden" name="ventas[{{ $i }}][surtidor]"
                                    value="{{ $v->surtidor }}">
                                <input type="hidden" name="ventas[{{ $i }}][combustible]"
                                    value="{{ $v->combustible }}">
                                {{ $v->surtidor }}
                            </td>
                            <td class="text-end">
                                <input type="text" name="ventas[{{ $i }}][galones]"
                                    class="form-control form-control-sm erp-input galones-input"
                                    data-precio="{{ $v->combustible === 'ACPM' ? config('combustibles.acpm') : config('combustibles.corriente') }}"
                                    value="{{ number_format($v->galones, 3, ',', '.') }}">
                            </td>
                            <td class="text-end">
                                <input type="text" name="ventas[{{ $i }}][valor]"
                                    class="form-control form-control-sm erp-input valor-total" readonly
                                    value="{{ number_format($v->valor, 0, ',', '.') }}">
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>
                            SURTIDOR 1 CTE
                            <input type="hidden" name="ventas[0][surtidor]" value="SURTIDOR 1 CTE">
                            <input type="hidden" name="ventas[0][combustible]" value="CTE">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[0][galones]"
                                class="form-control form-control-sm erp-input galones-input galones-cte"
                                data-precio="{{ config('combustibles.corriente') }}">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[0][valor]"
                                class="form-control form-control-sm erp-input valor-total" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            SURTIDOR 1 ACPM
                            <input type="hidden" name="ventas[1][surtidor]" value="SURTIDOR 1 ACPM">
                            <input type="hidden" name="ventas[1][combustible]" value="ACPM">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[1][galones]"
                                class="form-control form-control-sm erp-input galones-input galones-acpm"
                                data-precio="{{ config('combustibles.acpm') }}">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[1][valor]"
                                class="form-control form-control-sm erp-input valor-total" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            SURTIDOR 2 CTE
                            <input type="hidden" name="ventas[2][surtidor]" value="SURTIDOR 2 CTE">
                            <input type="hidden" name="ventas[2][combustible]" value="CTE">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[2][galones]"
                                class="form-control form-control-sm erp-input galones-input galones-cte"
                                data-precio="{{ config('combustibles.corriente') }}">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[2][valor]"
                                class="form-control form-control-sm erp-input valor-total" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            SURTIDOR 2 ACPM
                            <input type="hidden" name="ventas[3][surtidor]" value="SURTIDOR 2 ACPM">
                            <input type="hidden" name="ventas[3][combustible]" value="ACPM">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[3][galones]"
                                class="form-control form-control-sm erp-input galones-input galones-acpm"
                                data-precio="{{ config('combustibles.acpm') }}">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[3][valor]"
                                class="form-control form-control-sm erp-input valor-total" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            SURTIDOR 3 ACPM
                            <input type="hidden" name="ventas[4][surtidor]" value="SURTIDOR 3 ACPM">
                            <input type="hidden" name="ventas[4][combustible]" value="ACPM">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[4][galones]"
                                class="form-control form-control-sm erp-input galones-input galones-acpm"
                                data-precio="{{ config('combustibles.acpm') }}">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[4][valor]"
                                class="form-control form-control-sm erp-input valor-total" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            SURTIDOR 3 CTE
                            <input type="hidden" name="ventas[5][surtidor]" value="SURTIDOR 3 CTE">
                            <input type="hidden" name="ventas[5][combustible]" value="CTE">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[5][galones]"
                                class="form-control form-control-sm erp-input galones-input galones-cte"
                                data-precio="{{ config('combustibles.corriente') }}">
                        </td>
                        <td class="text-end">
                            <input type="text" name="ventas[5][valor]"
                                class="form-control form-control-sm erp-input valor-total" readonly>
                        </td>
                    </tr>
                @endif
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function parseNumber(value) {
            if (!value) return 0;
            return Number(String(value).replace(/\./g, '').replace(/,/g, '.')) || 0;
        }

        function formatNumber(value, decimals = 0) {
            return Number(value).toLocaleString('es-CO', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        }

        function updateVentasResumen() {
            let galonesCorriente = 0;
            let galonesAcpM = 0;
            let valorCorriente = 0;
            let valorAcpM = 0;
            let totalTurno = 0;

            document.querySelectorAll('.galones-input').forEach(input => {
                const tr = input.closest('tr');
                if (!tr) return;

                const combustibleInput = tr.querySelector('input[name$="[combustible]"]');
                const valorInput = tr.querySelector('input[name$="[valor]"]');
                const galones = parseNumber(input.value);
                const valor = parseNumber(valorInput ? valorInput.value : 0);
                const combustible = combustibleInput ? String(combustibleInput.value).toLowerCase() :
                '';

                if (combustible === 'acpm') {
                    galonesAcpM += galones;
                    valorAcpM += valor;
                } else {
                    galonesCorriente += galones;
                    valorCorriente += valor;
                }

                totalTurno += valor;
            });

            const galonesCorrienteField = document.querySelector('.tirillas-galones-corriente');
            const galonesAcpMField = document.querySelector('.tirillas-galones-acpm');
            const valorCorrienteField = document.querySelector('.tirillas-valor-corriente');
            const valorAcpMField = document.querySelector('.tirillas-valor-acpm');
            const ventasTotalTurno = document.querySelector('.ventas-total-turno');

            if (galonesCorrienteField) galonesCorrienteField.value = formatNumber(galonesCorriente, 3);
            if (galonesAcpMField) galonesAcpMField.value = formatNumber(galonesAcpM, 3);
            if (valorCorrienteField) valorCorrienteField.value = formatNumber(valorCorriente, 0);
            if (valorAcpMField) valorAcpMField.value = formatNumber(valorAcpM, 0);
            if (ventasTotalTurno) ventasTotalTurno.textContent = formatNumber(totalTurno, 0);
        }

        updateVentasResumen();

        document.querySelectorAll('.galones-input').forEach(input => {
            input.addEventListener('input', updateVentasResumen);
            input.addEventListener('change', updateVentasResumen);
        });
    });
</script>
