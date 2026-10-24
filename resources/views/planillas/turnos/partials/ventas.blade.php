{{-- Ventas según cierres de IAPROPIADA: un bloque por surtidor y combustible. Un surtidor
     puede tener varios cierres en el turno, por eso cada bloque tiene + AGREGAR FILA. --}}
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
        .tabla-ventas-turno .ventas-grupo-titulo th {
            background-color: #b5fdd2;
            vertical-align: middle;
        }

        .tabla-ventas-turno .agregar-venta {
            font-size: .7rem;
            padding: .1rem .35rem;
            white-space: nowrap;
        }

        .tabla-ventas-turno .quitar-venta {
            line-height: 1;
            text-decoration: none;
            font-weight: 700;
        }
    </style>

    <div class="table-responsive">
        <table class="table table-bordered table-sm tabla-ventas-turno" id="tabla-ventas-turno">

            <thead class="ventas-header">
                <tr class="ventas-title-row" style="background-color: #b5fdd2;">
                    <th colspan="4" class="text-center font-bold">
                        VENTAS SEGÚN CIERRES DE IAPPROPIADA
                    </th>
                </tr>
            </thead>

            @foreach ($surtidoresVentas as $surtidor => $combustible)
                @php
                    $filasSurtidor = $ventasGuardadas->where('surtidor', $surtidor)->values();
                    $precioSurtidor = $combustible === 'ACPM' ? $precioAcpm : $precioCorriente;
                    $claseGalones = $combustible === 'ACPM' ? 'galones-acpm' : 'galones-cte';
                @endphp
                <tbody class="ventas-grupo" data-surtidor="{{ $surtidor }}" data-combustible="{{ $combustible }}"
                    data-etiqueta="{{ $etiquetaSurtidor($surtidor) }}" data-precio="{{ $precioSurtidor }}" data-clase-galones="{{ $claseGalones }}">
                    <tr class="ventas-grupo-titulo">
                        <th>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
                                <span>{{ $etiquetaSurtidor($surtidor) }}</span>
                                <button type="button" class="btn btn-sm btn-outline-primary agregar-venta">+ AGREGAR FILA</button>
                            </div>
                        </th>
                        <th class="text-center">NO. CIERRE</th>
                        <th class="text-end">GALONES</th>
                        <th class="text-end">VALOR</th>
                    </tr>

                    {{-- Un surtidor sin cierres guardados muestra una fila vacía para digitar. --}}
                    @foreach ($filasSurtidor->isEmpty() ? [null] : $filasSurtidor as $venta)
                        <tr class="venta-fila" data-index="{{ $indiceVenta }}">
                            <td class="text-nowrap">
                                <input type="hidden" name="ventas[{{ $indiceVenta }}][surtidor]" value="{{ $surtidor }}">
                                <input type="hidden" name="ventas[{{ $indiceVenta }}][combustible]" value="{{ $combustible }}">
                                <span class="venta-surtidor">{{ $etiquetaSurtidor($surtidor) }}</span>
                                <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1 quitar-venta"
                                    title="Quitar fila" aria-label="Quitar fila">×</button>
                            </td>
                            <td>
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
                        </tr>
                        @php($indiceVenta++)
                    @endforeach
                </tbody>
            @endforeach

            <!-- Sección: Total ventas según corte -->
            <thead class="bg-light">
                <tr>
                    <th colspan="2" class="text-left whitespace-nowrap">
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
                    <th scope="row" colspan="2" class="text-left">
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
                    <th scope="row" colspan="2" class="text-left">
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
                    <td colspan="2" class="text-start">TOTAL</td>
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
            const tabla = document.getElementById('tabla-ventas-turno');
            if (!tabla) return;

            // Índice siguiente para los nombres ventas[i][...]: único en toda la tabla.
            let siguienteIndice = {{ $indiceVenta }};

            // También la usa el autoguardado para recrear las filas de un borrador.
            window.agregarFilaVenta = function(surtidor, indice = null) {
                const grupo = Array.from(tabla.querySelectorAll('.ventas-grupo'))
                    .find(g => g.dataset.surtidor === surtidor);
                if (!grupo) return null;

                const i = indice ?? siguienteIndice;
                siguienteIndice = Math.max(siguienteIndice, i + 1);

                const fila = document.createElement('tr');
                fila.className = 'venta-fila';
                fila.dataset.index = i;
                fila.innerHTML = `
                    <td class="text-nowrap">
                        <input type="hidden" name="ventas[${i}][surtidor]">
                        <input type="hidden" name="ventas[${i}][combustible]">
                        <span class="venta-surtidor"></span>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1 quitar-venta"
                            title="Quitar fila" aria-label="Quitar fila">×</button>
                    </td>
                    <td>
                        <input type="text" name="ventas[${i}][no_cierre]" maxlength="30" class="form-control form-control-sm erp-input text-center venta-no-cierre">
                    </td>
                    <td class="text-end">
                        <input type="text" name="ventas[${i}][galones]"
                            class="form-control form-control-sm erp-input galones-input ${grupo.dataset.claseGalones}">
                    </td>
                    <td class="text-end">
                        <input type="text" name="ventas[${i}][valor]" class="form-control form-control-sm erp-input valor-total" readonly>
                    </td>`;
                fila.querySelector(`[name="ventas[${i}][surtidor]"]`).value = grupo.dataset.surtidor;
                fila.querySelector(`[name="ventas[${i}][combustible]"]`).value = grupo.dataset.combustible;
                fila.querySelector('.venta-surtidor').textContent = grupo.dataset.etiqueta;
                fila.querySelector('.galones-input').dataset.precio = grupo.dataset.precio;

                grupo.appendChild(fila);
                window.initGalones?.();

                return fila;
            };

            tabla.addEventListener('click', function(event) {
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
                    tabla.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        })();
    </script>

</x-erp-card>
