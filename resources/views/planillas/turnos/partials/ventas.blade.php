<x-erp-card title="INFORMACION DE VENTAS DEL TURNO" title-class="turno-card-title">

    <div class="table-responsive">
        <table class="table table-bordered table-sm">

            <!-- Encabezado principal -->
            <thead class="ventas-header">
                <tr class="ventas-title-row bg-green-100">
                    <th colspan="3" class="text-center font-bold">
                        VENTAS SEGÚN CIERRES DE IAPPROPIADA
                    </th>
                </tr>

                <tr class="bg-green-100">
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
                                @php $galonesClass = $v->combustible === 'ACPM' ? 'galones-acpm' : 'galones-cte'; @endphp
                                <input type="text" name="ventas[{{ $i }}][galones]"
                                    class="form-control form-control-sm erp-input galones-input {{ $galonesClass }}"
                                    data-precio="{{ $v->combustible === 'ACPM' ? config('combustibles.acpm') : config('combustibles.corriente') }}"
                                    value="{{ number_format($v->galones, 3, '.', ',') }}">
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
                    <th class="text-left whitespace-nowrap">
                        VENTA SEGUN CORTES
                    </th>

                    <th class="text-center whitespace-nowrap">
                        CORRIENTE
                    </th>

                    <th class="text-center whitespace-nowrap">
                        ACPM
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <th scope="row" class="text-left">
                        GALONES
                    </th>

                    <td>
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-galones-corriente text-end"
                            value="{{ isset($turno) ? number_format($turno->tirillas_galones_corriente, 3, '.', ',') : '' }}">
                    </td>

                    <td>
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-galones-acpm text-end"
                            value="{{ isset($turno) ? number_format($turno->tirillas_galones_acpm, 3, '.', ',') : '' }}">
                    </td>
                </tr>

                <tr>
                    <th scope="row" class="text-left">
                        VALOR
                    </th>

                    <td>
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-valor-corriente text-end"
                            data-precio="{{ config('combustibles.corriente') }}"
                            value="{{ isset($turno) ? number_format($turno->tirillas_valor_corriente, 0, ',', '.') : '' }}">
                    </td>

                    <td>
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-valor-acpm text-end"
                            data-precio="{{ config('combustibles.acpm') }}"
                            value="{{ isset($turno) ? number_format($turno->tirillas_valor_acpm, 0, ',', '.') : '' }}">
                    </td>
                </tr>
            </tbody>

            <!-- Total general + precios -->
            <tfoot>
                <tr class="table-secondary fw-bold">
                    <td colspan="2" class="text-end" style="text-align: right;">TOTAL</td>
                    <td class="text-end ventas-total-turno" style="text-align: right;">
                        {{ isset($turno) ? number_format($turno->total_ventas, 0, ',', '.') : '0' }}</td>
                </tr>
                {{-- <tr class="table-warning">
                    <th colspan="2">PRECIO CORRIENTE</th>
                    <th class="text-end">
                        {{ number_format(config('combustibles.corriente'), 0, '.', ',') }}
                    </th>
                </tr> --}}
                {{-- <tr class="table-info">
                    <th colspan="2">PRECIO ACPM</th>
                    <th class="text-end">
                        {{ number_format(config('combustibles.acpm'), 0, '.', ',') }}
                    </th>
                </tr> --}}
            </tfoot>

        </table>
    </div>

</x-erp-card>
