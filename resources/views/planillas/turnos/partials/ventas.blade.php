{{-- Ventas según cierres de IAPROPIADA: un recuadro por surtidor y combustible, con el mismo
     patrón de INFORMACION DE MEDIOS DE PAGO (título con + AGREGAR FILA y ACCIÓN a la derecha)
     pero en verde. Un surtidor puede tener varios cierres en el turno. --}}
@php
    use App\Models\TurnoVenta;

    $ventasGuardadas = collect(optional($turno ?? null)->ventas ?? []);
    // Surtidores de la planilla y, si un turno viejo trae otro nombre, también ese.
    $surtidoresVentas = collect(TurnoVenta::SURTIDORES)
        ->union($ventasGuardadas->pluck('combustible', 'surtidor')->except(array_keys(TurnoVenta::SURTIDORES)));
    $etiquetaSurtidor = fn (string $surtidor): string => preg_replace('/ (CTE|ACPM)$/', ' - $1', $surtidor);
    $indiceVenta = 0;
@endphp

<x-erp-card title="INFORMACION DE VENTAS DEL TURNO" title-class="turno-card-title">

    <style>
        .ventas-turno-stack {
            display: grid;
            gap: .75rem;
        }

        .ventas-surtidor {
            border: 1px solid #dfe1ea;
            background: #f5f5f6;
            overflow: hidden;
        }

        /* Mismo patrón de medios de pago, en el verde de la tabla de ventas. */
        .ventas-surtidor .ventas-surtidor-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: 8px 10px;
            background: #b5fdd2;
            border-bottom: 1px solid #d0d0d0;
        }

        .ventas-surtidor .ventas-surtidor-titulo {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #1d1d1d;
        }

        .ventas-surtidor .agregar-venta {
            background: #f3f3f3 !important;
            color: #d93a2f !important;
            border-color: #d9d9d9 !important;
            font-weight: 700 !important;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 5px 10px !important;
        }

        .ventas-surtidor .agregar-venta:hover {
            background: #fff !important;
            border-color: #cfcfcf !important;
        }

        .ventas-surtidor-tabla thead th {
            background: #b5fdd2 !important;
            color: #1d1d1d !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            border-color: #d0d0d0 !important;
        }

        .ventas-surtidor-tabla tbody td {
            background: #fff;
            border-color: #d9d9d9 !important;
        }
    </style>

    <div class="text-center fw-bold mb-2">VENTAS SEGÚN CIERRES DE IAPPROPIADA</div>

    <div class="ventas-turno-stack" id="tabla-ventas-turno">
        @foreach ($surtidoresVentas as $surtidor => $combustible)
            @php
                $filasSurtidor = $ventasGuardadas->where('surtidor', $surtidor)->values();
                $precioSurtidor = $combustible === 'ACPM' ? $precioAcpm : $precioCorriente;
                $claseGalones = $combustible === 'ACPM' ? 'galones-acpm' : 'galones-cte';
            @endphp
            <div class="ventas-surtidor ventas-grupo" data-surtidor="{{ $surtidor }}" data-combustible="{{ $combustible }}"
                data-precio="{{ $precioSurtidor }}" data-clase-galones="{{ $claseGalones }}">
                <div class="ventas-surtidor-toolbar">
                    <div class="ventas-surtidor-titulo">{{ $etiquetaSurtidor($surtidor) }}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary agregar-venta">+ AGREGAR FILA</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 ventas-surtidor-tabla">
                        <thead>
                            <tr>
                                <th class="text-center">NO. CIERRE</th>
                                <th class="text-end">GALONES</th>
                                <th class="text-end">VALOR</th>
                                <th class="text-center">ACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody class="ventas-filas">
                            {{-- Un surtidor sin cierres guardados muestra una fila vacía para digitar. --}}
                            @foreach ($filasSurtidor->isEmpty() ? [null] : $filasSurtidor as $venta)
                                <tr class="venta-fila" data-index="{{ $indiceVenta }}">
                                    <td>
                                        <input type="hidden" name="ventas[{{ $indiceVenta }}][surtidor]" value="{{ $surtidor }}">
                                        <input type="hidden" name="ventas[{{ $indiceVenta }}][combustible]" value="{{ $combustible }}">
                                        <input type="text" name="ventas[{{ $indiceVenta }}][no_cierre]" maxlength="30"
                                            class="form-control form-control-sm erp-input text-center venta-no-cierre"
                                            value="{{ $venta?->no_cierre }}">
                                    </td>
                                    <td class="text-end">
                                        <input type="text" name="ventas[{{ $indiceVenta }}][galones]"
                                            class="form-control form-control-sm erp-input galones-input {{ $claseGalones }}"
                                            data-precio="{{ $precioSurtidor }}"
                                            value="{{ $venta ? number_format($venta->galones, 3, '.', ',') : '' }}">
                                    </td>
                                    <td class="text-end">
                                        <input type="text" name="ventas[{{ $indiceVenta }}][valor]"
                                            class="form-control form-control-sm erp-input valor-total" readonly
                                            value="{{ $venta ? number_format($venta->valor, 0, ',', '.') : '' }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger quitar-venta" aria-label="Quitar fila">×</button>
                                    </td>
                                </tr>
                                @php($indiceVenta++)
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-bordered table-sm mb-0">
            <!-- Sección: Total ventas según corte -->
            <thead class="bg-light">
                <tr>
                    <th class="text-left whitespace-nowrap">
                        TOTAL VENTAS SEGÚN CORTE
                    </th>

                    <th class="text-end whitespace-nowrap">
                        GALONES
                    </th>

                    <th class="text-end whitespace-nowrap">
                        VALOR
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <th scope="row" class="text-left">
                        CORRIENTE
                    </th>

                    <td>
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-galones-corriente text-end"
                            value="{{ isset($turno) ? number_format($turno->tirillas_galones_corriente, 3, '.', ',') : '' }}">
                    </td>

                    <td>
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-valor-corriente text-end"
                            data-precio="{{ $precioCorriente }}"
                            value="{{ isset($turno) ? number_format($turno->tirillas_valor_corriente, 0, ',', '.') : '' }}">
                    </td>
                </tr>

                <tr>
                    <th scope="row" class="text-left">
                        ACPM
                    </th>

                    <td>
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-galones-acpm text-end"
                            value="{{ isset($turno) ? number_format($turno->tirillas_galones_acpm, 3, '.', ',') : '' }}">
                    </td>

                    <td>
                        <input type="text" readonly
                            class="form-control form-control-sm erp-input tirillas-valor-acpm text-end"
                            data-precio="{{ $precioAcpm }}"
                            value="{{ isset($turno) ? number_format($turno->tirillas_valor_acpm, 0, ',', '.') : '' }}">
                    </td>
                </tr>
            </tbody>

            <tfoot>
                <tr class="table-secondary fw-bold">
                    <td class="text-start">TOTAL</td>
                    <td class="text-end ventas-total-galones">
                        {{ isset($turno) ? number_format($turno->tirillas_galones_corriente + $turno->tirillas_galones_acpm, 3, '.', ',') : '' }}
                    </td>
                    <td class="text-end ventas-total-turno">
                        {{ isset($turno) ? number_format($turno->total_ventas, 0, ',', '.') : '0' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
        (function() {
            const contenedor = document.getElementById('tabla-ventas-turno');
            if (!contenedor) return;

            // Índice siguiente para los nombres ventas[i][...]: único en todos los surtidores.
            let siguienteIndice = {{ $indiceVenta }};

            // También la usa el autoguardado para recrear las filas de un borrador.
            window.agregarFilaVenta = function(surtidor, indice = null) {
                const grupo = Array.from(contenedor.querySelectorAll('.ventas-grupo'))
                    .find(g => g.dataset.surtidor === surtidor);
                if (!grupo) return null;

                const i = indice ?? siguienteIndice;
                siguienteIndice = Math.max(siguienteIndice, i + 1);

                const fila = document.createElement('tr');
                fila.className = 'venta-fila';
                fila.dataset.index = i;
                fila.innerHTML = `
                    <td>
                        <input type="hidden" name="ventas[${i}][surtidor]">
                        <input type="hidden" name="ventas[${i}][combustible]">
                        <input type="text" name="ventas[${i}][no_cierre]" maxlength="30" class="form-control form-control-sm erp-input text-center venta-no-cierre">
                    </td>
                    <td class="text-end">
                        <input type="text" name="ventas[${i}][galones]"
                            class="form-control form-control-sm erp-input galones-input ${grupo.dataset.claseGalones}">
                    </td>
                    <td class="text-end">
                        <input type="text" name="ventas[${i}][valor]" class="form-control form-control-sm erp-input valor-total" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger quitar-venta" aria-label="Quitar fila">×</button>
                    </td>`;
                fila.querySelector(`[name="ventas[${i}][surtidor]"]`).value = grupo.dataset.surtidor;
                fila.querySelector(`[name="ventas[${i}][combustible]"]`).value = grupo.dataset.combustible;
                fila.querySelector('.galones-input').dataset.precio = grupo.dataset.precio;

                grupo.querySelector('.ventas-filas').appendChild(fila);
                window.initGalones?.();

                return fila;
            };

            contenedor.addEventListener('click', function(event) {
                const agregar = event.target.closest('.agregar-venta');
                if (agregar) {
                    const fila = window.agregarFilaVenta(agregar.closest('.ventas-grupo').dataset.surtidor);
                    fila?.querySelector('.venta-no-cierre')?.focus();
                    return;
                }

                const quitar = event.target.closest('.quitar-venta');
                if (quitar) {
                    quitar.closest('tr').remove();
                    window.recalcularVentasTurno?.();
                    contenedor.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        })();
    </script>

</x-erp-card>
