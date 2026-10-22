<x-erp-card title="LECTURA ELECTRÓNICA" title-class="lectura-electronica-title">

    <style>
        .ventas-lecturas-table th {
            font-size: clamp(0.65rem, 1.6vw, 0.95rem);
            white-space: normal;
            word-break: normal;
            overflow-wrap: break-word;
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
    </style>

    {{-- Ayuda: casi siempre solo 2 a 4 mangueras venden en el turno; las demás
         cierran con la misma lectura inicial. Se copian todas y se corrigen las que cambiaron. --}}
    <div class="d-flex justify-content-end mb-2">
        <button type="button" id="copiar-lecturas-btn" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-files"></i> COPIAR SALDOS INICIALES A FINALES
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const boton = document.getElementById('copiar-lecturas-btn');
            if (!boton) return;

            const filas = () => Array.from(document.querySelectorAll('tr'))
                .filter(row => row.querySelector('.lectura-inicial') && row.querySelector('.lectura-final'));

            function copiar() {
                filas().forEach(row => {
                    const final = row.querySelector('.lectura-final');
                    final.value = row.querySelector('.lectura-inicial').value;
                    // galones.js recalcula GLS y los totales con el evento input.
                    final.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                });
            }

            boton.addEventListener('click', function() {
                const hayFinales = filas().some(row => row.querySelector('.lectura-final').value.trim() !== '');

                if (!hayFinales) {
                    copiar();
                    return;
                }

                Swal.fire({
                    title: '¿Reemplazar las lecturas finales?',
                    text: 'Ya hay lecturas finales digitadas. Se reemplazarán todas por las lecturas iniciales.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, copiar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                }).then(result => {
                    if (result.isConfirmed) copiar();
                });
            });
        });
    </script>

    <div class="table-responsive">
        <table class="table table-bordered table-erp">

            <thead style="background-color: #b5fdd2;">
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
                        {{-- Con cambio de contador, galones.js suma (retiro - instalación) a final - inicial. --}}
                        <tr data-combustible="{{ $s->combustible }}"
                            @if ($s->tieneCambioContador()) data-ajuste-contador="{{ $s->ajusteContador() }}" @endif>
                            <td>
                                <input type="hidden" name="lecturas[{{ $i }}][manguera]"
                                    value="{{ $s->manguera }}">
                                <input type="hidden" name="lecturas[{{ $i }}][combustible]"
                                    value="{{ $s->combustible }}">
                                {{ $s->manguera }}
                                @if ($s->tieneCambioContador())
                                    <div class="small text-danger fw-semibold">Cambio de contador: retiro
                                        {{ number_format($s->contador_retiro, 3, '.', ',') }}, nuevo desde
                                        {{ number_format($s->contador_instalacion, 3, '.', ',') }}</div>
                                @endif
                            </td>
                            <td><input type="text" name="lecturas[{{ $i }}][lectura_inicial]"
                                    inputmode="decimal" placeholder="0,000"
                                    class="form-control erp-input lectura-inicial"
                                    value="{{ number_format($s->lectura_inicial, 3, '.', ',') }}" @readonly($lecturaInicialBloqueada)>
                            </td>
                            {{-- Lectura final en blanco = pendiente: el turno sigue abierto. --}}
                            <td><input type="text" name="lecturas[{{ $i }}][lectura_final]"
                                    inputmode="decimal" placeholder="0,000" class="form-control erp-input lectura-final"
                                    value="{{ $s->lectura_final === null ? '' : number_format($s->lectura_final, 3, '.', ',') }}"></td>
                            <td><input type="text" name="lecturas[{{ $i }}][galones]" readonly
                                    class="form-control erp-input lectura-gls"
                                    value="{{ number_format($s->galones, 3, '.', ',') }}"></td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" class="text-center text-muted">Esta planilla no tiene mangueras.</td>
                    </tr>
                @endif

                <table class="table table-bordered w-100 ventas-lecturas-table" style="table-layout: fixed;">

                    <colgroup>
                        <col style="width: 44%;">
                        <col style="width: 28%;">
                        <col style="width: 28%;">
                    </colgroup>

                    <thead class="table-light">
                        <tr>
                            <th class="text-center">
                                VENTA SEGÚN LECTURAS
                            </th>

                            <th class="text-end">
                                GALONES
                            </th>

                            <th class="text-end">
                                VALOR
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <th scope="row">CORRIENTE</th>

                            <td>
                                <input type="text" readonly
                                    class="form-control form-control-sm ventas-lectura-galones-corriente text-end"
                                    value="{{ isset($turno) ? number_format($turno->lecturas_galones_corriente, 3, '.', ',') : '' }}">
                            </td>

                            <td>
                                <input type="text" readonly
                                    class="form-control form-control-sm ventas-lectura-valor-corriente text-end"
                                    data-precio="{{ $precioCorriente }}"
                                    value="{{ isset($turno) ? number_format($turno->lecturas_valor_corriente, 0, ',', '.') : '' }}">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">ACPM</th>

                            <td>
                                <input type="text" readonly
                                    class="form-control form-control-sm ventas-lectura-galones-acpm text-end"
                                    value="{{ isset($turno) ? number_format($turno->lecturas_galones_acpm, 3, '.', ',') : '' }}">
                            </td>

                            <td>
                                <input type="text" readonly
                                    class="form-control form-control-sm ventas-lectura-valor-acpm text-end"
                                    data-precio="{{ $precioAcpm }}"
                                    value="{{ isset($turno) ? number_format($turno->lecturas_valor_acpm, 0, ',', '.') : '' }}">
                            </td>
                        </tr>

                        <tr class="table-secondary fw-bold">
                            <td class="text-start">TOTAL</td>
                            <td class="text-end ventas-total-lectura-galones">
                                {{ isset($turno) ? number_format($turno->lecturas_galones_corriente + $turno->lecturas_galones_acpm, 3, '.', ',') : '' }}
                            </td>
                            <td class="text-end ventas-total-lectura">
                                {{ isset($turno) ? number_format($turno->total_venta_lecturas, 0, ',', '.') : '0' }}
                            </td>
                        </tr>

                    </tbody>

                </table>

                <div class="card text-center mt-2">
                    <div class="card-header py-1 fw-bold" style="background-color: #b5fdd2;">PRECIOS ACTUALES</div>
                    <div class="card-body py-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="fw-bold small">CORRIENTE</div>
                                <div>{{ number_format($precioCorriente, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold small">ACPM</div>
                                <div>{{ number_format($precioAcpm, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            </tbody>

        </table>
    </div>

</x-erp-card>
