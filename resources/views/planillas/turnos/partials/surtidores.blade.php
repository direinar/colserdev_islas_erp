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
                @php
                    $lecturaInicialBloqueada = $turno->numero_turno > 1;
                @endphp
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
                                value="{{ number_format($s->lectura_inicial, 3, '.', ',') }}" @readonly($lecturaInicialBloqueada)></td>
                        <td><input type="text" name="lecturas[{{ $i }}][lectura_final]"
                                inputmode="decimal" placeholder="0,000" class="form-control erp-input lectura-final"
                                value="{{ number_format($s->lectura_final, 3, '.', ',') }}"></td>
                        <td><input type="text" name="lecturas[{{ $i }}][galones]" readonly
                                class="form-control erp-input lectura-gls"
                                value="{{ number_format($s->galones, 3, '.', ',') }}"></td>
                    </tr>
                @endforeach
            @else
                @php
                    // Mapear surtidores anteriores por manguera para acceso rápido
                    $surtidoresMap = collect(optional($previousTurno)->surtidores ?? [])->keyBy('manguera');
                    $lecturaInicialBloqueada = ($nextNumber ?? 1) > 1;

                    // Configuración de mangueras en orden
                    $mangueras = [
                        ['idx' => 0, 'name' => 'PLUS 01', 'combustible' => 'corriente'],
                        ['idx' => 1, 'name' => 'PLUS 02', 'combustible' => 'corriente'],
                        ['idx' => 2, 'name' => 'ACPM 03', 'combustible' => 'acpm'],
                        ['idx' => 3, 'name' => 'ACPM 04', 'combustible' => 'acpm'],
                        ['idx' => 4, 'name' => 'PLUS 05', 'combustible' => 'corriente'],
                        ['idx' => 5, 'name' => 'PLUS 06', 'combustible' => 'corriente'],
                        ['idx' => 6, 'name' => 'ACPM 07', 'combustible' => 'acpm'],
                        ['idx' => 7, 'name' => 'ACPM 08', 'combustible' => 'acpm'],
                        ['idx' => 8, 'name' => 'PLUS 09', 'combustible' => 'corriente'],
                        ['idx' => 9, 'name' => 'PLUS 10', 'combustible' => 'corriente'],
                        ['idx' => 10, 'name' => 'ACPM 11', 'combustible' => 'acpm'],
                        ['idx' => 11, 'name' => 'ACPM 12', 'combustible' => 'acpm'],
                    ];
                @endphp
                @foreach ($mangueras as $manguera)
                    @php
                        $dataType = $manguera['combustible'] === 'acpm' ? 'acpm' : 'corriente';
                        $surtidorAnterior = $surtidoresMap->get($manguera['name']);
                        $lecturainicial = $surtidorAnterior
                            ? number_format($surtidorAnterior->lectura_final, 3, '.', ',')
                            : '';
                    @endphp
                    <tr data-combustible="{{ $dataType }}">
                        <td>
                            {{ $manguera['name'] }}
                            <input type="hidden" name="lecturas[{{ $manguera['idx'] }}][manguera]"
                                value="{{ $manguera['name'] }}">
                            <input type="hidden" name="lecturas[{{ $manguera['idx'] }}][combustible]"
                                value="{{ $dataType }}">
                        </td>
                        <td><input type="text" name="lecturas[{{ $manguera['idx'] }}][lectura_inicial]"
                                inputmode="decimal" placeholder="0,000" class="form-control erp-input lectura-inicial"
                                value="{{ $lecturainicial }}" @readonly($lecturaInicialBloqueada)></td>
                        <td><input type="text" name="lecturas[{{ $manguera['idx'] }}][lectura_final]"
                                inputmode="decimal" placeholder="0,000" class="form-control erp-input lectura-final">
                        </td>
                        <td><input type="text" name="lecturas[{{ $manguera['idx'] }}][galones]" readonly
                                class="form-control erp-input lectura-gls"></td>
                    </tr>
                @endforeach
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
