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
            @if (isset($turno) && optional($turno->surtidores)->count())
                @foreach ($turno->surtidores as $i => $s)
                    <tr data-combustible="{{ $s->combustible }}">
                        <td>
                            <input type="hidden" name="lecturas[{{ $i }}][manguera]"
                                value="{{ $s->manguera }}">
                            <input type="hidden" name="lecturas[{{ $i }}][combustible]"
                                value="{{ $s->combustible }}">
                            {{ $s->manguera }}
                        </td>
                        <td><input type="text" name="lecturas[{{ $i }}][lectura_inicial]"
                                inputmode="decimal" placeholder="0,000" class="form-control erp-input lectura-inicial"
                                value="{{ number_format($s->lectura_inicial, 3, '.', ',') }}"></td>
                        <td><input type="text" name="lecturas[{{ $i }}][lectura_final]"
                                inputmode="decimal" placeholder="0,000" class="form-control erp-input lectura-final"
                                value="{{ number_format($s->lectura_final, 3, '.', ',') }}"></td>
                        <td><input type="text" name="lecturas[{{ $i }}][galones]" readonly
                                class="form-control erp-input lectura-gls"
                                value="{{ number_format($s->galones, 3, '.', ',') }}"></td>
                    </tr>
                @endforeach
            @else
                <tr data-combustible="corriente">
                    <td>
                        PLUS 01
                        <input type="hidden" name="lecturas[0][manguera]" value="PLUS 01">
                        <input type="hidden" name="lecturas[0][combustible]" value="corriente">
                    </td>
                    <td><input type="text" name="lecturas[0][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[0][lectura_final]" inputmode="decimal" placeholder="0,000"
                            class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[0][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="corriente">
                    <td>
                        PLUS 02
                        <input type="hidden" name="lecturas[1][manguera]" value="PLUS 02">
                        <input type="hidden" name="lecturas[1][combustible]" value="corriente">
                    </td>
                    <td><input type="text" name="lecturas[1][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[1][lectura_final]" inputmode="decimal" placeholder="0,000"
                            class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[1][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="acpm">
                    <td>
                        ACPM 03
                        <input type="hidden" name="lecturas[2][manguera]" value="ACPM 03">
                        <input type="hidden" name="lecturas[2][combustible]" value="acpm">
                    </td>
                    <td><input type="text" name="lecturas[2][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[2][lectura_final]" inputmode="decimal" placeholder="0,000"
                            class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[2][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="acpm">
                    <td>
                        ACPM 04
                        <input type="hidden" name="lecturas[3][manguera]" value="ACPM 04">
                        <input type="hidden" name="lecturas[3][combustible]" value="acpm">
                    </td>
                    <td><input type="text" name="lecturas[3][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[3][lectura_final]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[3][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="corriente">
                    <td>
                        PLUS 05
                        <input type="hidden" name="lecturas[4][manguera]" value="PLUS 05">
                        <input type="hidden" name="lecturas[4][combustible]" value="corriente">
                    </td>
                    <td><input type="text" name="lecturas[4][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[4][lectura_final]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[4][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="corriente">
                    <td>
                        PLUS 06
                        <input type="hidden" name="lecturas[5][manguera]" value="PLUS 06">
                        <input type="hidden" name="lecturas[5][combustible]" value="corriente">
                    </td>
                    <td><input type="text" name="lecturas[5][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[5][lectura_final]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[5][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="acpm">
                    <td>
                        ACPM 07
                        <input type="hidden" name="lecturas[6][manguera]" value="ACPM 07">
                        <input type="hidden" name="lecturas[6][combustible]" value="acpm">
                    </td>
                    <td><input type="text" name="lecturas[6][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[6][lectura_final]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[6][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="acpm">
                    <td>
                        ACPM 08
                        <input type="hidden" name="lecturas[7][manguera]" value="ACPM 08">
                        <input type="hidden" name="lecturas[7][combustible]" value="acpm">
                    </td>
                    <td><input type="text" name="lecturas[7][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[7][lectura_final]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[7][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="corriente">
                    <td>
                        PLUS 09
                        <input type="hidden" name="lecturas[8][manguera]" value="PLUS 09">
                        <input type="hidden" name="lecturas[8][combustible]" value="corriente">
                    </td>
                    <td><input type="text" name="lecturas[8][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[8][lectura_final]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[8][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="corriente">
                    <td>
                        PLUS 10
                        <input type="hidden" name="lecturas[9][manguera]" value="PLUS 10">
                        <input type="hidden" name="lecturas[9][combustible]" value="corriente">
                    </td>
                    <td><input type="text" name="lecturas[9][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[9][lectura_final]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[9][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="acpm">
                    <td>
                        ACPM 11
                        <input type="hidden" name="lecturas[10][manguera]" value="ACPM 11">
                        <input type="hidden" name="lecturas[10][combustible]" value="acpm">
                    </td>
                    <td><input type="text" name="lecturas[10][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[10][lectura_final]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[10][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
                </tr>
                <tr data-combustible="acpm">
                    <td>
                        ACPM 12
                        <input type="hidden" name="lecturas[11][manguera]" value="ACPM 12">
                        <input type="hidden" name="lecturas[11][combustible]" value="acpm">
                    </td>
                    <td><input type="text" name="lecturas[11][lectura_inicial]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-inicial"></td>
                    <td><input type="text" name="lecturas[11][lectura_final]" inputmode="decimal"
                            placeholder="0,000" class="form-control erp-input lectura-final"></td>
                    <td><input type="text" name="lecturas[11][galones]" readonly
                            class="form-control erp-input lectura-gls"></td>
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
            @endif

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
